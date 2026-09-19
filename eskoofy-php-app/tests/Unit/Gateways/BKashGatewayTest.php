<?php
declare(strict_types=1);

namespace Tests\Unit\Gateways;

use App\Gateways\BKashGateway;
use Tests\TestCase;

class BKashGatewayTest extends TestCase
{
    private BKashGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new BKashGateway([
            'api_key' => 'fake_key',
            'api_secret' => 'fake_secret',
            'test_mode' => true,
            'sandbox_url' => 'https://sandbox.bkash.com',
            'live_url' => 'https://api.bkash.com',
        ]);
    }

    public function test_initialize_fails_with_zero_amount(): void
    {
        $result = $this->gateway->initialize(['amount' => 0]);
        $this->assertFalse($result['success']);
    }

    public function test_initialize_fails_with_negative_amount(): void
    {
        $result = $this->gateway->initialize(['amount' => -100]);
        $this->assertFalse($result['success']);
    }

    public function test_process_callback_missing_payment_id(): void
    {
        $result = $this->gateway->processCallback([]);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Missing', $result['message']);
    }

    public function test_process_callback_completed(): void
    {
        $result = $this->gateway->processCallback([
            'paymentID' => 'pay_123',
            'transactionStatus' => 'Completed',
            'amount' => '5000.00',
            'currency' => 'BDT',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('pay_123', $result['data']['transaction_id']);
        $this->assertSame('completed', $result['data']['status']);
    }

    public function test_process_callback_not_completed(): void
    {
        $result = $this->gateway->processCallback([
            'paymentID' => 'pay_123',
            'transactionStatus' => 'Aborted',
        ]);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Aborted', $result['message']);
    }

    public function test_webhook_signature_valid(): void
    {
        $secret = 'bkash_secret';
        $payload = '{"paymentID":"pay_123","transactionStatus":"Completed"}';
        $signature = hash_hmac('sha256', $payload, $secret);

        $gw = new BKashGateway([
            'api_key' => 'key',
            'api_secret' => $secret,
            'test_mode' => true,
            'sandbox_url' => 'https://sandbox.bkash.com',
        ]);

        $this->assertTrue($gw->verifyWebhookSignature($payload, $signature));
    }

    public function test_webhook_signature_invalid(): void
    {
        $gw = new BKashGateway([
            'api_key' => 'key',
            'api_secret' => 'secret',
            'test_mode' => true,
            'sandbox_url' => 'https://sandbox.bkash.com',
        ]);
        $this->assertFalse($gw->verifyWebhookSignature('{}', 'bad_sig'));
    }

    public function test_webhook_signature_empty_secret(): void
    {
        $gw = new BKashGateway([
            'api_key' => 'key',
            'api_secret' => '',
            'test_mode' => true,
            'sandbox_url' => 'https://sandbox.bkash.com',
        ]);
        $this->assertFalse($gw->verifyWebhookSignature('{}', 'sig'));
    }

    public function test_base_url_sandbox_when_test_mode(): void
    {
        $ref = new \ReflectionMethod($this->gateway, 'baseUrl');
        $this->assertSame('https://sandbox.bkash.com', $ref->invoke($this->gateway));
    }

    public function test_base_url_live_when_not_test_mode(): void
    {
        $gw = new BKashGateway([
            'test_mode' => false,
            'sandbox_url' => 'https://sandbox.bkash.com',
            'live_url' => 'https://api.bkash.com',
        ]);
        $ref = new \ReflectionMethod($gw, 'baseUrl');
        $this->assertSame('https://api.bkash.com', $ref->invoke($gw));
    }
}
