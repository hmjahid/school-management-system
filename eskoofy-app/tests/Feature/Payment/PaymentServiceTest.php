<?php

namespace Tests\Feature\Payment;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = app(PaymentService::class);
    }

    protected function createBkashGateway(): PaymentGateway
    {
        return PaymentGateway::create([
            'code' => 'bkash',
            'name' => 'bKash',
            'type' => 'mobile_financial_service',
            'is_active' => true,
            'is_online' => true,
            'has_api' => true,
            'test_mode' => true,
            'sandbox_url' => 'https://checkout.sandbox.bka.sh',
            'live_url' => 'https://checkout.bka.sh',
            'api_key' => 'test_key',
            'api_secret' => 'test_secret',
            'api_username' => 'test_user',
            'api_password' => 'test_pass',
            'callback_url' => 'https://example.com/callback',
            'currency' => 'BDT',
            'config' => [
                'test_mode' => true,
                'api_url' => 'https://checkout.sandbox.bka.sh',
                'api_key' => 'test_key',
                'api_secret' => 'test_secret',
                'api_username' => 'test_user',
                'api_password' => 'test_pass',
            ],
        ]);
    }

    #[Test]
    public function it_can_initialize_offline_payment()
    {
        PaymentGateway::create([
            'code' => 'cash',
            'name' => 'Cash',
            'type' => 'other',
            'is_active' => true,
            'is_online' => false,
            'has_api' => false,
            'instructions' => 'Pay at the school office',
        ]);

        $payment = Payment::factory()->create([
            'total_amount' => 1000,
            'payment_status' => Payment::STATUS_PENDING,
        ]);

        $result = $this->paymentService->initializePayment($payment, 'cash');

        $this->assertTrue($result['success']);
        $this->assertEquals('cash', $result['gateway']);
        $this->assertEquals('Pay at the school office', $result['offline_instructions']);
    }

    #[Test]
    public function it_throws_exception_for_inactive_gateway()
    {
        PaymentGateway::create([
            'code' => 'bkash',
            'name' => 'bKash',
            'type' => 'mobile_financial_service',
            'is_active' => false,
            'is_online' => true,
            'has_api' => true,
            'test_mode' => true,
            'sandbox_url' => 'https://checkout.sandbox.bka.sh',
        ]);

        $payment = Payment::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment gateway is not active');

        $this->paymentService->initializePayment($payment, 'bkash');
    }

    #[Test]
    public function it_throws_exception_for_unknown_gateway()
    {
        $payment = Payment::factory()->create();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->paymentService->initializePayment($payment, 'does_not_exist');
    }

    #[Test]
    public function it_can_initialize_bkash_payment()
    {
        $this->createBkashGateway();

        $payment = Payment::factory()->create([
            'payment_method' => 'bkash',
            'payment_status' => Payment::STATUS_PENDING,
            'invoice_number' => 'INV'.uniqid(),
            'payment_details' => ['description' => 'Test'],
        ]);

        Http::fake([
            '*checkout.sandbox.bka.sh/checkout/token/grant*' => Http::response(['id_token' => 'TEST_TOKEN'], 200),
            '*checkout.sandbox.bka.sh/checkout/create*' => Http::response([
                'paymentID' => 'PID123',
                'bkashURL' => 'https://bkash.com/pay/123',
                'createTime' => now()->toIso8601String(),
                'orgLogo' => 'logo.png',
            ], 200),
        ]);

        $result = $this->paymentService->initializePayment($payment, 'bkash');

        $this->assertTrue($result['success']);
        $this->assertEquals('https://bkash.com/pay/123', $result['redirect_url']);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'payment_details->bkash_payment_id' => 'PID123',
        ]);
    }

    #[Test]
    public function it_can_process_bkash_callback()
    {
        $gateway = $this->createBkashGateway();

        $payment = Payment::factory()->create([
            'payment_method' => 'bkash',
            'payment_status' => Payment::STATUS_PENDING,
            'invoice_number' => 'INV'.uniqid(),
            'payment_details' => [
                'bkash_payment_id' => 'TRX12345',
                'bkash_token' => 'test_token',
            ],
        ]);

        Http::fake([
            '*checkout.sandbox.bka.sh/checkout/execute*' => Http::response([
                'paymentID' => 'TRX12345',
                'trxID' => 'TXN987',
                'transactionStatus' => 'Completed',
                'amount' => $payment->total_amount,
            ], 200),
        ]);

        $result = $this->paymentService->processCallback('bkash', [
            'paymentID' => 'TRX12345',
            'merchantInvoiceNumber' => $payment->invoice_number,
            'transactionStatus' => 'Completed',
            'amount' => $payment->total_amount,
        ]);

        $this->assertEquals(Payment::STATUS_COMPLETED, $result->payment_status);
        $this->assertEquals($payment->total_amount, $result->paid_amount);
    }

    #[Test]
    public function it_can_verify_payment_status()
    {
        $this->createBkashGateway();

        $payment = Payment::factory()->create([
            'payment_method' => 'bkash',
            'payment_status' => Payment::STATUS_PENDING,
            'payment_details' => ['bkash_payment_id' => 'TRX12345'],
        ]);

        Http::fake([
            '*checkout.sandbox.bka.sh/checkout/token/grant*' => Http::response(['id_token' => 'TEST_TOKEN'], 200),
            '*checkout.sandbox.bka.sh/checkout/payment/status*' => Http::response([
                'paymentID' => 'TRX12345',
                'transactionStatus' => 'Completed',
                'completedTime' => now()->toIso8601String(),
            ], 200),
        ]);

        $result = $this->paymentService->verifyPayment($payment);

        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals(Payment::STATUS_COMPLETED, $result->payment_status);
        $this->assertArrayHasKey('verification_response', $result->payment_details);
    }

    #[Test]
    public function it_processes_refunds()
    {
        $this->createBkashGateway();

        Http::fake([
            '*checkout.sandbox.bka.sh/tokenized/checkout/token/grant*' => Http::response(['id_token' => 'TEST_TOKEN'], 200),
            '*checkout.sandbox.bka.sh/tokenized/checkout/payment/refund*' => Http::response([
                'statusCode' => '0000',
                'statusMessage' => 'success',
                'refundTrxID' => 'R123',
            ], 200),
        ]);

        $result = $this->paymentService->processRefund('bkash', 'TRX12345', 1000, 'Test refund');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('transaction_id', $result);
    }

    #[Test]
    public function it_throws_on_failed_gateway_initialization()
    {
        $this->createBkashGateway();

        $payment = Payment::factory()->create([
            'payment_method' => 'bkash',
            'payment_status' => Payment::STATUS_PENDING,
            'invoice_number' => 'INV'.uniqid(),
            'payment_details' => ['description' => 'Test'],
        ]);

        Http::fake([
            '*checkout.sandbox.bka.sh/checkout/token/grant*' => Http::response([], 400),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to initialize bKash payment');

        $this->paymentService->initializePayment($payment, 'bkash');
    }
}
