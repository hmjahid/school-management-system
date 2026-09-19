<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Router;
use App\Core\View;
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

        foreach (['home', 'pricing', 'features', 'about', 'contact', 'checkout', 'blog', 'post'] as $view) {
            $this->assertFileExists($base . 'site/' . $view . '.php', "Missing site view: {$view}");
        }
        foreach (['app', 'theme', 'php'] as $view) {
            $this->assertFileExists($base . 'site/products/' . $view . '.php', "Missing product view: {$view}");
        }
        foreach (['login', 'register'] as $view) {
            $this->assertFileExists($base . 'auth/' . $view . '.php', "Missing auth view: {$view}");
        }
        foreach (['main', 'admin'] as $view) {
            $this->assertFileExists($base . 'layouts/' . $view . '.php', "Missing layout: {$view}");
        }
    }

    public function test_blog_routes_registered_in_correct_order(): void
    {
        $router = new Router();
        require dirname(__DIR__, 3) . '/routes/web.php';

        $routes = $router->getRoutes();
        $paths = array_map(fn ($r) => $r['path'], $routes);

        $this->assertContains('/blog', $paths);
        $this->assertContains('/blog/category/{slug}', $paths);
        $this->assertContains('/blog/{slug}', $paths);

        // /blog/category/{slug} must be registered before /blog/{slug} so the
        // router doesn't treat 'category' as a slug.
        $categoryIdx = array_search('/blog/category/{slug}', $paths, true);
        $slugIdx = array_search('/blog/{slug}', $paths, true);
        $this->assertNotFalse($categoryIdx);
        $this->assertNotFalse($slugIdx);
        $this->assertLessThan($slugIdx, $categoryIdx);
    }

    public function test_admin_post_routes_registered(): void
    {
        $router = new Router();
        require dirname(__DIR__, 3) . '/routes/web.php';

        $paths = array_map(fn ($r) => $r['path'], $router->getRoutes());

        $this->assertContains('/admin/posts', $paths);
        $this->assertContains('/admin/posts/create', $paths);
        $this->assertContains('/admin/posts/{id}/edit', $paths);
        $this->assertContains('/admin/posts/{id}/delete', $paths);
        $this->assertContains('/admin/post-categories', $paths);
        $this->assertContains('/admin/post-categories/create', $paths);
        $this->assertContains('/admin/post-categories/{id}/edit', $paths);
        $this->assertContains('/admin/post-categories/{id}/delete', $paths);
    }

    public function test_geo_language_route_registered_before_switch(): void
    {
        $router = new Router();
        require dirname(__DIR__, 3) . '/routes/web.php';

        $paths = array_map(fn ($r) => $r['path'], $router->getRoutes());

        $this->assertContains('/language/geo', $paths);
        $this->assertContains('/language/{locale}', $paths);

        $geoIdx = array_search('/language/geo', $paths, true);
        $switchIdx = array_search('/language/{locale}', $paths, true);
        $this->assertLessThan($switchIdx, $geoIdx);
    }

    public function test_admin_post_views_exist(): void
    {
        $base = dirname(__DIR__, 3) . '/views/admin/';
        foreach (['posts', 'post_form', 'post_categories', 'post_category_form'] as $view) {
            $this->assertFileExists($base . $view . '.php', "Missing admin view: {$view}");
        }
    }

    public function test_checkout_status_and_webhook_routes_registered(): void
    {
        $router = new Router();
        require dirname(__DIR__, 3) . '/routes/web.php';

        $paths = array_map(fn ($r) => $r['path'], $router->getRoutes());

        $this->assertContains('/checkout/status/{reference}', $paths);
        $this->assertContains('/webhooks/{gateway}', $paths);
    }

    public function test_payment_status_view_exists(): void
    {
        $resolved = View::resolve('site.payment-status');
        $this->assertFileExists($resolved, 'site.payment-status must resolve to an existing view');
        $this->assertStringEndsWith('/views/site/payment_status.php', $resolved);
    }

    public function test_admin_subscriptions_view_and_route_exist(): void
    {
        $router = new Router();
        require dirname(__DIR__, 3) . '/routes/web.php';

        $paths = array_map(fn ($r) => $r['path'], $router->getRoutes());
        $this->assertContains('/admin/subscriptions', $paths);

        $this->assertFileExists(dirname(__DIR__, 3) . '/views/admin/subscriptions.php');
    }

    public function test_subscription_pricing_keys_present(): void
    {
        $en = (string) file_get_contents(dirname(__DIR__, 3) . '/lang/en.php');
        foreach (['pricing.monthly_label', 'pricing.yearly_badge', 'pricing.most_popular', 'checkout.method_note_bd', 'checkout.status_paid'] as $key) {
            $this->assertStringContainsString("'{$key}'", $en, "missing subscription copy key: {$key}");
        }
    }

    public function test_compare_route_and_view_exist(): void
    {
        $router = new Router();
        require dirname(__DIR__, 3) . '/routes/web.php';

        $paths = array_map(fn ($r) => $r['path'], $router->getRoutes());
        $this->assertContains('/compare', $paths);

        $this->assertFileExists(dirname(__DIR__, 3) . '/views/site/compare.php');
    }

    public function test_site_js_and_role_tab_markup_exist(): void
    {
        $this->assertFileExists(dirname(__DIR__, 3) . '/public/js/site.js');

        $features = (string) file_get_contents(dirname(__DIR__, 3) . '/views/site/features.php');
        $this->assertStringContainsString('data-role-tabs', $features);
        $this->assertStringContainsString('data-role-panel="teacher"', $features);
        $this->assertStringContainsString('data-role-panel="parent"', $features);
    }
}