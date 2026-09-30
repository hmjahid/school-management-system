<?php

namespace Tests\Unit\Services;

use App\Services\Payment\BkashGatewayAdapter;
use App\Services\Payment\GatewayAdapterFactory;
use App\Services\Payment\GatewayAdapterInterface;
use App\Services\Payment\GenericHostedGatewayAdapter;
use App\Services\Payment\NagadGatewayAdapter;
use App\Services\Payment\RocketGatewayAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GatewayAdapterFactoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_resolves_bkash_adapter(): void
    {
        $adapter = GatewayAdapterFactory::make('bkash');

        $this->assertInstanceOf(BkashGatewayAdapter::class, $adapter);
        $this->assertInstanceOf(GatewayAdapterInterface::class, $adapter);
    }

    #[Test]
    public function it_resolves_nagad_adapter(): void
    {
        $adapter = GatewayAdapterFactory::make('nagad');

        $this->assertInstanceOf(NagadGatewayAdapter::class, $adapter);
        $this->assertInstanceOf(GatewayAdapterInterface::class, $adapter);
    }

    #[Test]
    public function it_resolves_rocket_adapter(): void
    {
        $adapter = GatewayAdapterFactory::make('rocket');

        $this->assertInstanceOf(RocketGatewayAdapter::class, $adapter);
        $this->assertInstanceOf(GatewayAdapterInterface::class, $adapter);
    }

    #[Test]
    public function it_resolves_the_config_driven_adapter_for_unknown_gateways(): void
    {
        $adapter = GatewayAdapterFactory::make('a-manually-added-gateway');

        $this->assertInstanceOf(GenericHostedGatewayAdapter::class, $adapter);
        $this->assertInstanceOf(GatewayAdapterInterface::class, $adapter);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function internationalGatewayProvider(): array
    {
        $codes = [];
        foreach (\App\Models\PaymentGateway::CONFIG_DRIVEN_GATEWAYS as $code) {
            $codes[$code] = [$code];
        }

        return $codes;
    }

    #[Test]
    #[DataProvider('internationalGatewayProvider')]
    public function it_resolves_every_international_gateway_to_the_generic_adapter(string $code): void
    {
        $this->assertInstanceOf(GenericHostedGatewayAdapter::class, GatewayAdapterFactory::make($code));
    }
}
