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

        // The current PaymentService is gateway-specific (bkash/nagad/rocket).
        // Create a real, active, configured bKash gateway to exercise it.
        $this->gateway = PaymentGateway::create([
            'code' => 'bkash',
            'name' => 'bKash',
            'type' => 'mobile_financial_service',
            'is_active' => true,
            'is_online' => true,
            'has_api' => true,
            'test_mode' => true,
            'sandbox_url' => 'https://sandbox.bkash.com',
            'live_url' => 'https://api.bkash.com',
            'api_key' => 'test_api_key',
            'api_secret' => 'test_api_secret',
            'api_username' => 'test_username',
            'api_password' => 'test_password',
            'callback_url' => 'https://example.com/callback',
            'currency' => 'BDT',
            'config' => [
                'test_mode' => true,
                'api_url' => 'https://sandbox.bkash.com',
                'api_key' => 'test_api_key',
                'api_secret' => 'test_api_secret',
                'api_username' => 'test_username',
                'api_password' => 'test_password',
            ],
        ]);

        // Create a test payment
        $this->payment = Payment::factory()->create([
            'amount' => 1000,
            'total_amount' => 1000,
            'payment_method' => 'bkash',
            'payment_status' => Payment::STATUS_PENDING,
            'invoice_number' => 'INV'.uniqid(),
            'payment_details' => [
                'description' => 'Test Payment',
            ],
        ]);
    }

    /** @test */
    public function it_can_initialize_payment()
    {
        Http::fake([
            '*sandbox.bkash.com/checkout/token/grant*' => Http::response(['id_token' => 'TEST_TOKEN'], 200),
            '*sandbox.bkash.com/checkout/create*' => Http::response([
                'paymentID' => 'PID123',
                'bkashURL' => 'https://bkash.com/pay/123',
                'createTime' => now()->toIso8601String(),
                'orgLogo' => 'logo.png',
            ], 200),
        ]);

        $result = $this->paymentService->initializePayment($this->payment, 'bkash');

        $this->assertTrue($result['success']);
        $this->assertEquals('https://bkash.com/pay/123', $result['redirect_url']);

        // Verify payment was updated with gateway identifiers
        $this->payment->refresh();
        $this->assertArrayHasKey('bkash_payment_id', $this->payment->payment_details);
        $this->assertArrayHasKey('bkash_token', $this->payment->payment_details);
    }

    /** @test */
    public function it_throws_for_inactive_gateway()
    {
        $inactive = PaymentGateway::create([
            'code' => 'bkash_inactive',
            'name' => 'bKash Inactive',
            'type' => 'mobile_financial_service',
            'is_active' => false,
            'is_online' => true,
            'has_api' => true,
            'test_mode' => true,
            'sandbox_url' => 'https://sandbox.bkash.com',
            'live_url' => 'https://api.bkash.com',
            'currency' => 'BDT',
            'config' => [],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment gateway is not active');

        $this->paymentService->initializePayment($this->payment, 'bkash_inactive');
    }

    /** @test */
    public function it_handles_payment_callback()
    {
        // The callback looks the payment up by merchantInvoiceNumber / bkash_payment_id
        // and needs the stored bkash token to verify via /checkout/execute.
        $this->payment->update([
            'payment_details' => array_merge($this->payment->payment_details, [
                'bkash_payment_id' => 'PID123',
                'bkash_token' => 'STORED_TOKEN',
            ]),
        ]);

        Http::fake([
            '*sandbox.bkash.com/checkout/execute*' => Http::response([
                'paymentID' => 'PID123',
                'trxID' => 'TXN'.time(),
                'transactionStatus' => 'Completed',
            ], 200),
        ]);

        $result = $this->paymentService->processCallback('bkash', [
            'merchantInvoiceNumber' => $this->payment->invoice_number,
            'paymentID' => 'PID123',
        ]);

        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals(Payment::STATUS_COMPLETED, $result->payment_status);
        $this->assertArrayHasKey('payment_method_details', $result->payment_details);
    }

    /** @test */
    public function it_verifies_payment_status()
    {
        $this->payment->update([
            'payment_status' => Payment::STATUS_PENDING,
            'payment_details' => array_merge($this->payment->payment_details, [
                'bkash_payment_id' => 'PID123',
            ]),
        ]);

        Http::fake([
            '*sandbox.bkash.com/checkout/token/grant*' => Http::response(['id_token' => 'TEST_TOKEN'], 200),
            '*sandbox.bkash.com/checkout/payment/status*' => Http::response([
                'paymentID' => 'PID123',
                'transactionStatus' => 'Completed',
                'completedTime' => now()->toIso8601String(),
            ], 200),
        ]);

        $result = $this->paymentService->verifyPayment($this->payment);

        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals(Payment::STATUS_COMPLETED, $result->payment_status);
        $this->assertArrayHasKey('verification_response', $result->payment_details);
    }

    /** @test */
    public function it_processes_refunds()
    {
        Http::fake([
            '*checkout.sandbox.bka.sh/tokenized/checkout/token/grant*' => Http::response(['id_token' => 'TEST_TOKEN'], 200),
            '*checkout.sandbox.bka.sh/tokenized/checkout/payment/refund*' => Http::response([
                'statusCode' => '0000',
                'refundTrxID' => 'REF'.time(),
                'statusMessage' => 'Refund successful',
            ], 200),
        ]);

        $result = $this->paymentService->processRefund('bkash', 'TXN123', 1000, 'Test refund');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('transaction_id', $result);
    }

    /** @test */
    public function it_throws_on_failed_gateway_initialization()
    {
        Http::fake([
            '*sandbox.bkash.com/checkout/token/grant*' => Http::response([], 400),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to initialize bKash payment');

        $this->paymentService->initializePayment($this->payment, 'bkash');
    }
}
