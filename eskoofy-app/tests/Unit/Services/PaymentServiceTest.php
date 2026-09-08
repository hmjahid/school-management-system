<?php

namespace Tests\Unit\Services;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function gateway(array $attributes): PaymentGateway
    {
        return PaymentGateway::create(array_merge([
            'name' => 'Gateway',
            'code' => 'gw_'.uniqid(),
            'type' => PaymentGateway::TYPE_OTHER,
            'is_active' => true,
        ], $attributes));
    }

    #[Test]
    public function it_throws_when_gateway_is_inactive(): void
    {
        $code = 'inactive_'.uniqid();
        $this->gateway(['code' => $code, 'is_active' => false, 'is_online' => false]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment gateway is not active');

        app(PaymentService::class)->initializePayment(Payment::factory()->create(), $code);
    }

    #[Test]
    public function it_throws_when_online_gateway_not_configured(): void
    {
        $code = 'bkash';
        $this->gateway([
            'code' => $code,
            'type' => PaymentGateway::TYPE_MOBILE_FINANCIAL_SERVICE,
            'is_active' => true,
            'is_online' => true,
            'api_key' => null,
            'api_secret' => null,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment gateway is not properly configured');

        app(PaymentService::class)->initializePayment(Payment::factory()->create(), $code);
    }

    #[Test]
    public function it_returns_offline_instructions_for_offline_gateway(): void
    {
        config(['payment.offline' => [
            'account_name' => 'School Account',
            'account_number' => '123456',
            'bank_name' => 'Test Bank',
            'branch' => 'Main',
            'routing_number' => 'ROUTE1',
        ]]);

        $code = 'cash_'.uniqid();
        $gateway = $this->gateway([
            'code' => $code,
            'is_active' => true,
            'is_online' => false,
            'instructions' => 'Pay at the counter',
        ]);

        $payment = Payment::factory()->create(['invoice_number' => 'INV-TEST-1']);

        $result = app(PaymentService::class)->initializePayment($payment, $code);

        $this->assertTrue($result['success']);
        $this->assertEquals($code, $result['gateway']);
        $this->assertNull($result['redirect_url']);
        $this->assertEquals('Pay at the counter', $result['offline_instructions']);
        $this->assertEquals('INV-TEST-1', $result['payment_details']['reference']);
        $this->assertEquals('School Account', $result['payment_details']['account_name']);
    }

    #[Test]
    public function supports_refunds_lists_known_methods(): void
    {
        $service = app(PaymentService::class);

        $this->assertTrue($service->supportsRefunds('bkash'));
        $this->assertTrue($service->supportsRefunds('nagad'));
        $this->assertTrue($service->supportsRefunds('rocket'));
        $this->assertTrue($service->supportsRefunds('test_gateway'));
        $this->assertFalse($service->supportsRefunds('cash'));
        $this->assertFalse($service->supportsRefunds('bank_transfer'));
    }

    #[Test]
    public function process_refund_succeeds_for_test_gateway(): void
    {
        $result = app(PaymentService::class)->processRefund('test_gateway', 'TXN-1', 500.00, 'Customer request');

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('R-', $result['transaction_id']);
        $this->assertEquals(['status' => 'Completed'], $result['gateway_response']);
    }

    #[Test]
    public function process_refund_fails_for_unknown_gateway_without_config(): void
    {
        $result = app(PaymentService::class)->processRefund('mystery_gateway', 'TXN-1', 100.00, 'reason');

        $this->assertFalse($result['success']);
        $this->assertEquals('Payment gateway not configured: mystery_gateway', $result['message']);
    }

    #[Test]
    public function resolve_gateway_config_returns_null_for_unknown(): void
    {
        $this->assertNull(app(PaymentService::class)->resolveGatewayConfig('nonexistent_gateway'));
    }

    #[Test]
    public function resolve_gateway_config_reads_from_config_when_present(): void
    {
        config(['payment.gateways.demo_gw' => [
            'test_mode' => true,
            'sandbox_url' => 'https://sandbox.example.com',
            'live_url' => 'https://live.example.com',
            'api_key' => 'k',
            'api_secret' => 's',
            'currency' => 'BDT',
        ]]);

        $config = app(PaymentService::class)->resolveGatewayConfig('demo_gw');

        $this->assertIsArray($config);
        $this->assertEquals('https://live.example.com', $config['base_url']);
    }

    #[Test]
    public function verify_payment_returns_same_payment_for_cash_method(): void
    {
        $payment = Payment::factory()->create(['payment_method' => 'cash']);

        $result = app(PaymentService::class)->verifyPayment($payment);

        $this->assertSame($payment->id, $result->id);
    }
}
