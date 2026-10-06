<?php

namespace Tests\Unit\Services;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\Payment\GatewayAdapterFactory;
use App\Services\Payment\GenericHostedGatewayAdapter;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BdHostedGatewayTest extends TestCase
{
    use RefreshDatabase;

    private const BD_GATEWAYS = [
        'shurjopay',
        'portwallet',
        'cellfin',
        'purse',
        'cashby',
        'upay',
        'mycash',
        'payer',
    ];

    /**
     * @return array<string, array{string}>
     */
    public static function bdGatewayProvider(): array
    {
        $codes = [];
        foreach (self::BD_GATEWAYS as $code) {
            $codes[$code] = [$code];
        }

        return $codes;
    }

    private function gateway(string $code): PaymentGateway
    {
        return PaymentGateway::where('code', $code)->firstOrFail();
    }

    private function payment(): Payment
    {
        return Payment::create([
            'paymentable_type' => Payment::PURPOSE_TUITION,
            'paymentable_id' => 1,
            'invoice_number' => 'INV-000789',
            'amount' => 100,
            'paid_amount' => 0,
            'due_amount' => 100,
            'total_amount' => 100,
            'payment_method' => 'shurjopay',
            'payment_status' => Payment::STATUS_PENDING,
        ]);
    }

    #[Test]
    public function it_seeds_the_extended_bd_gateways_disabled_and_unconfigured(): void
    {
        $this->seed(PaymentGatewaySeeder::class);

        foreach (self::BD_GATEWAYS as $code) {
            $gateway = $this->gateway($code);

            $this->assertFalse($gateway->is_active, $code);
            $this->assertTrue($gateway->test_mode, $code);
            $this->assertNull($gateway->api_key, $code);
            $this->assertNotEmpty($gateway->sandbox_url, $code);
            $this->assertFalse($gateway->is_configured, $code);
            $this->assertSame('BDT', $gateway->currency, $code);
        }
    }

    #[Test]
    #[DataProvider('bdGatewayProvider')]
    public function they_resolve_to_the_config_driven_adapter(string $code): void
    {
        $this->seed(PaymentGatewaySeeder::class);

        $this->assertInstanceOf(
            GenericHostedGatewayAdapter::class,
            GatewayAdapterFactory::make($code)
        );
    }

    #[Test]
    #[DataProvider('bdGatewayProvider')]
    public function they_carry_the_generic_hosted_contract(string $code): void
    {
        $this->seed(PaymentGatewaySeeder::class);

        $gateway = $this->gateway($code);
        $config = $gateway->getApiConfig();

        $this->assertSame('GET', $config['checkout_method']);
        $this->assertSame('status', $config['verify_success_path']);
        $this->assertSame('COMPLETED', $config['verify_success_value']);
        $this->assertNotEmpty($config['verify_url']);

        foreach (['amount', 'currency', 'reference', 'invoice', 'callback', 'cancel'] as $placeholder) {
            $this->assertStringContainsString('{'.$placeholder.'}', $config['checkout_url_template'], $code);
        }

        $this->assertStringStartsWith(
            rtrim($gateway->sandbox_url, '/'),
            $config['checkout_url_template'],
            $code
        );
        $this->assertArrayNotHasKey('refund_url', $config, $code);
    }

    #[Test]
    public function it_builds_a_shurjopay_sandbox_checkout_redirect(): void
    {
        Http::fake();

        $this->seed(PaymentGatewaySeeder::class);

        $result = (new GenericHostedGatewayAdapter)->initialize($this->payment(), $this->gateway('shurjopay'));

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('https://sandbox.shurjopay.io/checkout?', $result['redirect_url']);
        $this->assertStringContainsString('amount=100.00', $result['redirect_url']);
        $this->assertStringContainsString('currency=BDT', $result['redirect_url']);
        $this->assertStringContainsString('reference=INV-000789', $result['redirect_url']);
        Http::assertNothingSent();
    }

    #[Test]
    public function it_verifies_through_the_configured_sandbox_endpoint(): void
    {
        $this->seed(PaymentGatewaySeeder::class);

        Http::fake([
            '*' => Http::response(['status' => 'COMPLETED', 'transaction_id' => 'SP-123'], 200),
        ]);

        $result = (new GenericHostedGatewayAdapter)->verifyPayment($this->payment(), $this->gateway('shurjopay'));

        Http::assertSent(fn ($request) => $request->url() === 'https://sandbox.shurjopay.io/verify'
            && $request->method() === 'POST'
            && $request['reference'] === 'INV-000789');

        $this->assertSame(Payment::STATUS_COMPLETED, $result->payment_status);
        $this->assertSame('SP-123', $result->transaction_id);
    }
}
