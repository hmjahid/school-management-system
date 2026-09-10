<?php
declare(strict_types=1);

namespace Tests\Unit\Gateways;

use App\Gateways\GatewayFactory;
use App\Gateways\GatewayInterface;
use App\Gateways\OfflineGateway;
use App\Gateways\StripeGateway;
use App\Gateways\PaypalGateway;
use App\Gateways\PaddleGateway;
use App\Gateways\BKashGateway;
use App\Gateways\RocketGateway;
use App\Gateways\NagadGateway;
use Tests\TestCase;

class GatewayFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $ref = new \ReflectionClass(GatewayFactory::class);
        $prop = $ref->getProperty('instances');
        $prop->setValue(null, []);
    }

    public function test_make_offline(): void
    {
        $gw = GatewayFactory::make('offline');
        $this->assertInstanceOf(OfflineGateway::class, $gw);
    }

    public function test_make_offline_returns_same_instance(): void
    {
        $gw1 = GatewayFactory::make('offline');
        $gw2 = GatewayFactory::make('offline');
        $this->assertSame($gw1, $gw2);
    }

    public function test_make_stripe(): void
    {
        $gw = GatewayFactory::make('stripe');
        $this->assertInstanceOf(StripeGateway::class, $gw);
    }

    public function test_make_paypal(): void
    {
        $gw = GatewayFactory::make('paypal');
        $this->assertInstanceOf(PaypalGateway::class, $gw);
    }

    public function test_make_paddle(): void
    {
        $gw = GatewayFactory::make('paddle');
        $this->assertInstanceOf(PaddleGateway::class, $gw);
    }

    public function test_make_bkash(): void
    {
        $gw = GatewayFactory::make('bkash');
        $this->assertInstanceOf(BKashGateway::class, $gw);
    }

    public function test_make_rocket(): void
    {
        $gw = GatewayFactory::make('rocket');
        $this->assertInstanceOf(RocketGateway::class, $gw);
    }

    public function test_make_nagad(): void
    {
        $gw = GatewayFactory::make('nagad');
        $this->assertInstanceOf(NagadGateway::class, $gw);
    }

    public function test_make_unknown_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown payment gateway: foo');
        GatewayFactory::make('foo');
    }

    public function test_make_caches_instances(): void
    {
        $gw1 = GatewayFactory::make('stripe');
        $gw2 = GatewayFactory::make('stripe');
        $this->assertSame($gw1, $gw2);
    }

    public function test_get_default(): void
    {
        $gw = GatewayFactory::getDefault();
        $this->assertInstanceOf(GatewayInterface::class, $gw);
    }

    public function test_all_returns_configured_gateways(): void
    {
        $all = GatewayFactory::all();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('offline', $all);
        $this->assertArrayHasKey('bkash', $all);
        $this->assertArrayHasKey('stripe', $all);
    }

    public function test_online_excludes_offline(): void
    {
        $online = GatewayFactory::online();
        $this->assertArrayNotHasKey('offline', $online);
    }

    public function test_offline_returns_only_offline(): void
    {
        $offline = GatewayFactory::offline();
        $this->assertArrayHasKey('offline', $offline);
        $this->assertCount(1, $offline);
    }

    public function test_all_gateways_implement_interface(): void
    {
        $all = GatewayFactory::all();
        foreach (array_keys($all) as $code) {
            $gw = GatewayFactory::make($code);
            $this->assertInstanceOf(GatewayInterface::class, $gw);
        }
    }

    public function test_make_from_payment_record_offline(): void
    {
        $gw = GatewayFactory::makeFromPaymentRecord(
            ['payment_method' => 'offline'],
            []
        );
        $this->assertInstanceOf(OfflineGateway::class, $gw);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $ref = new \ReflectionClass(GatewayFactory::class);
        $prop = $ref->getProperty('instances');
        $prop->setValue(null, []);
    }
}
