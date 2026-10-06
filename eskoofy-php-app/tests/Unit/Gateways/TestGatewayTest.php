<?php
declare(strict_types=1);

namespace Tests\Unit\Gateways;

use App\Gateways\TestGateway;
use Tests\TestCase;

class TestGatewayTest extends TestCase
{
    private function gateway(array $overrides = []): TestGateway
    {
        return new TestGateway(array_merge([
            'app_url' => 'http://localhost:8000',
            'currency' => 'BDT',
        ], $overrides));
    }

    public function test_initialize_redirects_to_the_local_sandbox(): void
    {
        $result = $this->gateway()->initialize([
            'amount' => 100,
            'invoice_id' => 'INV-0001',
            'payment_id' => 42,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('http://localhost:8000/payments/sandbox/42', $result['data']['payment_url']);
        $this->assertSame('INV-0001', $result['data']['invoice_number']);
        $this->assertSame('BDT', $result['data']['currency']);
    }

    public function test_initialize_rejects_an_invalid_amount(): void
    {
        $result = $this->gateway()->initialize(['amount' => 0, 'invoice_id' => 'INV-1']);

        $this->assertFalse($result['success']);
    }

    public function test_process_callback_success_returns_a_test_transaction_id(): void
    {
        $result = $this->gateway()->processCallback([
            'simulate' => 'success',
            'invoice_id' => 'INV-0001',
            'payment_id' => 42,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('TEST-INV-0001', $result['data']['transaction_id']);
        $this->assertSame('completed', $result['data']['status']);
    }

    public function test_process_callback_failure(): void
    {
        $result = $this->gateway()->processCallback([
            'simulate' => 'failure',
            'invoice_id' => 'INV-0001',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('failed', $result['data']['status']);
    }

    public function test_process_callback_cancel(): void
    {
        $result = $this->gateway()->processCallback([
            'simulate' => 'cancel',
            'invoice_id' => 'INV-0001',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('cancelled', $result['data']['status']);
    }

    public function test_success_is_idempotent(): void
    {
        $gateway = $this->gateway();
        $payload = ['simulate' => 'success', 'invoice_id' => 'INV-0009'];

        $first = $gateway->processCallback($payload);
        $second = $gateway->processCallback($payload);

        $this->assertSame($first['data']['transaction_id'], $second['data']['transaction_id']);
        $this->assertSame('TEST-INV-0009', $second['data']['transaction_id']);
    }

    public function test_verify_payment_accepts_test_transactions_only(): void
    {
        $gateway = $this->gateway();

        $this->assertTrue($gateway->verifyPayment('TEST-INV-0001'));
        $this->assertTrue($gateway->verifyPayment('TEST-INV-0001'));
        $this->assertFalse($gateway->verifyPayment('TXN-LIVE-1'));
    }

    public function test_refunds_are_unsupported(): void
    {
        $result = $this->gateway()->refund('TEST-INV-1', 10.0, 'test');

        $this->assertFalse($result['success']);
    }

    public function test_webhook_signature_is_never_trusted(): void
    {
        $this->assertFalse($this->gateway()->verifyWebhookSignature('{}', 'anything'));
    }
}
