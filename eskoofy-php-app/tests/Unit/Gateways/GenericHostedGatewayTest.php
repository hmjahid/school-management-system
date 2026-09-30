<?php
declare(strict_types=1);

namespace Tests\Unit\Gateways;

use App\Gateways\GenericHostedGateway;
use Tests\TestCase;

class GenericHostedGatewayTest extends TestCase
{
    private function gateway(array $overrides = []): GenericHostedGateway
    {
        return new GenericHostedGateway(array_merge([
            'name' => 'Test Gateway',
            'test_mode' => false,
            'live_url' => 'https://checkout.example.test/pay',
            'api_key' => 'pk_live_123',
            'api_secret' => 'shhh',
            'currency' => 'USD',
        ], $overrides));
    }

    public function test_initialize_builds_a_hosted_checkout_url(): void
    {
        $result = $this->gateway()->initialize([
            'amount' => 125.5,
            'invoice_id' => 'INV-0001',
            'payment_id' => 7,
            'currency' => 'USD',
        ]);

        $this->assertTrue($result['success']);
        $url = $result['data']['payment_url'];
        $this->assertStringStartsWith('https://checkout.example.test/pay?', $url);
        $this->assertStringContainsString('amount=125.50', $url);
        $this->assertStringContainsString('reference=INV-0001', $url);
    }

    public function test_initialize_uses_the_checkout_url_template_when_set(): void
    {
        $result = $this->gateway([
            'checkout_url_template' => 'https://pay.example.test/checkout/REF-{reference}/AMT-{amount}',
        ])->initialize([
            'amount' => 10,
            'invoice_id' => 'INV-9',
            'payment_id' => 1,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('https://pay.example.test/checkout/REF-INV-9/AMT-10.00', $result['data']['payment_url']);
    }

    public function test_initialize_fails_without_a_checkout_url(): void
    {
        $result = $this->gateway(['live_url' => '', 'sandbox_url' => ''])->initialize([
            'amount' => 10,
            'invoice_id' => 'INV-1',
        ]);

        $this->assertFalse($result['success']);
    }

    public function test_webhook_signature_uses_the_api_secret_when_present(): void
    {
        $gateway = $this->gateway();
        $payload = '{"status":"COMPLETED"}';
        $signature = hash_hmac('sha256', $payload, 'shhh');

        $this->assertTrue($gateway->verifyWebhookSignature($payload, $signature));
        $this->assertFalse($gateway->verifyWebhookSignature($payload, 'nope'));
        $this->assertFalse($gateway->verifyWebhookSignature($payload, ''));
    }

    public function test_webhook_signature_fails_when_unconfigured(): void
    {
        $gateway = $this->gateway(['api_secret' => '', 'api_key' => '']);

        $this->assertFalse($gateway->verifyWebhookSignature('{}', 'anything'));
    }

    public function test_refund_is_unsupported_without_a_refund_url(): void
    {
        $result = $this->gateway()->refund('TXN-1', 10.0, 'test');

        $this->assertFalse($result['success']);
    }
}
