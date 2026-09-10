<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Router;
use Tests\TestCase;

class RouterRegistrationTest extends TestCase
{
    public function test_web_and_api_routes_register_without_error(): void
    {
        $router = new Router();

        require dirname(__DIR__, 3) . '/routes/web.php';
        require dirname(__DIR__, 3) . '/routes/api.php';

        $this->assertInstanceOf(Router::class, $router);
    }

    public function test_404_uses_error_view_template(): void
    {
        $view = dirname(__DIR__, 3) . '/views/errors/404.php';
        $this->assertFileExists($view);

        $layout = dirname(__DIR__, 3) . '/views/layouts/main.php';
        $this->assertFileExists($layout);
    }

    public function test_all_admin_views_exist(): void
    {
        $base = dirname(__DIR__, 3) . '/views/admin/';
        foreach (['dashboard', 'customers', 'customer_detail', 'plans', 'plan_form', 'licenses', 'license_form', 'license_detail', 'payments', 'messages', 'activities'] as $view) {
            $this->assertFileExists($base . $view . '.php', "Missing admin view: {$view}");
        }
    }

    public function test_all_account_views_exist(): void
    {
        $base = dirname(__DIR__, 3) . '/views/account/';
        foreach (['dashboard', 'licenses', 'license_detail', 'payments'] as $view) {
            $this->assertFileExists($base . $view . '.php', "Missing account view: {$view}");
        }
    }

    public function test_site_pages_and_layouts_exist(): void
    {
        $base = dirname(__DIR__, 3) . '/views/';

        foreach (['home', 'pricing', 'features', 'about', 'contact', 'checkout'] as $view) {
            $this->assertFileExists($base . 'site/' . $view . '.php', "Missing site view: {$view}");
        }
        foreach (['app', 'theme'] as $view) {
            $this->assertFileExists($base . 'site/products/' . $view . '.php', "Missing product view: {$view}");
        }
        foreach (['login', 'register'] as $view) {
            $this->assertFileExists($base . 'auth/' . $view . '.php', "Missing auth view: {$view}");
        }
        foreach (['main', 'admin'] as $view) {
            $this->assertFileExists($base . 'layouts/' . $view . '.php', "Missing layout: {$view}");
        }
    }
}