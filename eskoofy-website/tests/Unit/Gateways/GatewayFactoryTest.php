<?php
declare(strict_types=1);

namespace Tests\Unit\Gateways;

use App\Gateways\GatewayFactory;
use App\Gateways\ManualGateway;
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

    public function test_paddle_throws_not_available(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not yet available');

        GatewayFactory::make('paddle', ['default' => 'paddle', 'gateways' => []], new FakeDatabase());
    }

    public function test_unknown_gateway_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        GatewayFactory::make('bitcoin', ['default' => 'manual', 'gateways' => []], new FakeDatabase());
    }
}