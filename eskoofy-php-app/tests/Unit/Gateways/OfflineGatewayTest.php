<?php
declare(strict_types=1);

namespace Tests\Unit\Gateways;

use App\Gateways\OfflineGateway;
use Tests\TestCase;

class OfflineGatewayTest extends TestCase
{
    private OfflineGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new OfflineGateway([
            'bank_name' => 'Test Bank',
            'account_name' => 'Test Account',
            'account_number' => '1234567890',
            'routing_number' => '000123456',
            'instructions' => 'Transfer to account above.',
        ]);
    }

    public function test_initialize_returns_bank_details(): void
    {
        $result = $this->gateway->initialize([
            'amount' => 5000,
            'invoice_id' => 'INV-001',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('Test Bank', $result['data']['bank_name']);
        $this->assertSame('Test Account', $result['data']['account_name']);
        $this->assertSame('1234567890', $result['data']['account_number']);
        $this->assertSame(5000, $result['data']['amount']);
        $this->assertSame('INV-001', $result['data']['invoice_id']);
        $this->assertSame('pending_verification', $result['data']['status']);
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

    public function test_verify_payment_always_false(): void
    {
        $this->assertFalse($this->gateway->verifyPayment('TXN-001'));
    }

    public function test_process_callback_returns_pending(): void
    {
        $result = $this->gateway->processCallback(['payment_id' => 'TXN-001']);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('manual', $result['message']);
    }

    public function test_refund_returns_not_supported(): void
    {
        $result = $this->gateway->refund('TXN-001', 100, 'changed mind');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('manually', $result['message']);
        $this->assertSame(100.0, $result['data']['amount']);
    }

    public function test_webhook_signature_always_false(): void
    {
        $this->assertFalse($this->gateway->verifyWebhookSignature('payload', 'sig'));
    }
}
