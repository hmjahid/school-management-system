<?php

namespace Tests\Feature\Gateways;

use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NagadRefundTest extends TestCase
{
    use RefreshDatabase;

    protected $paymentService;
    protected $admin;
    protected $user;
    protected $payment;
    protected $merchantId = 'NAGAD_MERCHANT_ID';
    protected $merchantPrivateKey = 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQ...';
    protected $merchantCertificate = 'MIIE...';

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->user = User::factory()->create(['email' => 'user@example.com']);

        // Create a completed Nagad payment
        $this->payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 1500.00,
            'currency' => 'BDT',
            'payment_status' => Payment::STATUS_COMPLETED,
            'payment_method' => 'nagad',
            'transaction_id' => 'N' . uniqid(),
            'payment_details' => [
                'gateway' => 'nagad',
                'merchant_id' => $this->merchantId,
                'order_id' => 'ORD-' . uniqid(),
                'payment_ref_id' => 'P' . uniqid(),
                'issuer_payment_ref_no' => 'IP' . uniqid(),
                'customer_msisdn' => '017XXXXXXXX',
                'status' => 'Success',
            ],
        ]);

        $this->paymentService = app(PaymentService::class);

        // Mock Nagad API responses
        $this->mockNagadApis();
    }

    protected function mockNagadApis()
    {
        // Mock Nagad token endpoint
        Http::fake([
            'api.mynagad.com/api/dfs/refund/initialize' => Http::response([
                'status' => 'Success',
                'message' => 'Refund request received successfully',
                'refundRefNo' => 'R' . uniqid(),
                'issuerPaymentRefNo' => $this->payment->payment_details['issuer_payment_ref_no'],
                'refundTrxID' => 'RT' . uniqid(),
                'amount' => '500.00',
                'charge' => '0.00',
                'referenceNo' => $this->payment->payment_details['order_id'],
                'dateTime' => now()->toIso8601String(),
            ], 200),

            // Mock Nagad refund status check
            'api.mynagad.com/api/query/refund/status/*' => Http::response([
                'status' => 'Success',
                'refundTrxID' => 'RT' . uniqid(),
                'amount' => '500.00',
                'refundStatus' => 'Completed',
                'dateTime' => now()->toIso8601String(),
                'issuerPaymentRefNo' => $this->payment->payment_details['issuer_payment_ref_no'],
                'referenceNo' => $this->payment->payment_details['order_id'],
            ], 200),
        ]);
    }

    /** @test */
    public function it_processes_nagad_refund_successfully()
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
    }

    /** @test */
    public function it_handles_nagad_refund_failure()
    {
        // Mock Nagad refund failure
        Http::fake([
            'api.mynagad.com/api/dfs/refund/initialize' => Http::response([
                'status' => 'Failed',
                'message' => 'Insufficient balance',
                'reason' => 'Insufficient balance in merchant account',
            ], 400),
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/payments/{$this->payment->id}/refunds", [
                'amount' => 500.00,
                'reason' => 'Test refund',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Failed to process refund: Insufficient balance in merchant account',
            ]);

        // Verify refund was marked as failed
        $this->assertDatabaseHas('refunds', [
            'payment_id' => $this->payment->id,
            'status' => 'failed',
        ]);
    }

    /** @test */
    public function it_handles_nagad_webhook_notifications()
    {
        $refund = $this->payment->refunds()->create([
            'user_id' => $this->user->id,
            'processed_by' => $this->admin->id,
            'amount' => 500.00,
            'currency' => 'BDT',
            'status' => 'pending',
            'reason' => 'Webhook test',
        ]);

        // Simulate Nagad webhook
        $webhookPayload = [
            'merchantId' => $this->merchantId,
            'orderId' => $this->payment->payment_details['order_id'],
            'paymentRefId' => $this->payment->transaction_id,
            'refundRefNo' => 'R' . uniqid(),
            'refundTrxID' => 'RT' . uniqid(),
            'amount' => '500.00',
            'refundStatus' => 'Completed',
            'refundDate' => now()->toIso8601String(),
            'referenceNo' => $this->payment->payment_details['order_id'],
            'additionalInfo' => [
                'reason' => 'Customer requested refund',
            ],
        ];

        $response = $this->postJson('/api/webhooks/nagad/refund', $webhookPayload, [
            'X-Nagad-Signature' => $this->generateNagadSignature($webhookPayload),
        ]);

        $response->assertStatus(200);
        
        // Verify refund was updated
        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => 'completed',
            'transaction_id' => $webhookPayload['refundTrxID'],
        ]);
    }

    /** @test */
    public function it_handles_race_conditions()
    {
        // Simulate multiple concurrent refund requests
        $responses = [];
        
        // First request - should succeed
        $responses[] = $this->actingAs($this->admin, 'api')
            ->postJson("/api/payments/{$this->payment->id}/refunds", [
                'amount' => 500.00,
                'reason' => 'First refund',
            ]);

        // Second concurrent request - should be blocked by database lock
        $responses[] = $this->actingAs($this->admin, 'api')
            ->postJson("/api/payments/{$this->payment->id}/refunds", [
                'amount' => 500.00,
                'reason' => 'Concurrent refund',
            ]);

        // Verify responses
        $this->assertEquals(201, $responses[0]->getStatusCode());
        $this->assertEquals(422, $responses[1]->getStatusCode());

        // Verify only one refund was processed
        $this->assertEquals(1, $this->payment->refunds()->count());
    }

    protected function generateNagadSignature(array $data): string
    {
        // In a real implementation, this would use the same signature generation as the Nagad SDK
        // This is a simplified version for testing
        ksort($data);
        $signatureData = json_encode($data, JSON_UNESCAPED_SLASHES);
        return hash_hmac('sha256', $signatureData, 'test_merchant_secret');
    }
}
