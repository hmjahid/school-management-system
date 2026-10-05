<?php

declare(strict_types=1);

namespace Tests\Unit\Views;

use App\Services\ProductMatrix;
use App\Services\VariantResolver;
use Tests\FakeDatabase;
use Tests\TestCase;

/**
 * Renders every public page Phase 2 touched, with PHP warnings promoted to
 * exceptions.
 *
 * These are plain PHP templates with no compiler, so an undefined variable or a
 * renamed controller key is invisible until a visitor loads the page. Phase 2
 * rewrote /pricing, /choose, /choose-result, /compare, /products and all four
 * product pages, so this locks the page <-> controller contract for each of them.
 *
 * The `__()` helper takes params WITHOUT a leading colon (`['n' => 2]`, not
 * `[':n' => 2]`) and returns the raw key when a key is missing — both failure
 * modes are asserted against here rather than trusted.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §4
 */
class SitePagesRenderTest extends TestCase
{

    private FakeDatabase $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new FakeDatabase();
        foreach (['app' => 12.00, 'php' => 9.00, 'theme' => 9.00, 'node' => 12.00] as $product => $monthly) {
            $this->db->seed('plans', [
                ['id' => $product . '-m', 'product' => $product, 'name' => 'Monthly', 'period' => 'monthly', 'price' => $monthly, 'active' => 1, 'sort_order' => 1, 'description' => $product . ' monthly', 'features' => '["One school", "All modules"]'],
                ['id' => $product . '-y', 'product' => $product, 'name' => 'Yearly', 'period' => 'yearly', 'price' => $monthly * 10, 'active' => 1, 'sort_order' => 2, 'description' => $product . ' yearly', 'features' => '["One school", "All modules"]'],
            ]);
        }
    }

    private function sitePath(string $template): string
    {
        return dirname(__DIR__, 3) . '/views/site/' . $template . '.php';
    }

    /**
     * Render one site template with warnings promoted to exceptions.
     *
     * @param array<string, mixed> $data exactly the keys the controller passes
     * @return string rendered HTML
     */
    private function render(string $template, array $data): string
    {
        $path = $this->sitePath($template);
        self::assertFileExists($path);

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        ob_start();

        try {
            extract($data, EXTR_SKIP);
            include $path;
            $html = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            restore_error_handler();

            self::fail($template . ' raised ' . $e::class . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());

            return '';
        }

        restore_error_handler();

        return $html;
    }

    /** Data exactly as {@see \App\Controllers\Site\HomeController} passes it. */
    private function homeControllerData(array $overrides = []): array
    {
        return array_merge([
            'appPlans'    => $this->plans('app'),
            'themePlans'  => $this->plans('theme'),
            'phpPlans'    => $this->plans('php'),
            'nodePlans'   => $this->plans('node'),
            'matrix'      => ProductMatrix::build($this->db),
            'recentPosts' => [],
            'cmsPage'     => null,
        ], $overrides);
    }

    private function plans(string $product): array
    {
        return array_values(array_filter(
            $this->db->rows('plans'),
            static fn (array $row): bool => $row['product'] === $product,
        ));
    }

    /**
     * Lang keys that reached the page.
     *
     * `__()` returns the key verbatim when it is missing, so a missing key shows
     * up as literal `matrix.foo` in the markup. Attributes and tags are stripped
     * first: `hover:text-blue-600` and `el.closest` are not lang keys.
     *
     * @return list<string>
     */
    private function leakedKeys(string $html): array
    {
        $text = (string) preg_replace('/<[^>]*>/', ' ', $html);
        preg_match_all('/\b[a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+\b/i', $text, $m);

        $known = array_keys(require dirname(__DIR__, 3) . '/lang/en.php');
        $found = array_values(array_intersect(array_unique($m[0]), $known));

        sort($found);

        return $found;
    }

    /**
     * Placeholder tokens still visible in the page's *text* (not its attributes).
     *
     * @return list<string>
     */
    private function leakedPlaceholders(string $html): array
    {
        $text = (string) preg_replace('/<[^>]*>/', ' ', $html);
        preg_match_all('/(?<![\w-]):[a-z_][a-z0-9_]*/i', $text, $m);

        return array_values(array_unique($m[0]));
    }

    /** The markup of one `<section id="pricing">`, for variant assertions. */
    private function pricingSection(string $html): string
    {
        $start = (int) strpos($html, 'id="pricing"');
        self::assertNotSame(false, $start, 'no pricing section');

        $end = (int) strpos($html, '</section>', $start);

        return substr($html, $start, $end - $start);
    }

    // ------------------------------------------------------------- each page

    public function testHomeRenders(): void
    {
        $html = $this->render('home', $this->homeControllerData());

        self::assertGreaterThan(5000, strlen($html));
        self::assertSame([], $this->leakedKeys($html));
    }

    public function testProductsIndexRenders(): void
    {
        $html = $this->render('products_index', [
            'matrix'  => ProductMatrix::build($this->db),
            'cmsPage' => null,
        ]);

        self::assertStringContainsString('esk-matrix-grid', $html);
        self::assertSame([], $this->leakedKeys($html));
    }

    public function testPricingRenders(): void
    {
        $data = $this->homeControllerData();
        $html = $this->render('pricing', $data);

        self::assertGreaterThan(3000, strlen($html));
        self::assertSame([], $this->leakedKeys($html));
    }

    public function testCompareRenders(): void
    {
        $html = $this->render('compare', [
            'matrix'  => ProductMatrix::build($this->db),
            'cmsPage' => null,
        ]);

        self::assertStringContainsString('esk-matrix-grid', $html);
        self::assertSame([], $this->leakedKeys($html));
    }

    public function testChooseRenders(): void
    {
        $html = $this->render('choose', [
            'cmsPage'   => null,
            'markets'   => VariantResolver::all(),
            'sizes'     => ['small', 'medium', 'large'],
            'comforts'  => ['non_technical', 'some', 'technical'],
            'hostings'  => ['shared', 'vps', 'cloud', 'wordpress'],
            'stacks'    => ['any', 'php', 'javascript'],
            'priorities'=> ['budget', 'features', 'control'],
        ]);

        self::assertStringContainsString('name="market"', $html);
        self::assertSame([], $this->leakedKeys($html));
    }

    public function testChooseResultRenders(): void
    {
        $html = $this->render('choose_result', $this->chooseResultData());

        self::assertStringContainsString('esk-matrix-grid', $html);
        self::assertSame([], $this->leakedKeys($html));
    }

    public function testEveryProductPageRendersInBothVariants(): void
    {
        foreach (CatalogCodes::all() as $code) {
            foreach (VariantResolver::all() as $variant) {
                $html = $this->render('products/' . $code, [
                    'plans'          => $this->plans($code),
                    'matrix'         => ProductMatrix::build($this->db),
                    'productVariant' => $variant,
                    'productCode'    => $code,
                    'productPath'    => '/products/' . $code,
                    'cmsPage'        => null,
                ]);

                self::assertGreaterThan(3000, strlen($html), $code . '/' . $variant);
                self::assertStringContainsString('esk-variant-switch', $html, $code . '/' . $variant);
                self::assertSame([], $this->leakedKeys($html), $code . '/' . $variant);
            }
        }
    }

    private function chooseResultData(array $overrides = []): array
    {
        $answers = [
            'market'   => VariantResolver::BD,
            'size'     => 'medium',
            'comfort'  => 'some',
            'hosting'  => 'vps',
            'stack'    => 'any',
            'priority' => 'features',
        ];
        $result = \App\Services\ProductRecommender::recommend($answers);

        return array_merge([
            'cmsPage'    => null,
            'result'     => $result,
            'candidates' => \App\Services\ProductRecommender::candidates(),
            'answer'     => $answers,
            'matrix'     => ProductMatrix::build($this->db),
        ], $overrides);
    }

    // ------------------------------------------------- Phase 2 page contracts

    /**
     * The /pricing bug this phase fixes: a four-product catalogue rendered as
     * three hand-written sections, so the Node.js product had no card at all.
     */
    public function testPricingShowsAllFourProducts(): void
    {
        $html = $this->render('pricing', $this->homeControllerData());

        foreach (['app', 'php', 'theme', 'node'] as $code) {
            self::assertStringContainsString('href="/products/' . $code . '"', $html, $code);
        }
        self::assertSame(4, substr_count($html, 'data-plan-card>'), 'one card per product');
    }

    public function testPricingShipsBothCurrenciesForEveryPlanCard(): void
    {
        $html = $this->render('pricing', $this->homeControllerData());

        self::assertSame(4, substr_count($html, 'data-price-monthly-bd='));
        self::assertSame(4, substr_count($html, 'data-price-monthly-int='));
        self::assertSame(4, substr_count($html, 'data-price-yearly-bd='));
        self::assertSame(4, substr_count($html, 'data-price-yearly-int='));
    }

    /**
     * One price list: the BDT and USD figures of a card must derive from the same
     * `plans` row, so a card can never quote two different amounts.
     */
    public function testPricingCardQuotesOnePlanPriceInTwoCurrencies(): void
    {
        $html = $this->render('pricing', $this->homeControllerData());

        preg_match('/data-price-monthly-int="([^"]+)".*?data-price-monthly-bd="([^"]+)"/s', $html, $m);
        self::assertNotEmpty($m, 'no card carries both currencies');

        $int = (float) ltrim($m[1], '$');
        self::assertGreaterThan(0.0, $int, 'no USD figure captured');

        // The BDT figure is the same USD price converted, not a second price list.
        self::assertEqualsWithDelta(
            VariantResolver::toBdt($int),
            (float) str_replace([',', '৳'], '', $m[2]),
            1.0,
        );
    }

    public function testPricingHasBothToggles(): void
    {
        $html = $this->render('pricing', $this->homeControllerData());

        self::assertStringContainsString('data-variant-toggle', $html);
        self::assertStringContainsString('data-billing-toggle', $html);
        self::assertStringContainsString('data-variant="bd"', $html);
        self::assertStringContainsString('data-variant="int"', $html);
    }

    public function testCompareLeadsWithTheMatrix(): void
    {
        $html = $this->render('compare', ['matrix' => ProductMatrix::build($this->db), 'cmsPage' => null]);

        $matrixAt = (int) strpos($html, 'esk-matrix-grid');
        $tableAt = (int) strpos($html, '<table');

        self::assertNotSame(false, $matrixAt, 'no matrix on /compare');
        self::assertNotSame(false, $tableAt, 'no competitor table on /compare');
        self::assertGreaterThan($matrixAt, $tableAt, 'the competitor table must come after the Eskoofy matrix');
    }

    // -------------------------------------------------- the __() contract

    /**
     * `__('key', [':n' => 2])` silently does nothing — the helper prefixes each
     * param with a colon itself, so `':n'` becomes `'::n'`. A literal ':n' in the
     * output is the symptom.
     */
    public function testNoLiteralPlaceholdersReachThePage(): void
    {
        foreach ([
            'products_index' => ['matrix' => ProductMatrix::build($this->db), 'cmsPage' => null],
            'pricing'        => $this->homeControllerData(),
            'choose_result'  => $this->chooseResultData(),
        ] as $template => $data) {
            $html = $this->render((string) $template, $data);

            self::assertSame([], $this->leakedPlaceholders($html), $template . ' leaks a placeholder');
        }
    }

    /**
     * `__()` returns the key itself when it is missing, so an untranslated key
     * ships as literal 'matrix.foo' in customer-facing markup.
     */
    public function testNoUntranslatedLangKeysReachThePage(): void
    {
        $langKeys = array_keys(require dirname(__DIR__, 3) . '/lang/en.php');

        foreach ([
            'products_index' => ['matrix' => ProductMatrix::build($this->db), 'cmsPage' => null],
            'pricing'        => $this->homeControllerData(),
            'choose_result'  => $this->chooseResultData(),
        ] as $template => $data) {
            $html = $this->render((string) $template, $data);

            self::assertSame([], $this->leakedKeys($html), $template . ': ' . implode(', ', $langKeys));
        }
    }

    // --------------------------------------------------- variant propagation

    public function testProductPagePricesFollowTheRequestedVariant(): void
    {
        $bd = $this->render('products/app', [
            'plans'          => $this->plans('app'),
            'matrix'         => ProductMatrix::build($this->db),
            'productVariant' => VariantResolver::BD,
            'productCode'    => 'app',
            'productPath'    => '/products/app',
            'cmsPage'        => null,
        ]);
        $int = $this->render('products/app', [
            'plans'          => $this->plans('app'),
            'matrix'         => ProductMatrix::build($this->db),
            'productVariant' => VariantResolver::INT,
            'productCode'    => 'app',
            'productPath'    => '/products/app',
            'cmsPage'        => null,
        ]);

        // Scoped to the pricing section: the matrix above it always shows both
        // columns, which is the point of the matrix.
        $bdPlans = $this->pricingSection($bd);
        $intPlans = $this->pricingSection($int);

        self::assertStringContainsString('৳', $bdPlans);
        self::assertStringNotContainsString('$', $bdPlans);
        self::assertStringContainsString('$', $intPlans);
        self::assertStringNotContainsString('৳', $intPlans);
    }

    public function testProductPageMarksTheActiveVariantAsCurrent(): void
    {
        foreach (VariantResolver::all() as $variant) {
            $html = $this->render('products/php', [
                'plans'          => $this->plans('php'),
                'matrix'         => ProductMatrix::build($this->db),
                'productVariant' => $variant,
                'productCode'    => 'php',
                'productPath'    => '/products/php',
                'cmsPage'        => null,
            ]);

            self::assertSame(
                1,
                preg_match('/data-variant="' . $variant . '"[^>]*aria-current="true"|aria-current="true"[^>]*data-variant="' . $variant . '"/', $html),
                $variant,
            );
        }
    }

    public function testProductPageRingsItsOwnMatrixCell(): void
    {
        $html = $this->render('products/node', [
            'plans'          => $this->plans('node'),
            'matrix'         => ProductMatrix::build($this->db),
            'productVariant' => VariantResolver::INT,
            'productCode'    => 'node',
            'productPath'    => '/products/node',
            'cmsPage'        => null,
        ]);

        self::assertSame(1, substr_count($html, 'esk-matrix-cell--highlight'));
        self::assertStringContainsString('/products/node?variant=int', $html);
    }

    public function testChooseResultRingsTheRecommendedCell(): void
    {
        $data = $this->chooseResultData(['market' => VariantResolver::BD]);
        $result = $data['result'];
        $html = $this->render('choose_result', $data);

        self::assertStringContainsString('esk-matrix-cell--highlight', $html);
        self::assertMatchesRegularExpression(
            '/esk-matrix-cell--highlight.*?variant=' . preg_quote($result['variant'], '/') . '/s',
            $html,
        );
    }
}

/** The four catalog codes, so the test cannot drift from {@see \App\Services\Catalog}. */
final class CatalogCodes
{
    public static function all(): array
    {
        return array_keys(\App\Services\Catalog::all());
    }
}
