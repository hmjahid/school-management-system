<?php
declare(strict_types=1);

namespace Tests\Unit\Gateways;

use App\Gateways\PaddleGateway;
use Tests\TestCase;

class PaddleGatewayTest extends TestCase
{
    public function test_initialize_fails_with_zero_amount(): void
    {
        $gw = new PaddleGateway(['vendor_id' => '1', 'vendor_auth_code' => 'fake']);
        $result = $gw->initialize(['amount' => 0]);
        $this->assertFalse($result['success']);
    }

    public function test_process_callback_ignores_unknown_event(): void
    {
        $gw = new PaddleGateway(['vendor_id' => '1', 'vendor_auth_code' => 'fake']);
        $result = $gw->processCallback(['event' => 'transaction.updated', 'data' => []]);
        $this->assertFalse($result['success']);
    }

    public function test_process_callback_transaction_completed(): void
    {
        $gw = new PaddleGateway(['vendor_id' => '1', 'vendor_auth_code' => 'fake']);
        $result = $gw->processCallback([
            'event' => 'transaction.completed',
            'data' => [
                'id' => 'txn_123',
                'invoice_id' => 'INV-001',
                'currency_code' => 'USD',
                'totals' => ['total' => 5000],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('txn_123', $result['data']['transaction_id']);
        $this->assertSame('INV-001', $result['data']['invoice_id']);
        $this->assertSame(50, $result['data']['amount']);
    }

    public function test_process_callback_checkout_completed(): void
    {
        $gw = new PaddleGateway(['vendor_id' => '1', 'vendor_auth_code' => 'fake']);
        $result = $gw->processCallback([
            'event' => 'checkout.completed',
            'data' => [
                'id' => 'chk_789',
                'passthrough' => json_encode(['invoice_id' => 'INV-002']),
                'currency_code' => 'USD',
                'totals' => ['total' => 10000],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('chk_789', $result['data']['transaction_id']);
        $this->assertSame('INV-002', $result['data']['invoice_id']);
    }

    public function test_refund_not_supported(): void
    {
        $gw = new PaddleGateway(['vendor_id' => '1', 'vendor_auth_code' => 'fake']);
        $result = $gw->refund('txn_123', 50, 'test');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('manually', $result['message']);
    }

    public function test_webhook_signature_valid(): void
    {
        $secret = 'my_secret_key';
        $payload = '{"event":"transaction.completed"}';
        $signature = hash_hmac('sha256', $payload, $secret);

        $gw = new PaddleGateway(['vendor_id' => '1', 'vendor_auth_code' => $secret]);
        $this->assertTrue($gw->verifyWebhookSignature($payload, $signature));
    }

    public function test_webhook_signature_invalid(): void
    {
        $gw = new PaddleGateway(['vendor_id' => '1', 'vendor_auth_code' => 'my_secret']);
        $this->assertFalse($gw->verifyWebhookSignature('{}', 'wrong_sig'));
    }

    public function test_webhook_signature_empty_key(): void
    {
        $gw = new PaddleGateway(['vendor_id' => '1', 'vendor_auth_code' => '']);
        $this->assertFalse($gw->verifyWebhookSignature('{}', 'sig'));
    }
}
