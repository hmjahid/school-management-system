<?php

namespace Tests\Unit\Models;

use App\Models\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_true_for_offline_gateway_configuration(): void
    {
        $gateway = PaymentGateway::create([
            'name' => 'Cash',
            'code' => 'cash',
            'type' => PaymentGateway::TYPE_OTHER,
            'is_active' => true,
            'is_online' => false,
        ]);

        $this->assertTrue($gateway->is_configured);
    }

    /** @test */
    public function it_requires_api_key_and_secret_for_bkash(): void
    {
        $unconfigured = PaymentGateway::create([
            'name' => 'bKash',
            'code' => 'bkash',
            'type' => PaymentGateway::TYPE_MOBILE_FINANCIAL_SERVICE,
            'is_active' => true,
            'is_online' => true,
            'api_key' => null,
            'api_secret' => null,
        ]);
        $this->assertFalse($unconfigured->is_configured);

        $configured = PaymentGateway::create([
            'name' => 'bKash',
            'code' => 'bkash_2',
            'type' => PaymentGateway::TYPE_MOBILE_FINANCIAL_SERVICE,
            'is_active' => true,
            'is_online' => true,
            'api_key' => 'test-key',
            'api_secret' => 'test-secret',
        ]);
        $this->assertTrue($configured->is_configured);
    }

    /** @test */
    public function it_requires_callback_url_for_stripe(): void
    {
        $withoutCallback = PaymentGateway::create([
            'name' => 'Stripe',
            'code' => 'stripe',
            'type' => PaymentGateway::TYPE_ONLINE_PAYMENT,
            'is_active' => true,
            'is_online' => true,
            'api_key' => 'test-key',
            'api_secret' => 'test-secret',
            'callback_url' => null,
        ]);
        $this->assertFalse($withoutCallback->is_configured);

        $withCallback = PaymentGateway::create([
            'name' => 'Stripe',
            'code' => 'stripe_2',
            'type' => PaymentGateway::TYPE_ONLINE_PAYMENT,
            'is_active' => true,
            'is_online' => true,
            'api_key' => 'test-key',
            'api_secret' => 'test-secret',
            'callback_url' => 'https://example.com/callback',
        ]);
        $this->assertTrue($withCallback->is_configured);
    }

    /** @test */
    public function it_slugifies_code_on_save(): void
    {
        $gateway = PaymentGateway::create([
            'name' => 'Test Gateway',
            'code' => 'Test Gateway Code!',
            'type' => PaymentGateway::TYPE_OTHER,
            'is_active' => true,
            'is_online' => false,
        ]);

        $this->assertEquals('test_gateway_code_', $gateway->fresh()->code);
    }

    /** @test */
    public function it_defaults_currency_to_bdt(): void
    {
        $gateway = PaymentGateway::create([
            'name' => 'No Currency',
            'code' => 'nocurrency',
            'type' => PaymentGateway::TYPE_OTHER,
            'is_active' => true,
            'is_online' => false,
        ]);

        $this->assertEquals('BDT', $gateway->fresh()->currency);
    }

    /** @test */
    public function it_defaults_supported_currencies_to_currency_array(): void
    {
        $gateway = PaymentGateway::create([
            'name' => 'USD Gateway',
            'code' => 'usdgateway',
            'type' => PaymentGateway::TYPE_OTHER,
            'is_active' => true,
            'is_online' => false,
            'currency' => 'USD',
        ]);

        $this->assertEquals(['USD'], $gateway->fresh()->supported_currencies);
    }

    /** @test */
    public function it_returns_correct_type_label(): void
    {
        $bank = PaymentGateway::create([
            'name' => 'Bank',
            'code' => 'bank',
            'type' => PaymentGateway::TYPE_BANK,
            'is_active' => true,
            'is_online' => false,
        ]);
        $this->assertEquals('Bank', $bank->type_label);

        $mfs = PaymentGateway::create([
            'name' => 'MFS',
            'code' => 'mfs',
            'type' => PaymentGateway::TYPE_MOBILE_FINANCIAL_SERVICE,
            'is_active' => true,
            'is_online' => true,
        ]);
        $this->assertEquals('Mobile Financial Service', $mfs->type_label);
    }

    /** @test */
    public function it_hides_api_credentials_in_array_form(): void
    {
        $gateway = PaymentGateway::create([
            'name' => 'bKash',
            'code' => 'bkash_secret',
            'type' => PaymentGateway::TYPE_MOBILE_FINANCIAL_SERVICE,
            'is_active' => true,
            'is_online' => true,
            'api_key' => 'secret-key',
            'api_secret' => 'secret-secret',
            'api_username' => 'secret-user',
            'api_password' => 'secret-pass',
        ]);

        $array = $gateway->toArray();

        $this->assertArrayNotHasKey('api_key', $array);
        $this->assertArrayNotHasKey('api_secret', $array);
        $this->assertArrayNotHasKey('api_username', $array);
        $this->assertArrayNotHasKey('api_password', $array);
    }

    /** @test */
    public function it_returns_empty_api_config_for_offline_gateway(): void
    {
        $gateway = PaymentGateway::create([
            'name' => 'Cash',
            'code' => 'cash_cfg',
            'type' => PaymentGateway::TYPE_OTHER,
            'is_active' => true,
            'is_online' => false,
        ]);

        $this->assertEquals([], $gateway->getApiConfig());
    }

    /** @test */
    public function it_returns_api_config_for_online_gateway(): void
    {
        $gateway = PaymentGateway::create([
            'name' => 'bKash',
            'code' => 'bkash_api',
            'type' => PaymentGateway::TYPE_MOBILE_FINANCIAL_SERVICE,
            'is_active' => true,
            'is_online' => true,
            'api_key' => 'test-key',
            'api_secret' => 'test-secret',
            'callback_url' => 'https://example.com/callback',
            'test_mode' => true,
            'currency' => 'BDT',
        ]);

        $config = $gateway->getApiConfig();

        $this->assertTrue($config['test_mode']);
        $this->assertEquals('test-key', $config['api_key']);
        $this->assertEquals('test-secret', $config['api_secret']);
        $this->assertEquals('BDT', $config['currency']);
    }

    /** @test */
    public function it_returns_full_config_array(): void
    {
        $gateway = PaymentGateway::create([
            'name' => 'Test',
            'code' => 'test_cfg',
            'type' => PaymentGateway::TYPE_OTHER,
            'is_active' => true,
            'is_online' => false,
        ]);

        $config = $gateway->getConfig();

        $this->assertEquals('Test', $config['name']);
        $this->assertEquals('test_cfg', $config['code']);
        $this->assertTrue($config['is_active']);
        $this->assertArrayHasKey('is_configured', $config);
    }

    /** @test */
    public function scope_active_filters_correctly(): void
    {
        PaymentGateway::create([
            'name' => 'Active',
            'code' => 'active_gw',
            'type' => PaymentGateway::TYPE_OTHER,
            'is_active' => true,
            'is_online' => false,
        ]);

        PaymentGateway::create([
            'name' => 'Inactive',
            'code' => 'inactive_gw',
            'type' => PaymentGateway::TYPE_OTHER,
            'is_active' => false,
            'is_online' => false,
        ]);

        $active = PaymentGateway::active()->get();
        $this->assertCount(1, $active);
        $this->assertEquals('Active', $active->first()->name);
    }
}
