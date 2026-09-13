<?php
declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Guards the dashboard-favorites toggle parity with the app.
 *
 * The app registers a single POST /dashboard/favorites/toggle route backed by
 * the user widget-style dashboard_favorites schema (url + label). The php port
 * previously also shipped a php-only /favorites/toggle/{module} variant that
 * queried a nonexistent `module` column and crashed against the real schema.
 * These tests keep that phantom out.
 */
class FavoriteToggleParityTest extends PHPUnitTestCase
{
    private function webRoutes(): string
    {
        $path = dirname(__DIR__, 2) . '/routes/web.php';
        $this->assertFileExists($path);
        return (string) file_get_contents($path);
    }

    private function controllerSource(): string
    {
        $path = dirname(__DIR__, 2) . '/app/Controllers/Dashboard/FavoriteController.php';
        $this->assertFileExists($path);
        return (string) file_get_contents($path);
    }

    private function schema(): string
    {
        $path = dirname(__DIR__, 2) . '/database/schema.sql';
        $this->assertFileExists($path);
        return (string) file_get_contents($path);
    }

    public function test_only_the_app_parity_favorites_route_is_registered(): void
    {
        $routes = $this->webRoutes();
        $this->assertSame(1, substr_count($routes, "'/favorites/toggle',"),
            'exactly one app-parity favorites toggle route should exist');
        $this->assertStringNotContainsString('favorites/toggle/{module}', $routes,
            'the php-only {module} variant is a phantom — the app has no such route');
    }

    public function test_controller_targets_url_and_label_columns_never_module(): void
    {
        $src = $this->controllerSource();
        $this->assertStringNotContainsString("module", $src,
            'dashboard_favorites has no module column — the legacy branch was removed');
        $this->assertStringContainsString("'url'", $src);
        $this->assertStringContainsString("'label'", $src);
        $this->assertStringContainsString('pruneToLimit', $src);
    }

    public function test_schema_uses_url_and_label_columns(): void
    {
        $schema = $this->schema();
        $start = strpos($schema, 'CREATE TABLE IF NOT EXISTS `dashboard_favorites`');
        $this->assertNotFalse($start, 'dashboard_favorites table must exist in schema.sql');
        $block = substr($schema, $start, 700);
        $this->assertStringContainsString('`url`', $block);
        $this->assertStringContainsString('`label`', $block);
        $this->assertStringNotContainsString('`module`', $block);
    }
}