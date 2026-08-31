<?php

namespace Tests\Unit\Services;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\Payment\GatewayAdapterInterface;
use App\Services\Payment\NagadGatewayAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NagadGatewayAdapterTest extends TestCase
{
    use RefreshDatabase;

    private function gateway(): PaymentGateway
    {
        return PaymentGateway::create([
            'name' => 'Nagad',
            'code' => 'nagad',
            'type' => PaymentGateway::TYPE_MOBILE_FINANCIAL_SERVICE,
            'is_active' => true,
            'is_online' => true,
            'has_api' => true,
            'test_mode' => true,
            'sandbox_url' => 'https://sandbox.nagad.com',
            'live_url' => 'https://live.nagad.com',
            'api_key' => 'app_key',
            'api_secret' => 'app_secret',
            'callback_url' => 'https://example.com/callback',
            'currency' => 'BDT',
            'extra_attributes' => ['merchant_account' => '0123456789'],
        ]);
    }

    private function payment(array $details = []): Payment
    {
        return Payment::create(array_merge([
            'invoice_number' => 'INV'.uniqid(),
            'amount' => 1500.00,
            'total_amount' => 1500.00,
            'paid_amount' => 0,
            'due_amount' => 1500.00,
            'payment_method' => 'nagad',
            'payment_status' => Payment::STATUS_PENDING,
            'currency' => 'BDT',
            'paymentable_type' => 'App\Models\Student',
            'paymentable_id' => 1,
            'payment_details' => [],
        ], $details));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $request = new Request;
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $this->app->instance('request', $request);
    }

    /** @test */
    public function it_implements_gateway_adapter_interface(): void
    {
        $this->assertInstanceOf(GatewayAdapterInterface::class, new NagadGatewayAdapter);
    }

    /** @test */
    public function it_initializes_payment_and_returns_redirect_url(): void
    {
        Http::fake([
            '*/checkout/initialize/*' => Http::response([
                'paymentReferenceId' => 'NAGREF123',
                'orderId' => 'NAGORD123',
                'callBackUrl' => 'https://nagad.com/callback',
                'challenge' => 'challenge-token',
            ], 200),
            '*' => Http::response(['status' => 'Success'], 200),
        ]);

        $adapter = new NagadGatewayAdapter;
        $gateway = $this->gateway();
        $payment = $this->payment();

        $result = $adapter->initialize($payment, $gateway);

        $this->assertTrue($result['success']);
        $this->assertEquals('nagad', $result['gateway']);
        $this->assertEquals(
            'https://nagad.com/callback?paymentRefId=NAGREF123',
            $result['redirect_url']
        );
        $this->assertEquals('NAGREF123', $payment->fresh()->payment_details['nagad_payment_id']);
    }

    /** @test */
    public function it_processes_callback_and_completes_payment(): void
    {
        Event::fake();
        Http::fake([
            '*/verify/payment/*' => Http::response([
                'status' => 'Success',
                'paymentId' => 'NAGTXN123',
            ], 200),
            '*' => Http::response(['status' => 'Success'], 200),
        ]);

        $adapter = new NagadGatewayAdapter;
        $gateway = $this->gateway();
        $payment = $this->payment([
            'payment_details' => [
                'nagad_payment_id' => 'NAGREF123',
                'nagad_order_id' => 'NAGORD123',
            ],
        ]);

        $result = $adapter->processCallback([
            'orderId' => 'NAGORD123',
            'paymentRefId' => 'NAGREF123',
        ], $gateway);

        $this->assertEquals(Payment::STATUS_COMPLETED, $result->fresh()->payment_status);
        $this->assertEquals('NAGTXN123', $result->fresh()->payment_details['transaction_id']);
    }

    /** @test */
    public function verify_payment_returns_payment_unchanged(): void
    {
        $adapter = new NagadGatewayAdapter;
        $gateway = $this->gateway();
        $payment = $this->payment();

        $result = $adapter->verifyPayment($payment, $gateway);

        $this->assertSame($payment->id, $result->id);
        $this->assertEquals(Payment::STATUS_PENDING, $result->fresh()->payment_status);
    }

    /** @test */
    public function it_refunds_successfully(): void
    {
        Http::fake([
            '*/dfs/refund/initialize' => Http::response([
                'status' => 'Success',
                'refundTrxID' => 'NAGREFUND123',
            ], 200),
            '*' => Http::response(['status' => 'Success'], 200),
        ]);

        $adapter = new NagadGatewayAdapter;
        $gateway = $this->gateway();

        $result = $adapter->refund($gateway, 'NAGREF123', 500.00, 'Customer request');

        $this->assertTrue($result['success']);
        $this->assertEquals('NAGREFUND123', $result['transaction_id']);
    }

    /** @test */
    public function it_accepts_a_valid_webhook_signature(): void
    {
        $adapter = new NagadGatewayAdapter;
        $gateway = $this->gateway();

        $body = json_encode(['orderId' => 'NAGORD123', 'paymentRefId' => 'NAGREF123']);
        $signature = hash_hmac('sha256', $body, $gateway->api_secret);

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_NAGAD_SIGNATURE' => $signature,
        ], $body);

        $this->assertTrue($adapter->verifyWebhookSignature($request, $gateway));
    }

    /** @test */
    public function it_rejects_a_webhook_with_missing_signature(): void
    {
        $adapter = new NagadGatewayAdapter;
        $gateway = $this->gateway();

        $request = Request::create('/webhook', 'POST', [], [], [], [], 'orderId=NAGORD123');

        $this->assertFalse($adapter->verifyWebhookSignature($request, $gateway));
    }

    /** @test */
    public function it_rejects_a_webhook_with_invalid_signature(): void
    {
        $adapter = new NagadGatewayAdapter;
        $gateway = $this->gateway();

        $body = json_encode(['orderId' => 'NAGORD123']);
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_NAGAD_SIGNATURE' => 'tampered',
        ], $body);

        $this->assertFalse($adapter->verifyWebhookSignature($request, $gateway));
    }
}
