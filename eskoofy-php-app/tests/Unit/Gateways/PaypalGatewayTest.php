<?php
declare(strict_types=1);

namespace Tests\Unit\Gateways;

use App\Gateways\PaypalGateway;
use Tests\TestCase;

class PaypalGatewayTest extends TestCase
{
    public function test_initialize_fails_with_zero_amount(): void
    {
        $gw = new PaypalGateway([
            'client_id' => 'fake',
            'client_secret' => 'fake',
            'mode' => 'sandbox',
        ]);
        $result = $gw->initialize(['amount' => 0]);
        $this->assertFalse($result['success']);
    }

    public function test_initialize_fails_with_negative_amount(): void
    {
        $gw = new PaypalGateway(['client_id' => 'fake', 'client_secret' => 'fake', 'mode' => 'sandbox']);
        $result = $gw->initialize(['amount' => -10]);
        $this->assertFalse($result['success']);
    }

    public function test_process_callback_ignores_invalid_event(): void
    {
        $gw = new PaypalGateway(['client_id' => 'fake', 'client_secret' => 'fake', 'mode' => 'sandbox']);
        $result = $gw->processCallback(['event_type' => 'PAYMENT.CAPTURE.DENIED', 'resource' => []]);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Ignoring', $result['message']);
    }

    public function test_process_callback_capture_completed(): void
    {
        $gw = new PaypalGateway(['client_id' => 'fake', 'client_secret' => 'fake', 'mode' => 'sandbox']);
        $result = $gw->processCallback([
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'capture_123',
                'custom_id' => 'INV-001',
                'amount' => ['value' => '50.00', 'currency_code' => 'USD'],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('INV-001', $result['data']['transaction_id']);
        $this->assertSame('completed', $result['data']['status']);
        $this->assertSame('50.00', $result['data']['amount']);
    }

    public function test_process_callback_checkout_approved(): void
    {
        $gw = new PaypalGateway(['client_id' => 'fake', 'client_secret' => 'fake', 'mode' => 'sandbox']);
        $result = $gw->processCallback([
            'event_type' => 'CHECKOUT.ORDER.APPROVED',
            'resource' => [
                'id' => 'order_456',
                'amount' => ['value' => '100.00', 'currency_code' => 'USD'],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('order_456', $result['data']['order_id']);
    }

    public function test_refund_returns_error_when_auth_fails(): void
    {
        $gw = new PaypalGateway(['client_id' => '', 'client_secret' => '', 'mode' => 'sandbox']);
        $result = $gw->refund('cap_123', 50, 'test');
        $this->assertFalse($result['success']);
    }

    public function test_base_url_sandbox(): void
    {
        $gw = new PaypalGateway(['mode' => 'sandbox', 'client_id' => '', 'client_secret' => '']);
        $ref = new \ReflectionMethod($gw, 'baseUrl');
        $this->assertSame('https://api-m.sandbox.paypal.com', $ref->invoke($gw));
    }

    public function test_base_url_live(): void
    {
        $gw = new PaypalGateway(['mode' => 'live', 'client_id' => '', 'client_secret' => '']);
        $ref = new \ReflectionMethod($gw, 'baseUrl');
        $this->assertSame('https://api-m.paypal.com', $ref->invoke($gw));
    }
}
