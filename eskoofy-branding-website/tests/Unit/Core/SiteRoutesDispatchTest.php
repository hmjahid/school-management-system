<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Database;
use App\Core\Router;
use Tests\FakeDatabase;
use Tests\TestCase;

/**
 * Dispatches the real routes through the real controllers with a fake database.
 *
 * {@see SitePagesRenderTest} renders the templates but has to build each
 * controller's data array by hand, so it cannot catch a controller that stops
 * passing a key the view needs. This closes that gap: it goes through
 * `routes/web.php` and the controllers themselves, which is the only place a
 * rename like `$matrix` -> `$productMatrix` can hide.
 */
class SiteRoutesDispatchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $db = new FakeDatabase();
        foreach (['app' => 12.00, 'php' => 9.00, 'theme' => 9.00, 'node' => 12.00] as $product => $monthly) {
            $db->seed('plans', [
                [
                    'product' => $product, 'name' => 'Monthly', 'period' => 'monthly',
                    'price' => $monthly, 'active' => 1, 'sort_order' => 1,
                    'description' => $product . ' monthly subscription',
                    'features' => '["One school", "All modules"]',
                ],
                [
                    'product' => $product, 'name' => 'Yearly', 'period' => 'yearly',
                    'price' => $monthly * 10, 'active' => 1, 'sort_order' => 2,
                    'description' => $product . ' yearly subscription',
                    'features' => '["One school", "All modules"]',
                ],
            ]);
        }

        Database::setInstance($db);

        // In the CLI SAPI http_response_code() reads as false until it is set
        // once, so give the redirect/404 assertions a real baseline.
        if (http_response_code() === false) {
            http_response_code(200);
        }
    }

    protected function tearDown(): void
    {
        http_response_code(200);
        Database::setInstance(null);
        parent::tearDown();
    }

    /**
     * Dispatch a URI and capture the rendered page.
     *
     * Warnings are promoted to exceptions, because a controller that renders a
     * missing variable is exactly the regression this test exists to catch.
     */
    private function get(string $uri, array $query = []): string
    {
        $previous = $_GET;
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        $_GET = $query;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = $uri . ($query !== [] ? '?' . http_build_query($query) : '');

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        $level = ob_get_level();
        ob_start();

        try {
            $router = new Router();
            require dirname(__DIR__, 3) . '/routes/web.php';
            $router->dispatch('GET', $_SERVER['REQUEST_URI']);

            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            $failure = $uri . ' raised ' . $e::class . ': ' . $e->getMessage()
                . ' @ ' . basename($e->getFile()) . ':' . $e->getLine();
        } finally {
            // Unwind only what this method opened — PHPUnit has buffers of its own.
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            restore_error_handler();
            $_GET = $previous;
            $_SERVER['REQUEST_METHOD'] = $method;
            $_SERVER['REQUEST_URI'] = $requestUri;
        }

        self::fail($failure);

        return '';
    }

    public function test_the_products_page_renders(): void
    {
        $html = $this->get('/products');

        self::assertStringContainsString('esk-matrix-grid', $html);
        self::assertStringContainsString('School App', $html);
        self::assertStringContainsString('Node.js', $html);
    }

    public function test_every_product_page_renders(): void
    {
        foreach (['app', 'php', 'theme', 'node'] as $product) {
            $html = $this->get('/products/' . $product);

            self::assertGreaterThan(3000, strlen($html), $product);
            self::assertStringContainsString('esk-variant-switch', $html, $product);
            self::assertStringContainsString('esk-matrix-grid', $html, $product);
        }
    }

    public function test_a_product_page_honours_the_variant_query_param(): void
    {
        $bd = $this->get('/products/app', ['variant' => 'bd']);
        $int = $this->get('/products/app', ['variant' => 'int']);

        self::assertStringContainsString('৳', $bd);
        self::assertStringContainsString('aria-current="true"', $bd);

        self::assertStringContainsString('$', $int);
    }

    public function test_an_invalid_variant_falls_back_rather_than_erroring(): void
    {
        $html = $this->get('/products/app', ['variant' => 'klingon']);

        self::assertGreaterThan(3000, strlen($html));
        self::assertStringContainsString('esk-variant-switch', $html);
    }

    public function test_an_unknown_product_slug_is_not_found(): void
    {
        $this->get('/products/spreadsheet');

        self::assertSame(404, http_response_code());
    }

    public function test_the_pricing_page_renders_all_four_products(): void
    {
        $html = $this->get('/pricing');

        self::assertSame(4, substr_count($html, 'data-plan-card>'));
    }

    public function test_the_compare_page_renders(): void
    {
        $html = $this->get('/compare');

        self::assertStringContainsString('esk-matrix-grid', $html);
    }

    public function test_the_choose_page_renders_all_six_questions(): void
    {
        $html = $this->get('/choose');

        foreach (['market', 'size', 'comfort', 'hosting', 'stack', 'priority'] as $field) {
            self::assertStringContainsString('name="' . $field . '"', $html, $field);
        }
    }

    public function test_the_choose_result_renders_for_a_complete_quiz(): void
    {
        $html = $this->get('/choose', [
            'market'   => 'bd',
            'size'     => 'medium',
            'comfort'  => 'some',
            'hosting'  => 'vps',
            'stack'    => 'any',
            'priority' => 'features',
        ]);

        self::assertStringContainsString('esk-matrix-grid', $html);
        self::assertStringContainsString('esk-matrix-cell--highlight', $html);
    }

    /**
     * A missing required answer makes `Controller::validate()` redirect and
     * `exit`, which cannot be exercised in-process. The contract it enforces is
     * the rule set, so that is what is asserted here.
     */
    public function test_the_market_question_is_required(): void
    {
        $rules = \App\Controllers\Site\ChooseController::RULES;

        self::assertArrayHasKey('market', $rules);
        self::assertStringStartsWith('required', $rules['market']);
        self::assertStringContainsString('bd', $rules['market']);
        self::assertStringContainsString('int', $rules['market']);
    }

    public function test_every_quiz_option_has_a_translation(): void
    {
        $lang = require dirname(__DIR__, 3) . '/lang/en.php';

        foreach (\App\Controllers\Site\ChooseController::OPTIONS as $question => $options) {
            self::assertArrayHasKey('choose.q_' . $question, $lang, 'missing question label: ' . $question);

            foreach ($options as $option) {
                self::assertArrayHasKey(
                    'choose.opt.' . $question . '.' . $option,
                    $lang,
                    'missing option label: ' . $question . '/' . $option,
                );
            }
        }
    }
}
