<?php
declare(strict_types=1);

namespace Tests\Unit\Gateways;

use App\Gateways\GatewayFactory;
use App\Gateways\BkashGateway;
use App\Gateways\ManualGateway;
use App\Gateways\NagadGateway;
use App\Gateways\PaddleGateway;
use App\Gateways\PaypalGateway;
use App\Gateways\RocketGateway;
use App\Gateways\StripeGateway;
use Tests\FakeDatabase;
use Tests\TestCase;

class GatewayFactoryTest extends TestCase
{
    public function test_default_code_is_manual(): void
    {
        $this->assertSame('manual', GatewayFactory::defaultCode(['default' => 'manual', 'gateways' => []]));
    }

    public function test_make_returns_manual_gateway(): void
    {
        $gateway = GatewayFactory::make('manual', ['default' => 'manual', 'gateways' => []], new FakeDatabase());

        $this->assertInstanceOf(ManualGateway::class, $gateway);
        $this->assertSame('manual', $gateway->id());
        $this->assertSame('Manual / Bank Transfer', $gateway->name());
    }

    public function test_make_is_case_insensitive(): void
    {
        $this->assertInstanceOf(ManualGateway::class, GatewayFactory::make('MANUAL', ['default' => 'manual', 'gateways' => []], new FakeDatabase()));
    }

    public function test_make_cache_returns_same_instance(): void
    {
        $config = ['default' => 'manual', 'gateways' => []];
        $db = new FakeDatabase();

        $this->assertSame(GatewayFactory::make('manual', $config, $db), GatewayFactory::make('manual', $config, $db));
    }

    public function test_paddle_gateway_is_available(): void
    {
        $gateway = GatewayFactory::make('paddle', ['default' => 'paddle', 'gateways' => []], new FakeDatabase());

        $this->assertInstanceOf(PaddleGateway::class, $gateway);
    }

    public function test_all_online_gateways_resolve(): void
    {
        $db = new FakeDatabase();
        $map = [
            'bkash'  => BkashGateway::class,
            'rocket' => RocketGateway::class,
            'nagad'  => NagadGateway::class,
            'stripe' => StripeGateway::class,
            'paypal' => PaypalGateway::class,
            'paddle' => PaddleGateway::class,
        ];
        foreach ($map as $code => $class) {
            $this->assertInstanceOf($class, GatewayFactory::make($code, ['default' => 'manual', 'gateways' => []], $db));
        }
    }

    public function test_unknown_gateway_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        GatewayFactory::make('bitcoin', ['default' => 'manual', 'gateways' => []], new FakeDatabase());
    }

    public function test_bd_country_detection(): void
    {
        $this->assertTrue(GatewayFactory::isBdCountry('BD'));
        $this->assertTrue(GatewayFactory::isBdCountry('Bangladesh'));
        $this->assertFalse(GatewayFactory::isBdCountry('US'));
        $this->assertFalse(GatewayFactory::isBdCountry(null));
    }

    public function test_gateways_for_bd_country_prefer_local(): void
    {
        // Configure the BD gateways via env so they are not filtered out.
        $_ENV['BKASH_APP_KEY'] = 'k';
        $_ENV['BKASH_APP_SECRET'] = 's';
        $_ENV['ROCKET_API_KEY'] = 'k';
        $_ENV['NAGAD_MERCHANT_ID'] = 'm';
        $_ENV['NAGAD_API_KEY'] = 'k';

        $codes = GatewayFactory::gatewaysForCountry('BD');
        $this->assertContains('bkash', $codes);
        $this->assertContains('manual', $codes);
        // BD should not offer the international gateways.
        $this->assertNotContains('stripe', $codes);

        unset($_ENV['BKASH_APP_KEY'], $_ENV['BKASH_APP_SECRET'], $_ENV['ROCKET_API_KEY'], $_ENV['NAGAD_MERCHANT_ID'], $_ENV['NAGAD_API_KEY']);
    }

    public function test_gateways_for_int_country_prefer_international(): void
    {
        $_ENV['STRIPE_SECRET_KEY'] = 'k';
        $_ENV['PAYPAL_CLIENT_ID'] = 'k';
        $_ENV['PAYPAL_CLIENT_SECRET'] = 's';
        $_ENV['PADDLE_VENDOR_ID'] = 'v';

        $codes = GatewayFactory::gatewaysForCountry('US');
        $this->assertContains('stripe', $codes);
        $this->assertContains('manual', $codes);
        $this->assertNotContains('bkash', $codes);

        unset($_ENV['STRIPE_SECRET_KEY'], $_ENV['PAYPAL_CLIENT_ID'], $_ENV['PAYPAL_CLIENT_SECRET'], $_ENV['PADDLE_VENDOR_ID']);
    }

    public function test_bdt_conversion(): void
    {
        $config = ['default' => 'manual', 'gateways' => [], 'bdt_rate' => 110];
        $this->assertSame(1100.0, GatewayFactory::toBdt(10.0, $config));
        $this->assertSame(110.0, GatewayFactory::bdtRate($config));
    }
}
