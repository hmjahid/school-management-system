<?php

namespace Tests\Feature\Gateways;

use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BkashRefundTest extends TestCase
{
    use RefreshDatabase;

    protected $paymentService;
    protected $admin;
    protected $user;
    protected $payment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->user = User::factory()->create(['email' => 'user@example.com']);

        // Create a completed bKash payment
        $this->payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 1000.00,
            'currency' => 'BDT',
            'payment_status' => Payment::STATUS_COMPLETED,
            'payment_method' => 'bkash',
            'transaction_id' => 'TRX' . uniqid(),
            'payment_details' => [
                'gateway' => 'bkash',
                'trx_id' => 'TRX' . uniqid(),
                'sender' => '017XXXXXXXX',
                'reference' => 'INV-' . uniqid(),
            ],
        ]);

        $this->paymentService = app(PaymentService::class);

        // Mock bKash API responses
        $this->mockBkashApis();
    }

    protected function mockBkashApis()
    {
        // Mock bKash token endpoint
        Http::fake([
            'tokenized/checkout/token/grant' => Http::response([
                'token_type' => 'Bearer',
                'id_token' => 'mock_id_token',
                'refresh_token' => 'mock_refresh_token',
                'expires_in' => 3600,
                'status' => 'success',
                'statusCode' => '0000',
                'statusMessage' => 'Success',
            ], 200),

            // Mock bKash search transaction endpoint
            'tokenized/checkout/payment/status/*' => Http::response([
                'statusCode' => '0000',
                'statusMessage' => 'Successful',
                'paymentID' => $this->payment->transaction_id,
                'merchantInvoiceNumber' => $this->payment->payment_details['reference'],
                'transactionStatus' => 'Completed',
                'amount' => (string) $this->payment->amount,
                'currency' => $this->payment->currency,
                'intent' => 'sale',
                'paymentExecuteTime' => now()->toIso8601String(),
            ], 200),

            // Mock bKash refund endpoint
            'tokenized/checkout/payment/refund' => Http::response([
                'statusCode' => '0000',
                'statusMessage' => 'Refund request has been executed successfully',
                'refundTrxID' => 'R' . uniqid(),
                'amount' => 500.00,
                'currency' => 'BDT',
                'transactionStatus' => 'Completed',
                'completedTime' => now()->toIso8601String(),
                'originalTrxID' => $this->payment->transaction_id,
            ], 200),
        ]);
    }

    /** @test */
    public function it_processes_bkash_refund_successfully()
    {
        // Process refund
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/payments/{$this->payment->id}/refunds", [
                'amount' => 500.00,
                'reason' => 'Customer requested partial refund',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'amount' => '500.00',
                    'status' => 'completed',
                ]
            ]);

        // Verify refund was recorded
        $this->assertDatabaseHas('refunds', [
            'payment_id' => $this->payment->id,
            'amount' => 500.00,
            'status' => 'completed',
        ]);

        // Verify payment was updated
        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
            'refund_status' => 'partially_refunded',
        ]);
    }

    /** @test */
    public function it_handles_bkash_refund_failure()
    {
        // Mock bKash refund failure
        Http::fake([
            'tokenized/checkout/payment/refund' => Http::response([
                'statusCode' => '2007',
                'statusMessage' => 'Insufficient balance',
                'errorCode' => '2007',
                'errorMessage' => 'Insufficient balance',
            ], 400),
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/payments/{$this->payment->id}/refunds", [
                'amount' => 500.00,
                'reason' => 'Test refund',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Failed to process refund: Insufficient balance',
            ]);

        // Verify refund was marked as failed
        $this->assertDatabaseHas('refunds', [
            'payment_id' => $this->payment->id,
            'status' => 'failed',
        ]);
    }

    /** @test */
    public function it_handles_concurrent_refund_attempts()
    {
        // Simulate concurrent refund attempts
        $responses = [];
        
        // First request - should succeed
        $responses[] = $this->actingAs($this->admin, 'api')
            ->postJson("/api/payments/{$this->payment->id}/refunds", [
                'amount' => 500.00,
                'reason' => 'First refund',
            ]);

        // Second concurrent request - should fail (duplicate)
        $responses[] = $this->actingAs($this->admin, 'api')
            ->postJson("/api/payments/{$this->payment->id}/refunds", [
                'amount' => 500.00,
                'reason' => 'Duplicate refund',
            ]);

        // Verify responses
        $this->assertEquals(201, $responses[0]->getStatusCode());
        $this->assertEquals(422, $responses[1]->getStatusCode());

        // Verify only one refund was processed
        $this->assertEquals(1, $this->payment->refunds()->count());
    }

    /** @test */
    public function it_handles_webhook_notifications()
    {
        $refund = $this->payment->refunds()->create([
            'user_id' => $this->user->id,
            'processed_by' => $this->admin->id,
            'amount' => 500.00,
            'currency' => 'BDT',
            'status' => 'pending',
            'reason' => 'Webhook test',
        ]);

        // Simulate bKash webhook
        $webhookPayload = [
            'paymentID' => $this->payment->transaction_id,
            'refundTrxID' => 'R' . uniqid(),
            'amount' => '500.00',
            'transactionStatus' => 'Completed',
            'currency' => 'BDT',
            'completedTime' => now()->toIso8601String(),
            'reference' => $this->payment->payment_details['reference'],
        ];

        $response = $this->postJson('/api/webhooks/bkash/refund', $webhookPayload, [
            'X-Webhook-Signature' => $this->generateBkashSignature($webhookPayload),
        ]);

        $response->assertStatus(200);
        
        // Verify refund was updated
        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => 'completed',
            'transaction_id' => $webhookPayload['refundTrxID'],
        ]);
    }

    protected function generateBkashSignature(array $data): string
    {
        // In a real implementation, this would use the same signature generation as the bKash SDK
        // This is a simplified version for testing
        ksort($data);
        $signatureData = json_encode($data, JSON_UNESCAPED_SLASHES);
        return hash_hmac('sha256', $signatureData, 'test_secret');
    }
}
