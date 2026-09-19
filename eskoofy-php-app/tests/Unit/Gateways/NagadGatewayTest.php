<?php
declare(strict_types=1);

namespace Tests\Unit\Gateways;

use App\Gateways\NagadGateway;
use Tests\TestCase;

class NagadGatewayTest extends TestCase
{
    public function test_initialize_fails_with_zero_amount(): void
    {
        $gw = new NagadGateway([
            'api_key' => 'key', 'api_secret' => 'secret',
            'test_mode' => true, 'sandbox_url' => 'https://sandbox.nagad.com', 'live_url' => '',
        ]);
        $result = $gw->initialize(['amount' => 0]);
        $this->assertFalse($result['success']);
    }

    public function test_initialize_fails_with_negative_amount(): void
    {
        $gw = new NagadGateway([
            'api_key' => 'key', 'api_secret' => 'secret',
            'test_mode' => true, 'sandbox_url' => 'https://sandbox.nagad.com', 'live_url' => '',
        ]);
        $result = $gw->initialize(['amount' => -50]);
        $this->assertFalse($result['success']);
    }

    public function test_process_callback_missing_invoice(): void
    {
        $gw = new NagadGateway([
            'api_key' => 'key', 'api_secret' => 'secret',
            'test_mode' => true, 'sandbox_url' => 'https://sandbox.nagad.com', 'live_url' => '',
        ]);
        $result = $gw->processCallback([]);
        $this->assertFalse($result['success']);
    }

    public function test_process_callback_success(): void
    {
        $gw = new NagadGateway([
            'api_key' => 'key', 'api_secret' => 'secret',
            'test_mode' => true, 'sandbox_url' => 'https://sandbox.nagad.com', 'live_url' => '',
        ]);
        $result = $gw->processCallback([
            'merchantInvoiceNumber' => 'INV-001',
            'transactionStatus' => 'Success',
            'transactionId' => 'ngd_456',
            'amount' => '2000.00',
            'currency' => 'BDT',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('INV-001', $result['data']['invoice_id']);
        $this->assertSame('ngd_456', $result['data']['transaction_id']);
    }

    public function test_process_callback_failed(): void
    {
        $gw = new NagadGateway([
            'api_key' => 'key', 'api_secret' => 'secret',
            'test_mode' => true, 'sandbox_url' => 'https://sandbox.nagad.com', 'live_url' => '',
        ]);
        $result = $gw->processCallback([
            'merchantInvoiceNumber' => 'INV-001',
            'transactionStatus' => 'Failed',
        ]);
        $this->assertFalse($result['success']);
    }

    public function test_webhook_signature_valid(): void
    {
        $secret = 'nagad_secret';
        $payload = '{"merchantInvoiceNumber":"INV-001"}';
        $signature = hash_hmac('sha256', $payload, $secret);

        $gw = new NagadGateway([
            'api_key' => 'key', 'api_secret' => $secret,
            'test_mode' => true, 'sandbox_url' => 'https://sandbox.nagad.com', 'live_url' => '',
        ]);
        $this->assertTrue($gw->verifyWebhookSignature($payload, $signature));
    }

    public function test_webhook_signature_invalid(): void
    {
        $gw = new NagadGateway([
            'api_key' => 'key', 'api_secret' => 'secret',
            'test_mode' => true, 'sandbox_url' => 'https://sandbox.nagad.com', 'live_url' => '',
        ]);
        $this->assertFalse($gw->verifyWebhookSignature('{}', 'bad'));
    }

    public function test_webhook_signature_empty_secret(): void
    {
        $gw = new NagadGateway([
            'api_key' => 'key', 'api_secret' => '',
            'test_mode' => true, 'sandbox_url' => 'https://sandbox.nagad.com', 'live_url' => '',
        ]);
        $this->assertFalse($gw->verifyWebhookSignature('{}', 'sig'));
    }
}
