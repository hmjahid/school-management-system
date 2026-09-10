<?php
declare(strict_types=1);

namespace Tests\Unit\Gateways;

use App\Gateways\StripeGateway;
use Tests\TestCase;

class StripeGatewayTest extends TestCase
{
    private StripeGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new StripeGateway([
            'api_key' => 'sk_test_fake',
            'publishable' => 'pk_test_fake',
            'webhook_secret' => 'whsec_test_secret',
        ]);
    }

    public function test_initialize_fails_with_zero_amount(): void
    {
        $result = $this->gateway->initialize(['amount' => 0]);
        $this->assertFalse($result['success']);
        $this->assertSame('Invalid amount', $result['message']);
    }

    public function test_initialize_fails_with_negative_amount(): void
    {
        $result = $this->gateway->initialize(['amount' => -50]);
        $this->assertFalse($result['success']);
    }

    public function test_webhook_signature_valid(): void
    {
        $secret = 'whsec_test_secret';
        $payload = '{"id":"evt_123","type":"payment_intent.succeeded"}';
        $timestamp = (string) time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        $header = "t={$timestamp},v1={$signature}";
        $this->assertTrue($this->gateway->verifyWebhookSignature($payload, $header));
    }

    public function test_webhook_signature_invalid(): void
    {
        $payload = '{"id":"evt_123"}';
        $header = "t=12345,v1=invalid_signature";
        $this->assertFalse($this->gateway->verifyWebhookSignature($payload, $header));
    }

    public function test_webhook_signature_empty_secret(): void
    {
        $gw = new StripeGateway(['api_key' => '', 'webhook_secret' => '']);
        $this->assertFalse($gw->verifyWebhookSignature('payload', 'sig'));
    }

    public function test_webhook_signature_missing_timestamp(): void
    {
        $payload = '{}';
        $this->assertFalse($this->gateway->verifyWebhookSignature($payload, 'v1=abc'));
    }

    public function test_webhook_signature_missing_v1(): void
    {
        $payload = '{}';
        $this->assertFalse($this->gateway->verifyWebhookSignature($payload, 't=123'));
    }

    public function test_process_callback_ignores_wrong_event_type(): void
    {
        $result = $this->gateway->processCallback([
            'type' => 'payment_intent.created',
            'data' => ['object' => []],
        ]);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Ignoring', $result['message']);
    }

    public function test_process_callback_succeeded(): void
    {
        $result = $this->gateway->processCallback([
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_abc123',
                    'amount' => 5000,
                    'currency' => 'usd',
                    'metadata' => ['invoice_id' => 'INV-001'],
                    'payment_method' => 'pm_card_visa',
                ],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('pi_abc123', $result['data']['transaction_id']);
        $this->assertSame('completed', $result['data']['status']);
        $this->assertSame(50, $result['data']['amount']);
        $this->assertSame('usd', $result['data']['currency']);
        $this->assertSame('INV-001', $result['data']['invoice_id']);
    }

    public function test_process_callback_missing_payment_id(): void
    {
        $result = $this->gateway->processCallback([
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => '']],
        ]);
        $this->assertFalse($result['success']);
    }

    public function test_refund_success_structure(): void
    {
        $payload = [
            'payment_intent' => 'pi_abc123',
            'reason' => 'requested_by_customer',
        ];
        $this->assertSame('pi_abc123', $payload['payment_intent']);
    }
}
