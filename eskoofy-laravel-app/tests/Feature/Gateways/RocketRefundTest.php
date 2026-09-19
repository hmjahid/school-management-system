<?php

namespace Tests\Feature\Gateways;

use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RocketRefundTest extends TestCase
{
    use RefreshDatabase;

    protected $paymentService;

    protected $admin;

    protected $user;

    protected $payment;

    protected $apiKey = 'rocket_test_api_key';

    protected $storeId = 'rocket_store_123';

    protected $storePassword = 'test_password';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        // Create test users
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->admin->assignRole('admin');
        $this->user = User::factory()->create(['email' => 'user@example.com']);

        // Create a completed Rocket payment
        $this->payment = Payment::factory()->create([
            'created_by' => $this->user->id,
            'amount' => 2000.00,
            'payment_status' => Payment::STATUS_COMPLETED,
            'payment_method' => 'rocket',
            'transaction_id' => 'RKT'.uniqid(),
            'payment_details' => [
                'gateway' => 'rocket',
                'invoice_no' => 'INV-'.uniqid(),
                'bank_tran_id' => 'BT'.uniqid(),
                'card_issuer' => 'Rocket',
                'card_brand' => 'Rocket',
                'card_issuer_country' => 'Bangladesh',
                'card_issuer_country_code' => 'BD',
                'store_amount' => '1975.00',
                'currency_type' => 'BDT',
                'currency_amount' => '2000.00',
                'verify_sign' => 'verified_'.md5(uniqid()),
                'verify_key' => 'amount,currency_type,currency_amount,payment_status,validation_date',
                'risk_level' => 0,
                'risk_title' => 'Safe',
            ],
        ]);

        $this->paymentService = app(PaymentService::class);

        // Configure test webhook secret
        config(['payment.gateways.rocket.webhook_secret' => 'test_store_password']);

        // Mock Rocket API responses
        $this->mockRocketApis();
    }

    protected function mockRocketApis()
    {
        // Mock Rocket token endpoint
        Http::fake([
            'api.rocket.com.bd/api/v1/token' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'rocket_test_access_token',
                'expires_in' => 3600,
                'status' => 'success',
            ], 200),

            // Mock Rocket refund status check
            'api.rocket.com.bd/api/v1/refund/status/*' => Http::response([
                'status' => 'success',
                'refund_id' => 'RFD'.uniqid(),
                'transaction_id' => $this->payment->transaction_id,
                'refund_amount' => '1000.00',
                'currency' => 'BDT',
                'status' => 'Completed',
                'refund_date' => now()->toIso8601String(),
            ], 200),
        ]);
    }

    #[Test]
    public function it_processes_rocket_refund_successfully()
    {
        Http::fake([
            'api.rocket.com.bd/api/v1/refund' => Http::response([
                'status' => 'success',
                'message' => 'Refund request has been executed successfully',
                'refund_id' => 'RFD'.uniqid(),
                'transaction_id' => $this->payment->transaction_id,
                'refund_amount' => '1000.00',
                'currency' => 'BDT',
                'status' => 'Completed',
                'refund_date' => now()->toIso8601String(),
            ], 200),
        ]);

        // Process refund
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/payments/{$this->payment->id}/refunds", [
                'amount' => 1000.00,
                'reason' => 'Customer requested partial refund',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'amount' => '1000.00',
                    'status' => 'completed',
                ],
            ]);

        // Verify refund was recorded
        $this->assertDatabaseHas('refunds', [
            'payment_id' => $this->payment->id,
            'amount' => 1000.00,
            'status' => 'completed',
        ]);
    }

    #[Test]
    public function it_handles_rocket_refund_failure()
    {
        // Mock Rocket refund failure
        Http::fake([
            'api.rocket.com.bd/api/v1/refund' => Http::response([
                'status' => 'failed',
                'message' => 'Insufficient balance',
                'code' => 'insufficient_balance',
            ], 400),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/payments/{$this->payment->id}/refunds", [
                'amount' => 1000.00,
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

    #[Test]
    public function it_handles_rocket_webhook_notifications()
    {
        $refund = $this->payment->refunds()->create([
            'user_id' => $this->user->id,
            'processed_by' => $this->admin->id,
            'amount' => 1000.00,
            'currency' => 'BDT',
            'status' => 'pending',
            'reason' => 'Webhook test',
        ]);

        // Simulate Rocket webhook
        $webhookPayload = [
            'refund_id' => 'RFD'.uniqid(),
            'transaction_id' => $this->payment->transaction_id,
            'refund_amount' => '1000.00',
            'currency' => 'BDT',
            'status' => 'Completed',
            'refund_date' => now()->toIso8601String(),
            'reason' => 'Customer requested refund',
        ];

        $response = $this->postJson('/api/webhooks/rocket/refund', $webhookPayload, [
            'X-Rocket-Signature' => $this->generateRocketSignature($webhookPayload),
        ]);

        $response->assertStatus(200);

        // Verify refund was updated
        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => 'completed',
        ]);
    }

    #[Test]
    public function it_handles_concurrent_refund_requests()
    {
        Http::fake([
            'api.rocket.com.bd/api/v1/refund' => Http::response([
                'status' => 'success',
                'message' => 'Refund request has been executed successfully',
                'refund_id' => 'RFD'.uniqid(),
                'transaction_id' => $this->payment->transaction_id,
                'refund_amount' => '1000.00',
                'currency' => 'BDT',
                'status' => 'Completed',
                'refund_date' => now()->toIso8601String(),
            ], 200),
        ]);

        // Simulate multiple concurrent refund requests
        $responses = [];

        // First request - should succeed
        $responses[] = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/payments/{$this->payment->id}/refunds", [
                'amount' => 1000.00,
                'reason' => 'First refund',
            ]);

        // Second concurrent request - should be blocked by database lock
        $responses[] = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/payments/{$this->payment->id}/refunds", [
                'amount' => 1000.00,
                'reason' => 'Concurrent refund',
            ]);

        // Verify responses
        $this->assertEquals(201, $responses[0]->getStatusCode());
        $this->assertEquals(422, $responses[1]->getStatusCode());

        // Verify only one refund was processed
        $this->assertEquals(1, $this->payment->refunds()->count());
    }

    #[Test]
    public function it_handles_partial_refunds_correctly()
    {
        Http::fake([
            'api.rocket.com.bd/api/v1/refund' => Http::response([
                'status' => 'success',
                'message' => 'Refund request has been executed successfully',
                'refund_id' => 'RFD'.uniqid(),
                'transaction_id' => $this->payment->transaction_id,
                'refund_amount' => '1000.00',
                'currency' => 'BDT',
                'status' => 'Completed',
                'refund_date' => now()->toIso8601String(),
            ], 200),
        ]);

        // First partial refund
        $response1 = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/payments/{$this->payment->id}/refunds", [
                'amount' => 1000.00,
                'reason' => 'First partial refund',
            ]);

        $response1->assertStatus(201);

        // Second partial refund
        $response2 = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/payments/{$this->payment->id}/refunds", [
                'amount' => 800.00,
                'reason' => 'Second partial refund',
            ]);

        $response2->assertStatus(201);

        // Verify both refunds were processed
        $this->assertEquals(2, $this->payment->refresh()->refunds()->count());
        $this->assertEquals(1800.00, $this->payment->refunds()->sum('amount'));
        $this->assertEquals('partially_refunded', $this->payment->refresh()->refund_status);

        // Third refund that would exceed the payment amount
        $response3 = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/payments/{$this->payment->id}/refunds", [
                'amount' => 300.00,
                'reason' => 'Third partial refund',
            ]);

        $response3->assertStatus(422);

        // Verify only 200.00 is refundable now
        $response4 = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/payments/{$this->payment->id}/refunds", [
                'amount' => 200.00,
                'reason' => 'Final partial refund',
            ]);

        $response4->assertStatus(201);
        $this->assertEquals('fully_refunded', $this->payment->refresh()->refund_status);
    }

    protected function generateRocketSignature(array $data): string
    {
        // Match server-side HMAC verification: HMAC-SHA256 of the raw JSON body.
        return hash_hmac('sha256', json_encode($data, JSON_UNESCAPED_SLASHES), 'test_store_password');
    }
}
