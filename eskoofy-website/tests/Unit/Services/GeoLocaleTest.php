<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\GeoLocale;
use Tests\TestCase;

class GeoLocaleTest extends TestCase
{
    private function baseConfig(): array
    {
        return [
            'enabled'            => 'true',
            'cdn_headers'        => ['CF-IPCountry', 'X-IPCountry', 'IPCountry'],
            'accept_language'    => true,
            'remote_api_url'     => '',
            'remote_api_timeout' => 2,
            'use_timezone_hint'  => true,
            'bd_locale'          => 'bn',
            'other_locale'       => 'en',
        ];
    }

    public function test_cdn_header_bd_yields_bd_locale(): void
    {
        $config = $this->baseConfig();
        $server = ['CF_IPCountry' => 'BD'];

        $this->assertSame('bn', GeoLocale::detect($server, $config));
    }

    public function test_cdn_header_us_yields_other_locale(): void
    {
        $config = $this->baseConfig();
        $server = ['CF_IPCountry' => 'US'];

        $this->assertSame('en', GeoLocale::detect($server, $config));
    }

    public function test_cdn_header_lookup_is_case_insensitive_and_trims(): void
    {
        $config = $this->baseConfig();

        $this->assertSame('bn', GeoLocale::detect(['CF_IPCountry' => '  bd  '], $config));
        $this->assertSame('bn', GeoLocale::detect(['CF_IPCountry' => 'bD'], $config));
    }

    public function test_accept_language_bn_yields_bd(): void
    {
        $config = $this->baseConfig();
        $server = ['HTTP_ACCEPT_LANGUAGE' => 'bn-BD,bn;q=0.9,en;q=0.7'];

        $this->assertSame('bn', GeoLocale::detect($server, $config));
    }

    public function test_accept_language_en_yields_other(): void
    {
        $config = $this->baseConfig();
        $server = ['HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9'];

        $this->assertSame('en', GeoLocale::detect($server, $config));
    }

    public function test_timezone_asia_dhaka_upgrades_to_bd(): void
    {
        $config = $this->baseConfig();
        $server = [];

        $this->assertSame('bn', GeoLocale::detect($server, $config, 'Asia/Dhaka'));
    }

    public function test_timezone_other_does_not_upgrade(): void
    {
        $config = $this->baseConfig();
        $server = [];

        $this->assertSame('en', GeoLocale::detect($server, $config, 'America/New_York'));
        $this->assertSame('en', GeoLocale::detect($server, $config, 'Europe/Paris'));
    }

    public function test_remote_api_failure_falls_through(): void
    {
        $config = $this->baseConfig();
        $config['remote_api_url'] = 'http://127.0.0.1:1/never-listening.json';
        $server = ['REMOTE_ADDR' => '203.0.113.5'];

        $this->assertSame('en', GeoLocale::detect($server, $config, null));
    }

    public function test_remote_api_with_bd_country_yields_bd(): void
    {
        $config = $this->baseConfig();
        $tmp = tempnam(sys_get_temp_dir(), 'eskgeo_');
        file_put_contents($tmp, json_encode(['country' => 'BD']));
        $config['remote_api_url'] = 'file://' . $tmp;
        $server = ['REMOTE_ADDR' => '203.0.113.5'];

        $this->assertSame('bn', GeoLocale::detect($server, $config, null));
        unlink($tmp);
    }

    public function test_disabled_returns_other(): void
    {
        $config = $this->baseConfig();
        $config['enabled'] = 'false';
        $server = ['CF_IPCountry' => 'BD', 'HTTP_ACCEPT_LANGUAGE' => 'bn'];

        $this->assertSame('en', GeoLocale::detect($server, $config, 'Asia/Dhaka'));
    }

    public function test_invalid_inputs_never_throw(): void
    {
        $config = $this->baseConfig();
        $server = ['CF_IPCountry' => 'X1', 'HTTP_ACCEPT_LANGUAGE' => null];

        $this->assertSame('en', GeoLocale::detect($server, $config, ''));
    }

    public function test_priority_cdn_header_beats_accept_language(): void
    {
        $config = $this->baseConfig();
        $server = ['CF_IPCountry' => 'BD', 'HTTP_ACCEPT_LANGUAGE' => 'en-US'];

        $this->assertSame('bn', GeoLocale::detect($server, $config, 'America/New_York'));
    }

    public function test_is_bd_header_helper(): void
    {
        $this->assertTrue(GeoLocale::isBdHeader('BD'));
        $this->assertTrue(GeoLocale::isBdHeader(' bd '));
        $this->assertFalse(GeoLocale::isBdHeader('US'));
        $this->assertFalse(GeoLocale::isBdHeader(null));
    }

    public function test_is_bd_accept_language_helper(): void
    {
        $this->assertTrue(GeoLocale::isBdAcceptLanguage('bn'));
        $this->assertTrue(GeoLocale::isBdAcceptLanguage('bn-BD,en;q=0.5'));
        $this->assertFalse(GeoLocale::isBdAcceptLanguage('en-US'));
        $this->assertFalse(GeoLocale::isBdAcceptLanguage(''));
        $this->assertFalse(GeoLocale::isBdAcceptLanguage(null));
    }

    public function test_is_bd_timezone_helper(): void
    {
        $this->assertTrue(GeoLocale::isBdTimezone('Asia/Dhaka'));
        $this->assertTrue(GeoLocale::isBdTimezone('  Asia/Dhaka  '));
        $this->assertFalse(GeoLocale::isBdTimezone('Asia/Karachi'));
        $this->assertFalse(GeoLocale::isBdTimezone(null));
    }
}