<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $paymentService;
    protected $payment;
    protected $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->paymentService = app(PaymentService::class);
        
        // Create a test payment gateway
        $this->gateway = PaymentGateway::factory()->create([
            'code' => 'test_gateway',
            'name' => 'Test Gateway',
            'is_active' => true,
            'is_online' => true,
            'config' => [
                'api_url' => 'https://api.test-gateway.com/v1',
                'api_key' => 'test_api_key',
                'api_secret' => 'test_api_secret',
                'callback_url' => 'https://yourdomain.com/api/payments/callback/test_gateway',
            ],
        ]);
        
        // Create a test payment
        $this->payment = Payment::factory()->create([
            'amount' => 1000,
            'currency' => 'BDT',
            'payment_method' => 'test_gateway',
            'payment_status' => Payment::STATUS_PENDING,
            'payment_details' => [
                'description' => 'Test Payment',
                'metadata' => ['order_id' => 'TEST123'],
            ],
        ]);
        
        // Mock HTTP client
        Http::fake();
    }

    /** @test */
    public function it_can_initialize_payment()
    {
        $response = [
            'success' => true,
            'payment_url' => 'https://test-gateway.com/pay/123',
            'transaction_id' => 'TXN' . time(),
        ];
        
        Http::fake([
            'api.test-gateway.com/*' => Http::response($response, 200),
        ]);
        
        $result = $this->paymentService->initializePayment($this->payment, 'test_gateway');
        
        $this->assertTrue($result['success']);
        $this->assertEquals($response['payment_url'], $result['redirect_url']);
        
        // Verify payment was updated
        $this->payment->refresh();
        $this->assertNotNull($this->payment->transaction_id);
        $this->assertArrayHasKey('init_response', $this->payment->payment_details);
    }
    
    /** @test */
    public function it_handles_payment_callback()
    {
        $callbackData = [
            'transaction_id' => 'TXN' . time(),
            'status' => 'completed',
            'amount' => 1000,
            'currency' => 'BDT',
            'signature' => 'test_signature',
        ];
        
        $result = $this->paymentService->processCallback('test_gateway', $callbackData);
        
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals('completed', $result->payment_status);
        $this->assertArrayHasKey('callback_data', $result->payment_details);
    }
    
    /** @test */
    public function it_verifies_payment_status()
    {
        $transactionId = 'TXN' . time();
        
        $this->payment->update([
            'transaction_id' => $transactionId,
            'payment_details' => array_merge($this->payment->payment_details, [
                'verification_url' => 'https://api.test-gateway.com/verify/'.$transactionId,
            ]),
        ]);
        
        $verificationResponse = [
            'status' => 'completed',
            'amount' => 1000,
            'currency' => 'BDT',
            'timestamp' => now()->toIso8601String(),
        ];
        
        Http::fake([
            'api.test-gateway.com/verify/*' => Http::response($verificationResponse, 200),
        ]);
        
        $result = $this->paymentService->verifyPayment($this->payment);
        
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals('completed', $result->payment_status);
        $this->assertArrayHasKey('verification_response', $result->payment_details);
    }
    
    /** @test */
    public function it_handles_webhook_events()
    {
        $webhookData = [
            'event' => 'payment.completed',
            'data' => [
                'transaction_id' => 'TXN' . time(),
                'amount' => 1000,
                'currency' => 'BDT',
                'status' => 'completed',
                'timestamp' => now()->toIso8601String(),
            ],
            'signature' => 'test_webhook_signature',
        ];
        
        $result = $this->paymentService->processWebhook('test_gateway', $webhookData);
        
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals('completed', $result->payment_status);
        $this->assertArrayHasKey('webhook_data', $result->payment_details);
    }
    
    /** @test */
    public function it_processes_refunds()
    {
        $this->payment->update([
            'payment_status' => Payment::STATUS_COMPLETED,
            'transaction_id' => 'TXN' . time(),
        ]);
        
        $refundResponse = [
            'success' => true,
            'refund_id' => 'REF' . time(),
            'amount' => 1000,
            'status' => 'processed',
        ];
        
        Http::fake([
            'api.test-gateway.com/refund' => Http::response($refundResponse, 200),
        ]);
        
        $result = $this->paymentService->processRefund($this->payment, 1000, 'Test refund');
        
        $this->assertTrue($result['success']);
        $this->assertEquals($refundResponse['refund_id'], $result['refund_id']);
        
        $this->payment->refresh();
        $this->assertEquals('refunded', $this->payment->payment_status);
    }
    
    /** @test */
    public function it_handles_failed_payments()
    {
        $errorResponse = [
            'error' => [
                'code' => 'INSUFFICIENT_FUNDS',
                'message' => 'Insufficient funds in account',
            ],
        ];
        
        Http::fake([
            'api.test-gateway.com/*' => Http::response($errorResponse, 400),
        ]);
        
        $result = $this->paymentService->initializePayment($this->payment, 'test_gateway');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('payment_failed', $result['error_code']);
        
        $this->payment->refresh();
        $this->assertEquals('failed', $this->payment->payment_status);
        $this->assertArrayHasKey('error', $this->payment->payment_details);
    }
    
    /** @test */
    public function it_validates_webhook_signature()
    {
        $webhookData = [
            'event' => 'payment.completed',
            'data' => [
                'transaction_id' => 'TXN' . time(),
                'amount' => 1000,
                'currency' => 'BDT',
                'status' => 'completed',
            ],
            'signature' => 'invalid_signature',
        ];
        
        // Mock the signature validation to fail
        $mock = $this->partialMock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('validateWebhookSignature')->andReturn(false);
        });
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid webhook signature');
        
        $mock->processWebhook('test_gateway', $webhookData);
    }
    
    /** @test */
    public function it_handles_duplicate_webhooks()
    {
        $transactionId = 'TXN' . time();
        
        // Create a completed payment
        $payment = Payment::factory()->create([
            'transaction_id' => $transactionId,
            'payment_status' => Payment::STATUS_COMPLETED,
            'payment_details' => [
                'webhook_processed' => true,
            ],
        ]);
        
        $webhookData = [
            'event' => 'payment.completed',
            'data' => [
                'transaction_id' => $transactionId,
                'amount' => 1000,
                'currency' => 'BDT',
                'status' => 'completed',
            ],
            'signature' => 'test_signature',
        ];
        
        $result = $this->paymentService->processWebhook('test_gateway', $webhookData);
        
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals('completed', $result->payment_status);
        
        // Verify no duplicate processing occurred
        $this->assertCount(1, Payment::where('transaction_id', $transactionId)->get());
    }
}
