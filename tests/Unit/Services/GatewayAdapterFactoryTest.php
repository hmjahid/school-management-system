<?php

namespace Tests\Unit\Services;

use App\Services\Payment\BkashGatewayAdapter;
use App\Services\Payment\GatewayAdapterFactory;
use App\Services\Payment\GatewayAdapterInterface;
use App\Services\Payment\NagadGatewayAdapter;
use App\Services\Payment\RocketGatewayAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GatewayAdapterFactoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_resolves_bkash_adapter(): void
    {
        $adapter = GatewayAdapterFactory::make('bkash');

        $this->assertInstanceOf(BkashGatewayAdapter::class, $adapter);
        $this->assertInstanceOf(GatewayAdapterInterface::class, $adapter);
    }

    /** @test */
    public function it_resolves_nagad_adapter(): void
    {
        $adapter = GatewayAdapterFactory::make('nagad');

        $this->assertInstanceOf(NagadGatewayAdapter::class, $adapter);
        $this->assertInstanceOf(GatewayAdapterInterface::class, $adapter);
    }

    /** @test */
    public function it_resolves_rocket_adapter(): void
    {
        $adapter = GatewayAdapterFactory::make('rocket');

        $this->assertInstanceOf(RocketGatewayAdapter::class, $adapter);
        $this->assertInstanceOf(GatewayAdapterInterface::class, $adapter);
    }

    /** @test */
    public function it_throws_for_unsupported_gateway(): void
    {
        $this->expectException(\Exception::class);

        GatewayAdapterFactory::make('unknown-gateway');
    }
}
