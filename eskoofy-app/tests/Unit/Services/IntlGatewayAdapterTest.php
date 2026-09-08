<?php

namespace Tests\Unit\Services;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\Payment\GatewayAdapterInterface;
use App\Services\Payment\PaddleGatewayAdapter;
use App\Services\Payment\PaypalGatewayAdapter;
use App\Services\Payment\StripeGatewayAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IntlGatewayAdapterTest extends TestCase
{
    use RefreshDatabase;

    protected function makeGateway(string $code, string $currency = 'USD', array $extra = []): PaymentGateway
    {
        return PaymentGateway::create(array_merge([
            'name' => $code,
            'code' => $code,
            'type' => PaymentGateway::TYPE_ONLINE_PAYMENT,
            'is_active' => true,
            'is_online' => true,
            'has_api' => true,
            'test_mode' => true,
            'sandbox_url' => 'https://api.sandbox.example.test/v1',
            'live_url' => 'https://api.example.test/v1',
            'api_key' => 'test-key-'.$code,
            'api_secret' => 'test-secret-'.$code,
            'api_username' => 'test-user-'.$code,
            'callback_url' => 'https://example.com/callback',
            'currency' => $currency,
        ], $extra));
    }

    protected function payment(string $gateway, array $details = []): Payment
    {
        return Payment::create(array_merge([
            'invoice_number' => 'INV'.uniqid(),
            'amount' => 100.00,
            'total_amount' => 100.00,
            'paid_amount' => 0,
            'due_amount' => 100.00,
            'payment_method' => $gateway,
            'payment_status' => Payment::STATUS_PENDING,
            'currency' => 'USD',
            'paymentable_type' => 'App\Models\Student',
            'paymentable_id' => 1,
            'payment_details' => [],
        ], $details));
    }

    #[Test]
    public function stripe_implements_gateway_adapter_interface(): void
    {
        $this->assertInstanceOf(GatewayAdapterInterface::class, new StripeGatewayAdapter);
    }

    #[Test]
    public function paypal_implements_gateway_adapter_interface(): void
    {
        $this->assertInstanceOf(GatewayAdapterInterface::class, new PaypalGatewayAdapter);
    }

    #[Test]
    public function paddle_implements_gateway_adapter_interface(): void
    {
        $this->assertInstanceOf(GatewayAdapterInterface::class, new PaddleGatewayAdapter);
    }

    #[Test]
    public function stripe_initializes_a_payment_intent(): void
    {
        Http::fake([
            '*/payment_intents' => Http::response([
                'id' => 'pi_mock123',
                'client_secret' => 'pi_mock123_secret_abc',
                'status' => 'requires_confirmation',
            ], 200),
            '*' => Http::response(['error' => ['message' => 'unexpected']], 400),
        ]);

        $adapter = new StripeGatewayAdapter;
        $payment = $this->payment('stripe');

        $result = $adapter->initialize($payment, $this->makeGateway('stripe'));

        $this->assertTrue($result['success']);
        $this->assertSame('pi_mock123_secret_abc', $result['payment_details']['client_secret']);
        $this->assertSame('pi_mock123', $payment->fresh()->payment_details['stripe_payment_intent']);
    }

    #[Test]
    public function stripe_completes_payment_from_webhook_callback(): void
    {
        Event::fake();
        $adapter = new StripeGatewayAdapter;
        $gateway = $this->makeGateway('stripe');
        $payment = $this->payment('stripe', [
            'payment_details' => ['stripe_payment_intent' => 'pi_mock123'],
        ]);

        $result = $adapter->processCallback([
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_mock123', 'status' => 'succeeded']],
        ], $gateway);

        $this->assertSame(Payment::STATUS_COMPLETED, $result->fresh()->payment_status);
        $this->assertSame(0, (int) $result->fresh()->due_amount);
    }

    #[Test]
    public function stripe_refund_returns_transaction_id(): void
    {
        Http::fake([
            '*/refunds' => Http::response([
                'id' => 're_junk12345',
                'status' => 'succeeded',
            ], 200),
        ]);

        $adapter = new StripeGatewayAdapter;
        $gateway = $this->makeGateway('stripe');

        $result = $adapter->refund($gateway, 'pi_mock123', 50.00, 'Test refund');

        $this->assertTrue($result['success']);
        $this->assertSame('re_junk12345', $result['transaction_id']);
    }

    #[Test]
    public function stripe_accepts_a_valid_webhook_signature(): void
    {
        $adapter = new StripeGatewayAdapter;
        $gateway = $this->makeGateway('stripe');

        $body = '{"type":"payment_intent.succeeded"}';
        $hmac = hash_hmac('sha256', '1612111111.'.$body, $gateway->api_secret);
        $header = 't=1612111111,v1='.$hmac;

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $header,
        ], $body);

        $this->assertTrue($adapter->verifyWebhookSignature($request, $gateway));
    }

    #[Test]
    public function stripe_rejects_a_tampered_webhook_signature(): void
    {
        $adapter = new StripeGatewayAdapter;
        $gateway = $this->makeGateway('stripe');

        $body = '{"type":"payment_intent.succeeded"}';
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't=1612111111,v1=tampered',
        ], $body);

        $this->assertFalse($adapter->verifyWebhookSignature($request, $gateway));
    }

    #[Test]
    public function paypal_initializes_an_order_and_returns_approval_url(): void
    {
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'AKIA-token'], 200),
            '*/v2/checkout/orders' => Http::response([
                'id' => 'PAYPAL_ORDER_1',
                'status' => 'CREATED',
                'links' => [
                    ['rel' => 'approve', 'href' => 'https://paypal.com/checkout/APPROVE_1'],
                    ['rel' => 'self', 'href' => 'https://paypal.com/order/1'],
                ],
            ], 200),
        ]);

        $adapter = new PaypalGatewayAdapter;
        $payment = $this->payment('paypal');

        $result = $adapter->initialize($payment, $this->makeGateway('paypal'));

        $this->assertTrue($result['success']);
        $this->assertSame('https://paypal.com/checkout/APPROVE_1', $result['redirect_url']);
        $this->assertSame('PAYPAL_ORDER_1', $payment->fresh()->payment_details['paypal_order_id']);
    }

    #[Test]
    public function paypal_completes_payment_from_capture_webhook(): void
    {
        Event::fake();
        $adapter = new PaypalGatewayAdapter;
        $gateway = $this->makeGateway('paypal');
        $payment = $this->payment('paypal', [
            'payment_details' => ['paypal_order_id' => 'PAYPAL_ORDER_1'],
        ]);

        $result = $adapter->processCallback([
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => ['order_id' => 'PAYPAL_ORDER_1', 'id' => 'CAPTURE_9', 'status' => 'COMPLETED'],
        ], $gateway);

        $this->assertSame(Payment::STATUS_COMPLETED, $result->fresh()->payment_status);
    }

    #[Test]
    public function paypal_uses_refund_api(): void
    {
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'AKIA-token'], 200),
            '*/v2/payments/captures/CAPTURE_9/refund' => Http::response([
                'id' => 'REFUND_77',
                'status' => 'COMPLETED',
            ], 200),
        ]);

        $adapter = new PaypalGatewayAdapter;
        $gateway = $this->makeGateway('paypal');

        $result = $adapter->refund($gateway, 'CAPTURE_9', 50.00, 'Test refund', ['paypal_capture_id' => 'CAPTURE_9']);

        $this->assertTrue($result['success']);
        $this->assertSame('REFUND_77', $result['transaction_id']);
    }

    #[Test]
    public function paypal_accepts_a_valid_webhook_signature(): void
    {
        $adapter = new PaypalGatewayAdapter;
        $gateway = $this->makeGateway('paypal');

        $body = '{"event_type":"PAYMENT.CAPTURE.COMPLETED"}';
        $signedPayload = 'WEBHOOK_1|2026-01-01T00:00:00Z|'.$body;
        $hmac = hash_hmac('sha256', $signedPayload, $gateway->api_secret);

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_PAYPAL_TRANSMISSION_SIG' => $hmac,
            'HTTP_PAYPAL_WEBHOOK_ID' => 'WEBHOOK_1',
            'HTTP_PAYPAL_TRANSMISSION_TIME' => '2026-01-01T00:00:00Z',
        ], $body);

        $this->assertTrue($adapter->verifyWebhookSignature($request, $gateway));
    }

    #[Test]
    public function paypal_rejects_a_missing_webhook_signature(): void
    {
        $adapter = new PaypalGatewayAdapter;
        $gateway = $this->makeGateway('paypal');

        $request = Request::create('/webhook', 'POST', [], [], [], [], '{}');

        $this->assertFalse($adapter->verifyWebhookSignature($request, $gateway));
    }

    #[Test]
    public function paddle_initializes_a_checkout_url(): void
    {
        $adapter = new PaddleGatewayAdapter;
        $gateway = $this->makeGateway('paddle', 'USD', [
            'sandbox_url' => 'https://sandbox-checkout.paddle.test/checkout/?',
        ]);
        $payment = $this->payment('paddle');

        $result = $adapter->initialize($payment, $gateway);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('sandbox-checkout.paddle.test', $result['redirect_url']);
        $this->assertStringContainsString('product_id=', $result['redirect_url']);
        $this->assertStringContainsString('payment_id', $result['redirect_url']);
    }

    #[Test]
    public function paddle_completes_payment_from_payment_succeeded_alert(): void
    {
        Event::fake();
        $adapter = new PaddleGatewayAdapter;
        $gateway = $this->makeGateway('paddle');
        $payment = $this->payment('paddle');

        $result = $adapter->processCallback([
            'alert_name' => 'payment_succeeded',
            'checkout_id' => 'CHECKOUT_42',
            'passthrough' => json_encode(['payment_id' => $payment->id, 'invoice_number' => $payment->invoice_number]),
        ], $gateway);

        $this->assertSame(Payment::STATUS_COMPLETED, $result->fresh()->payment_status);
        $this->assertSame('CHECKOUT_42', $result->fresh()->payment_details['paddle_checkout_id']);
    }

    #[Test]
    public function paddle_marks_payment_refunded_from_alert(): void
    {
        $adapter = new PaddleGatewayAdapter;
        $gateway = $this->makeGateway('paddle');
        $payment = $this->payment('paddle');

        $result = $adapter->processCallback([
            'alert_name' => 'refund_issued',
            'refund_reason' => 'Test refund',
            'passthrough' => json_encode(['payment_id' => $payment->id]),
        ], $gateway);

        $this->assertSame(Payment::STATUS_REFUNDED, $result->fresh()->payment_status);
    }

    #[Test]
    public function paddle_refund_is_explicitly_offline(): void
    {
        $adapter = new PaddleGatewayAdapter;
        $gateway = $this->makeGateway('paddle');

        $result = $adapter->refund($gateway, 'CHECKOUT_42', 50.00, 'Test refund');

        $this->assertFalse($result['success']);
        $this->assertTrue($result['offline']);
    }

    #[Test]
    public function paddle_accepts_a_valid_webhook_signature(): void
    {
        $adapter = new PaddleGatewayAdapter;
        $gateway = $this->makeGateway('paddle', 'USD', [
            'extra_attributes' => ['paddle_public_key' => "-----BEGIN PUBLIC KEY-----\nabc\n-----END PUBLIC KEY-----"],
        ]);

        $fields = ['alert_name' => 'payment_succeeded', 'checkout_id' => 'CHECKOUT_42'];
        $publicKey = '-----BEGIN PUBLIC KEY-----abc-----END PUBLIC KEY-----';

        $expected = '';
        ksort($fields);
        foreach ($fields as $key => $value) {
            $expected .= "{$key}={$value}";
        }
        $expected .= $publicKey;

        $request = Request::create('/webhook', 'POST', $fields + ['p_signature' => md5($expected)]);

        $this->assertTrue($adapter->verifyWebhookSignature($request, $gateway));
    }

    #[Test]
    public function paddle_rejects_a_bad_webhook_signature(): void
    {
        $adapter = new PaddleGatewayAdapter;
        $gateway = $this->makeGateway('paddle', 'USD', [
            'extra_attributes' => ['paddle_public_key' => 'PUBKEY_123'],
        ]);

        $request = Request::create('/webhook', 'POST', [
            'alert_name' => 'payment_succeeded',
            'p_signature' => 'tampered',
        ]);

        $this->assertFalse($adapter->verifyWebhookSignature($request, $gateway));
    }
}
