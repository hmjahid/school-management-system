<?php

namespace Tests\Unit\Services;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\Payment\GenericHostedGatewayAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenericHostedGatewayAdapterTest extends TestCase
{
    use RefreshDatabase;

    private function gateway(array $attributes = []): PaymentGateway
    {
        return PaymentGateway::create(array_merge([
            'name' => 'Google Pay',
            'code' => 'gpay',
            'type' => PaymentGateway::TYPE_ONLINE_PAYMENT,
            'is_active' => true,
            'is_online' => true,
            'has_api' => true,
            'test_mode' => true,
            'sandbox_url' => 'https://sandbox.example.test/pay',
            'live_url' => 'https://pay.example.test/pay',
            'api_key' => 'pk_test_123',
            'api_secret' => 'shhh',
            'currency' => 'USD',
        ], $attributes));
    }

    private function payment(): Payment
    {
        return Payment::create([
            'paymentable_type' => Payment::PURPOSE_TUITION,
            'paymentable_id' => 1,
            'invoice_number' => 'INV-000123',
            'amount' => 25.5,
            'paid_amount' => 0,
            'due_amount' => 25.5,
            'total_amount' => 25.5,
            'payment_method' => 'gpay',
            'payment_status' => Payment::STATUS_PENDING,
        ]);
    }

    #[Test]
    public function it_builds_a_hosted_checkout_redirect_from_the_gateway_row(): void
    {
        $adapter = new GenericHostedGatewayAdapter;

        $result = $adapter->initialize($this->payment(), $this->gateway());

        $this->assertTrue($result['success']);
        $this->assertSame('gpay', $result['gateway']);
        $this->assertStringStartsWith('https://sandbox.example.test/pay?', $result['redirect_url']);
        $this->assertStringContainsString('amount=25.50', $result['redirect_url']);
        $this->assertStringContainsString('currency=USD', $result['redirect_url']);
        $this->assertStringContainsString('reference=INV-000123', $result['redirect_url']);
    }

    #[Test]
    public function it_honours_the_checkout_url_template(): void
    {
        $adapter = new GenericHostedGatewayAdapter;
        $gateway = $this->gateway([
            'extra_attributes' => [
                'checkout_url_template' => 'https://pay.example.test/checkout/REF-{reference}/AMT-{amount}',
            ],
        ]);

        $result = $adapter->initialize($this->payment(), $gateway);

        $this->assertSame(
            'https://pay.example.test/checkout/REF-INV-000123/AMT-25.50',
            $result['redirect_url']
        );
    }

    #[Test]
    public function it_refuses_to_initialize_without_a_checkout_endpoint(): void
    {
        $adapter = new GenericHostedGatewayAdapter;
        $gateway = $this->gateway([
            'sandbox_url' => null,
            'live_url' => null,
            'extra_attributes' => [],
        ]);

        $this->expectException(\Exception::class);

        $adapter->initialize($this->payment(), $gateway);
    }

    #[Test]
    public function its_webhook_signature_is_fail_closed_hmac(): void
    {
        $adapter = new GenericHostedGatewayAdapter;
        $gateway = $this->gateway();
        $body = '{"status":"COMPLETED"}';
        $signature = hash_hmac('sha256', $body, 'shhh');

        $this->assertTrue($adapter->verifyWebhookSignature($this->signedRequest($body, $signature), $gateway));
        $this->assertFalse($adapter->verifyWebhookSignature($this->signedRequest($body, 'wrong'), $gateway));
        $this->assertFalse($adapter->verifyWebhookSignature($this->signedRequest($body, null), $gateway));
    }

    #[Test]
    public function it_reports_refunds_unsupported_without_a_refund_url(): void
    {
        $adapter = new GenericHostedGatewayAdapter;

        $result = $adapter->refund($this->gateway(), 'TXN-1', 10.0, 'test');

        $this->assertFalse($result['success']);
    }

    private function signedRequest(string $body, ?string $signature): Request
    {
        $request = Request::create('/webhook', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);

        if ($signature !== null) {
            $request->headers->set('X-Webhook-Signature', $signature);
        }

        return $request;
    }
}
