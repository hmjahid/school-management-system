<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Tests\TestCase;

/**
 * Phase 0 gate (B13): the vendored chart bundles must load under the CSP that
 * `SecurityHeadersMiddleware` already emits, so the dashboard redesign needs no
 * policy change and no CDN allow-list growth.
 *
 * The middleware is asserted against its literal policy string rather than by
 * invoking `handle()`, which would emit real headers into the test process.
 */
class SecurityHeadersCspTest extends TestCase
{
    private const CSP = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' "
        . "https://cdn.tailwindcss.com https://fonts.bunny.net; style-src 'self' 'unsafe-inline' "
        . "https://fonts.bunny.net https://fonts.googleapis.com; img-src 'self' data: https:; "
        . "font-src 'self' data: https://fonts.bunny.net https://fonts.googleapis.com "
        . "https://fonts.gstatic.com; connect-src 'self' https://fonts.googleapis.com "
        . "https://fonts.gstatic.com; frame-ancestors 'self'";

    private const BUNDLES = [
        'public/vendor/charts/chart.umd.min.js',
        'public/vendor/charts/apexcharts.min.js',
        'public/js/charts.js',
        'public/js/admin.js',
        'public/css/admin.css',
    ];

    private function policy(): string
    {
        $source = file_get_contents(
            dirname(__DIR__, 3) . '/app/Core/Middleware/SecurityHeadersMiddleware.php'
        );
        self::assertIsString($source);

        // The policy must not drift from what this test asserts.
        self::assertStringContainsString(self::CSP, $source);

        return self::CSP;
    }

    public function testSameOriginScriptsAndStylesAreAllowed(): void
    {
        $csp = $this->policy();

        // 'self' is what lets /vendor/charts/*.js load without a CDN exception.
        self::assertStringContainsString("script-src 'self'", $csp);
        self::assertStringContainsString("style-src 'self'", $csp);
        self::assertStringContainsString("default-src 'self'", $csp);
    }

    public function testPolicyDoesNotNeedPerBundleAllowListEntries(): void
    {
        $csp = $this->policy();

        foreach (self::BUNDLES as $bundle) {
            self::assertStringNotContainsString($bundle, $csp, 'bundles are same-origin, not allow-listed');
        }
    }

    public function testVendoredBundlesExistAndAreLocal(): void
    {
        $root = dirname(__DIR__, 3);

        foreach (self::BUNDLES as $bundle) {
            $path = $root . '/' . $bundle;
            self::assertFileExists($path, "missing vendored asset: {$bundle}");
            self::assertGreaterThan(0, filesize($path));
        }
    }

    public function testPinnedChartVersionsAreRecorded(): void
    {
        $versions = file_get_contents(dirname(__DIR__, 3) . '/public/vendor/charts/VERSIONS.md');
        self::assertIsString($versions);

        // The record is a markdown table: | file | Library | x.y.z | size | licence |
        self::assertMatchesRegularExpression('/Chart\.js\s*\|\s*\d+\.\d+\.\d+/i', $versions);
        self::assertMatchesRegularExpression('/ApexCharts\s*\|\s*\d+\.\d+\.\d+/i', $versions);
    }
}
