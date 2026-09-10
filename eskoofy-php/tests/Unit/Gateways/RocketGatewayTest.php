<?php
declare(strict_types=1);

namespace Tests\Unit\Gateways;

use App\Gateways\RocketGateway;
use Tests\TestCase;

class RocketGatewayTest extends TestCase
{
    public function test_initialize_fails_with_zero_amount(): void
    {
        $gw = new RocketGateway([
            'api_key' => 'key', 'api_secret' => 'secret',
            'test_mode' => true, 'sandbox_url' => 'https://sandbox.rocket.com', 'live_url' => '',
        ]);
        $result = $gw->initialize(['amount' => 0]);
        $this->assertFalse($result['success']);
    }

    public function test_process_callback_missing_payment_id(): void
    {
        $gw = new RocketGateway([
            'api_key' => 'key', 'api_secret' => 'secret',
            'test_mode' => true, 'sandbox_url' => 'https://sandbox.rocket.com', 'live_url' => '',
        ]);
        $result = $gw->processCallback([]);
        $this->assertFalse($result['success']);
    }

    public function test_process_callback_completed(): void
    {
        $gw = new RocketGateway([
            'api_key' => 'key', 'api_secret' => 'secret',
            'test_mode' => true, 'sandbox_url' => 'https://sandbox.rocket.com', 'live_url' => '',
        ]);
        $result = $gw->processCallback([
            'paymentID' => 'rkt_123',
            'transactionStatus' => 'Completed',
            'amount' => '3000.00',
            'currency' => 'BDT',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('rkt_123', $result['data']['transaction_id']);
    }

    public function test_process_callback_not_completed(): void
    {
        $gw = new RocketGateway([
            'api_key' => 'key', 'api_secret' => 'secret',
            'test_mode' => true, 'sandbox_url' => 'https://sandbox.rocket.com', 'live_url' => '',
        ]);
        $result = $gw->processCallback([
            'paymentID' => 'rkt_123',
            'transactionStatus' => 'Failed',
        ]);
        $this->assertFalse($result['success']);
    }

    public function test_webhook_signature_valid(): void
    {
        $secret = 'rocket_secret';
        $payload = '{"paymentID":"rkt_123"}';
        $signature = hash_hmac('sha256', $payload, $secret);

        $gw = new RocketGateway([
            'api_key' => 'key', 'api_secret' => $secret,
            'test_mode' => true, 'sandbox_url' => 'https://sandbox.rocket.com', 'live_url' => '',
        ]);
        $this->assertTrue($gw->verifyWebhookSignature($payload, $signature));
    }

    public function test_webhook_signature_invalid(): void
    {
        $gw = new RocketGateway([
            'api_key' => 'key', 'api_secret' => 'secret',
            'test_mode' => true, 'sandbox_url' => 'https://sandbox.rocket.com', 'live_url' => '',
        ]);
        $this->assertFalse($gw->verifyWebhookSignature('{}', 'bad'));
    }

    public function test_webhook_signature_empty_secret(): void
    {
        $gw = new RocketGateway([
            'api_key' => 'key', 'api_secret' => '',
            'test_mode' => true, 'sandbox_url' => 'https://sandbox.rocket.com', 'live_url' => '',
        ]);
        $this->assertFalse($gw->verifyWebhookSignature('{}', 'sig'));
    }
}
