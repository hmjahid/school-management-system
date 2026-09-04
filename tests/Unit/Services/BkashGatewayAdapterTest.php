<?php

namespace Tests\Unit\Services;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\Payment\BkashGatewayAdapter;
use App\Services\Payment\GatewayAdapterInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BkashGatewayAdapterTest extends TestCase
{
    use RefreshDatabase;

    private function gateway(): PaymentGateway
    {
        return PaymentGateway::create([
            'name' => 'bKash',
            'code' => 'bkash',
            'type' => PaymentGateway::TYPE_MOBILE_FINANCIAL_SERVICE,
            'is_active' => true,
            'is_online' => true,
            'has_api' => true,
            'test_mode' => true,
            'sandbox_url' => 'https://sandbox.bkash.com',
            'live_url' => 'https://live.bkash.com',
            'api_key' => 'app_key',
            'api_secret' => 'app_secret',
            'api_username' => 'user',
            'api_password' => 'pass',
            'callback_url' => 'https://example.com/callback',
            'currency' => 'BDT',
        ]);
    }

    private function payment(array $details = []): Payment
    {
        return Payment::create(array_merge([
            'invoice_number' => 'INV'.uniqid(),
            'amount' => 1000.00,
            'total_amount' => 1000.00,
            'paid_amount' => 0,
            'due_amount' => 1000.00,
            'payment_method' => 'bkash',
            'payment_status' => Payment::STATUS_PENDING,
            'currency' => 'BDT',
            'paymentable_type' => 'App\Models\Student',
            'paymentable_id' => 1,
            'payment_details' => [],
        ], $details));
    }

    #[Test]
    public function it_implements_gateway_adapter_interface(): void
    {
        $this->assertInstanceOf(GatewayAdapterInterface::class, new BkashGatewayAdapter);
    }

    #[Test]
    public function it_initializes_payment_and_stores_payment_id(): void
    {
        Http::fake([
            '*/checkout/token/grant' => Http::response([
                'id_token' => 'mock_id_token',
                'statusCode' => '0000',
            ], 200),
            '*/checkout/create' => Http::response([
                'paymentID' => 'BKPAY123',
                'bkashURL' => 'https://bkash.com/pay/BKPAY123',
                'createTime' => now()->toIso8601String(),
                'orgLogo' => 'https://bkash.com/logo.png',
            ], 200),
            '*' => Http::response(['statusCode' => '0000'], 200),
        ]);

        $adapter = new BkashGatewayAdapter;
        $gateway = $this->gateway();
        $payment = $this->payment();

        $result = $adapter->initialize($payment, $gateway);

        $this->assertTrue($result['success']);
        $this->assertEquals('bkash', $result['gateway']);
        $this->assertEquals('https://bkash.com/pay/BKPAY123', $result['redirect_url']);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
        ]);
        $this->assertEquals('BKPAY123', $payment->fresh()->payment_details['bkash_payment_id']);
        $this->assertEquals('mock_id_token', $payment->fresh()->payment_details['bkash_token']);
    }

    #[Test]
    public function it_processes_callback_and_completes_payment(): void
    {
        Event::fake();
        Http::fake([
            '*/checkout/execute' => Http::response([
                'transactionStatus' => 'Completed',
                'trxID' => 'TRX987',
            ], 200),
            '*' => Http::response(['statusCode' => '0000'], 200),
        ]);

        $adapter = new BkashGatewayAdapter;
        $gateway = $this->gateway();
        $payment = $this->payment([
            'payment_details' => [
                'bkash_payment_id' => 'BKPAY123',
                'bkash_token' => 'tok',
            ],
        ]);

        $result = $adapter->processCallback([
            'merchantInvoiceNumber' => $payment->invoice_number,
            'paymentID' => 'BKPAY123',
        ], $gateway);

        $this->assertEquals(Payment::STATUS_COMPLETED, $result->fresh()->payment_status);
        $this->assertEquals(0, $result->fresh()->due_amount);
        $this->assertEquals('TRX987', $result->fresh()->payment_details['transaction_id']);
    }

    #[Test]
    public function it_verifies_payment_status(): void
    {
        Http::fake([
            '*/checkout/token/grant' => Http::response([
                'id_token' => 'mock_id_token',
                'statusCode' => '0000',
            ], 200),
            '*/checkout/payment/status' => Http::response([
                'transactionStatus' => 'Completed',
                'completedTime' => now()->toIso8601String(),
            ], 200),
            '*' => Http::response(['statusCode' => '0000'], 200),
        ]);

        $adapter = new BkashGatewayAdapter;
        $gateway = $this->gateway();
        $payment = $this->payment([
            'payment_details' => ['bkash_payment_id' => 'BKPAY123'],
        ]);

        $result = $adapter->verifyPayment($payment, $gateway);

        $this->assertEquals(Payment::STATUS_COMPLETED, $result->fresh()->payment_status);
    }

    #[Test]
    public function it_refunds_successfully(): void
    {
        Http::fake([
            '*/tokenized/checkout/token/grant' => Http::response([
                'id_token' => 'mock_id_token',
                'statusCode' => '0000',
            ], 200),
            '*/tokenized/checkout/payment/refund' => Http::response([
                'statusCode' => '0000',
                'statusMessage' => 'Success',
                'refundTrxID' => 'RREF123',
            ], 200),
            '*' => Http::response(['statusCode' => '0000'], 200),
        ]);

        $adapter = new BkashGatewayAdapter;
        $gateway = $this->gateway();

        $result = $adapter->refund($gateway, 'BKPAY123', 500.00, 'Customer request');

        $this->assertTrue($result['success']);
        $this->assertEquals('RREF123', $result['transaction_id']);
    }

    #[Test]
    public function it_accepts_a_valid_webhook_signature(): void
    {
        $adapter = new BkashGatewayAdapter;
        $gateway = $this->gateway();

        $body = 'paymentID=BKPAY123&merchantInvoiceNumber=INV1';
        $signature = hash_hmac('sha256', $body, $gateway->api_secret);

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_BKASH_SIGNATURE' => $signature,
        ], $body);

        $this->assertTrue($adapter->verifyWebhookSignature($request, $gateway));
    }

    #[Test]
    public function it_rejects_a_webhook_with_missing_signature(): void
    {
        $adapter = new BkashGatewayAdapter;
        $gateway = $this->gateway();

        $request = Request::create('/webhook', 'POST', [], [], [], [], 'paymentID=BKPAY123');

        $this->assertFalse($adapter->verifyWebhookSignature($request, $gateway));
    }

    #[Test]
    public function it_rejects_a_webhook_with_invalid_signature(): void
    {
        $adapter = new BkashGatewayAdapter;
        $gateway = $this->gateway();

        $body = 'paymentID=BKPAY123';
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_BKASH_SIGNATURE' => 'tampered',
        ], $body);

        $this->assertFalse($adapter->verifyWebhookSignature($request, $gateway));
    }
}
