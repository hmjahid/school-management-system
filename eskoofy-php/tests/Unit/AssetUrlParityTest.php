<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Guards asset() / url() routing semantics.
 *
 * asset() must emit host-relative URLs (the app's nav links are host-relative
 * already), so the built CSS/JS, favicon and og-image keep working regardless
 * of the host/port the site is served on — hardcoding APP_URL for static
 * files breaks the homepage on any other port (e.g. left running php -S on
 * 8051 while APP_URL points at :8000). url() keeps returning absolute URLs
 * for callers that need them (API logo_url, canonical-ish flows).
 */
class AssetUrlParityTest extends PHPUnitTestCase
{
    public function test_asset_is_host_relative(): void
    {
        $this->assertSame('/build/assets/app-abc123.css', asset('build/assets/app-abc123.css'));
        $this->assertSame('/build/assets/app-abc123.js', asset('/build/assets/app-abc123.js'));
        $this->assertSame('/storage/logo.png', asset('storage/logo.png'));
        $this->assertSame('/favicon.ico', asset('favicon.ico'));
    }

    public function test_asset_passes_external_urls_through_unchanged(): void
    {
        $https = 'https://cdn.example.com/app.js';
        $http = 'http://cdn.example.com/app.js';
        $protoRelative = '//cdn.example.com/app.js';
        $this->assertSame($https, asset($https));
        $this->assertSame($http, asset($http));
        $this->assertSame($protoRelative, asset($protoRelative));
    }

    public function test_url_stays_absolute_for_non_asset_callers(): void
    {
        $this->assertSame(config('app.url', '') . '/uploads/x.jpg', url('uploads/x.jpg'));
        $this->assertSame(rtrim(config('app.url', ''), '/') . '/', url('/'));
    }

    public function test_blade_vite_emits_relative_asset_hrefs(): void
    {
        $html = (new \App\Core\Blade())->vite(['resources/css/app.css', 'resources/js/app.js']);
        $this->assertStringContainsString('/build/assets/', $html);
        $this->assertStringNotContainsString('http://', $html);
        $this->assertStringNotContainsString('https://', $html);
    }
}