<?php
declare(strict_types=1);

namespace Tests\Unit\Admin;

use Tests\TestCase;

/**
 * Guards for admin surfaces that silently corrupted other admin screens.
 *
 * 1. Admin -> Settings used to write `services.*` with hardcoded defaults even
 *    though its form has no such fields, wiping prices set in Admin -> Services.
 *    `services.*` must have exactly one owner.
 * 2. Every gateway whose driver reads a `live_url` setting must expose that
 *    field in the admin form, otherwise it can only be set via .env.
 */
class SettingsAndGatewayConfigTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = dirname(__DIR__, 3);
    }

    private function read(string $relative): string
    {
        $path = $this->root . '/' . $relative;
        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }

    public function test_settings_controller_does_not_write_service_prices(): void
    {
        $controller = $this->read('app/Controllers/Admin/SettingsController.php');

        $this->assertStringNotContainsString(
            'services.deploy_app',
            $controller,
            'services.* is owned by ServiceController; writing it here resets admin-set prices'
        );
        $this->assertStringNotContainsString('services.care_monthly', $controller);

        // ...and the fields it DOES own must still be persisted.
        $this->assertStringContainsString("'site.name'", $controller);
        $this->assertStringContainsString("'support.widget_enabled'", $controller);
    }

    public function test_service_controller_still_owns_service_prices(): void
    {
        $controller = $this->read('app/Controllers/Admin/ServiceController.php');

        $this->assertStringContainsString('services.deploy_app', $controller);
        $this->assertStringContainsString('services.deploy_php_theme', $controller);
        $this->assertStringContainsString('services.care_monthly', $controller);
    }

    public function test_every_gateway_with_a_live_url_setting_exposes_it_in_admin(): void
    {
        $gateways = $this->read('app/Controllers/Admin/GatewayController.php');

        // Drivers that read a `live_url` setting to resolve their base URL.
        $drivers = glob($this->root . '/app/Gateways/*Gateway.php') ?: [];
        $this->assertNotEmpty($drivers);

        $withLiveUrl = [];
        foreach ($drivers as $driver) {
            $src = (string) file_get_contents($driver);
            if (str_contains($src, "live_url") && str_contains($src, "setting")) {
                $withLiveUrl[] = basename($driver, '.php');
            }
        }

        // Every driver that honours live_url must be a key in the admin form map.
        foreach ($withLiveUrl as $name) {
            $key = strtolower(str_replace('Gateway', '', $name));
            $this->assertMatchesRegularExpression(
                "/'" . preg_quote($key, '/') . "'\s*=>\s*\[.*?'live_url'/s",
                $gateways,
                "Gateway {$key} reads a live_url setting but the admin form cannot set it"
            );
        }

        // And no gateway may be missing its live_url field.
        $this->assertStringContainsString("'stripe'", $gateways);
        $this->assertStringContainsString("'paypal'", $gateways);
        $this->assertStringContainsString("'paddle'", $gateways);
    }

    public function test_custom_request_email_template_is_editable(): void
    {
        $controller = $this->read('app/Controllers/Admin/EmailTemplateController.php');

        $this->assertStringContainsString(
            "'custom_request'",
            $controller,
            'views/emails/custom_request.php is sent by CustomOrderController but was not editable'
        );
        $this->assertFileExists($this->root . '/views/emails/custom_request.php');
    }

    public function test_all_four_product_dashboards_are_configurable(): void
    {
        $controller = $this->read('app/Controllers/Admin/SettingsController.php');
        $view = $this->read('views/admin/settings.php');
        $dashboard = $this->read('views/admin/dashboard.php');

        foreach (['app', 'php', 'theme', 'node'] as $product) {
            $this->assertStringContainsString("products.dashboards.{$product}", $controller, "missing settings write for {$product}");
            $this->assertStringContainsString("products_dashboards_{$product}", $view, "missing settings field for {$product}");
            $this->assertStringContainsString("products.dashboards.{$product}", $dashboard, "missing dashboard link for {$product}");
        }
    }

    public function test_subscription_lifecycle_routes_are_registered(): void
    {
        $routes = $this->read('routes/web.php');

        // Admin pause/resume/cancel.
        $this->assertStringContainsString('SubscriptionController::class, \'setStatus\'', $routes);
        // Customer self-serve cancel/resume.
        $this->assertStringContainsString('AccountLicense::class, \'subscription\'', $routes);
    }
}
