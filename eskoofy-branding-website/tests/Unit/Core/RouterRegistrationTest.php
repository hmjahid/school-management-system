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
        foreach (['dashboard', 'customers', 'customer_detail', 'plans', 'plan_form', 'licenses', 'license_form', 'license_detail', 'payments', 'messages', 'activities', 'pages', 'page_form', 'custom_requests'] as $view) {
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

        foreach (['home', 'products_index', 'pricing', 'features', 'about', 'contact', 'checkout', 'blog', 'post', 'legal', 'choose', 'choose_result', 'custom_order'] as $view) {
            $this->assertFileExists($base . 'site/' . $view . '.php', "Missing site view: {$view}");
        }
        foreach (['app', 'theme', 'php', 'node'] as $view) {
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

    public function test_cms_quiz_and_custom_order_routes_registered(): void
    {
        $router = new Router();
        require dirname(__DIR__, 3) . '/routes/web.php';

        $paths = array_map(fn ($r) => $r['path'], $router->getRoutes());

        foreach (['/choose', '/custom-order', '/admin/pages', '/admin/pages/create', '/admin/pages/{id}/edit', '/admin/pages/{id}/delete', '/admin/custom-requests', '/admin/custom-requests/{id}/read', '/admin/custom-requests/{id}/status', '/admin/custom-requests/{id}/delete'] as $path) {
            $this->assertContains($path, $paths, "Missing route: {$path}");
        }
    }

    public function test_cms_partial_exists(): void
    {
        $this->assertFileExists(dirname(__DIR__, 3) . '/views/site/partials/cms_block.php');
        $this->assertFileExists(dirname(__DIR__, 3) . '/views/emails/custom_request.php');
    }

    public function test_site_js_and_role_tab_markup_exist(): void
    {
        $this->assertFileExists(dirname(__DIR__, 3) . '/public/js/site.js');

        $features = (string) file_get_contents(dirname(__DIR__, 3) . '/views/site/features.php');
        $this->assertStringContainsString('data-role-tabs', $features);
        $this->assertStringContainsString('data-role-panel="teacher"', $features);
        $this->assertStringContainsString('data-role-panel="parent"', $features);
    }

    // -------------------------------------------- Phase 2: product x variant

    /**
     * `/products` must be registered *before* `/products/{slug}`, or the slug
     * route swallows it and the taxonomy page 404s.
     */
    public function test_products_index_route_precedes_the_product_slug_route(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3) . '/routes/web.php');

        // Only real registrations count: the file also explains this ordering in
        // a comment, which mentions the slug route first.
        preg_match_all('/^\$router->get\(\'([^\']+)\'/m', $source, $matches);
        $paths = $matches[1];

        $index = array_search('/products', $paths, true);
        $slug = array_search('/products/{slug}', $paths, true);

        $this->assertNotFalse($index, '/products route is missing');
        $this->assertNotFalse($slug, '/products/{slug} route is missing');
        $this->assertLessThan($slug, $index, '/products must be registered before /products/{slug}');
    }

    public function test_the_shared_matrix_partial_exists_in_every_mode(): void
    {
        $partial = dirname(__DIR__, 3) . '/views/partials/product_variant_matrix.php';
        $this->assertFileExists($partial);

        $source = (string) file_get_contents($partial);
        foreach (['full', 'compact', 'admin'] as $mode) {
            $this->assertStringContainsString("'{$mode}'", $source, "matrix mode {$mode} is not handled");
        }
    }

    /**
     * The single-product market-build switcher is a second shared partial; a
     * broken path here would 500 on all four product pages at once.
     */
    public function test_the_product_variant_switch_partial_exists(): void
    {
        $this->assertFileExists(dirname(__DIR__, 3) . '/views/partials/product_variant_switch.php');
    }

    public function test_every_product_page_renders_the_matrix_and_the_switcher(): void
    {
        $base = dirname(__DIR__, 3) . '/views/site/products/';

        foreach (['app', 'php', 'theme', 'node'] as $product) {
            $source = (string) file_get_contents($base . $product . '.php');

            $this->assertStringContainsString('partials.product_variant_switch', $source, $product);
            $this->assertStringContainsString('partials.product_variant_matrix', $source, $product);
            $this->assertStringContainsString('$productVariant', $source, $product);
        }
    }

    public function test_choose_result_and_compare_render_the_matrix(): void
    {
        $base = dirname(__DIR__, 3) . '/views/site/';

        foreach (['choose_result', 'compare', 'products_index'] as $view) {
            $source = (string) file_get_contents($base . $view . '.php');
            $this->assertStringContainsString('partials.product_variant_matrix', $source, $view);
        }
    }
}
