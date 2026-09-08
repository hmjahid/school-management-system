<?php

namespace Tests\Unit\Services;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\Payment\GatewayAdapterInterface;
use App\Services\Payment\RocketGatewayAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RocketGatewayAdapterTest extends TestCase
{
    use RefreshDatabase;

    private function gateway(): PaymentGateway
    {
        return PaymentGateway::create([
            'name' => 'Rocket',
            'code' => 'rocket',
            'type' => PaymentGateway::TYPE_MOBILE_FINANCIAL_SERVICE,
            'is_active' => true,
            'is_online' => true,
            'has_api' => true,
            'test_mode' => true,
            'sandbox_url' => 'https://sandbox.rocket.com',
            'live_url' => 'https://live.rocket.com',
            'api_key' => 'app_key',
            'api_secret' => 'app_secret',
            'api_username' => 'user',
            'api_password' => 'pass',
            'success_url' => 'https://example.com/success',
            'currency' => 'BDT',
        ]);
    }

    private function payment(array $details = []): Payment
    {
        return Payment::create(array_merge([
            'invoice_number' => 'INV'.uniqid(),
            'amount' => 2000.00,
            'total_amount' => 2000.00,
            'paid_amount' => 0,
            'due_amount' => 2000.00,
            'payment_method' => 'rocket',
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
        $this->assertInstanceOf(GatewayAdapterInterface::class, new RocketGatewayAdapter);
    }

    #[Test]
    public function it_initializes_payment_without_http(): void
    {
        $adapter = new RocketGatewayAdapter;
        $gateway = $this->gateway();
        $payment = $this->payment();

        $result = $adapter->initialize($payment, $gateway);

        $this->assertTrue($result['success']);
        $this->assertEquals('rocket', $result['gateway']);
        $this->assertStringContainsString('payment_id=', $result['redirect_url']);
        $this->assertNotEmpty($payment->fresh()->payment_details['rocket_transaction_id']);
    }

    #[Test]
    public function process_callback_throws_exception(): void
    {
        $this->expectException(\Exception::class);

        $adapter = new RocketGatewayAdapter;
        $gateway = $this->gateway();
        $payment = $this->payment();

        $adapter->processCallback(['foo' => 'bar'], $gateway);
    }

    #[Test]
    public function it_verifies_payment_and_marks_completed(): void
    {
        Event::fake();

        $adapter = new RocketGatewayAdapter;
        $gateway = $this->gateway();
        $payment = $this->payment();

        $result = $adapter->verifyPayment($payment, $gateway);

        $this->assertEquals(Payment::STATUS_COMPLETED, $result->fresh()->payment_status);
        $this->assertEquals(0, $result->fresh()->due_amount);
    }

    #[Test]
    public function it_refunds_successfully(): void
    {
        Http::fake([
            '*/token' => Http::response([
                'access_token' => 'rocket_token',
                'status' => 'success',
            ], 200),
            '*/refund' => Http::response([
                'status' => 'Completed',
                'refund_id' => 'RKTREF123',
            ], 200),
            '*' => Http::response(['status' => 'success'], 200),
        ]);

        $adapter = new RocketGatewayAdapter;
        $gateway = $this->gateway();

        $result = $adapter->refund($gateway, 'RKTTXN123', 500.00, 'Customer request');

        $this->assertTrue($result['success']);
        $this->assertEquals('RKTREF123', $result['transaction_id']);
    }

    #[Test]
    public function it_accepts_a_valid_webhook_signature(): void
    {
        $adapter = new RocketGatewayAdapter;
        $gateway = $this->gateway();

        $body = json_encode(['paymentID' => 'RKTPAY123']);
        $signature = hash_hmac('sha256', $body, $gateway->api_secret);

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_ROCKET_SIGNATURE' => $signature,
        ], $body);

        $this->assertTrue($adapter->verifyWebhookSignature($request, $gateway));
    }

    #[Test]
    public function it_rejects_a_webhook_with_missing_signature(): void
    {
        $adapter = new RocketGatewayAdapter;
        $gateway = $this->gateway();

        $request = Request::create('/webhook', 'POST', [], [], [], [], 'paymentID=RKTPAY123');

        $this->assertFalse($adapter->verifyWebhookSignature($request, $gateway));
    }

    #[Test]
    public function it_rejects_a_webhook_with_invalid_signature(): void
    {
        $adapter = new RocketGatewayAdapter;
        $gateway = $this->gateway();

        $body = json_encode(['paymentID' => 'RKTPAY123']);
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_ROCKET_SIGNATURE' => 'tampered',
        ], $body);

        $this->assertFalse($adapter->verifyWebhookSignature($request, $gateway));
    }
}
