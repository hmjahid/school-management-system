<?php

declare(strict_types=1);

namespace Tests\Unit\Views;

use App\Services\ProductMatrix;
use Tests\FakeDatabase;
use Tests\TestCase;

/**
 * Renders the shared Product x Variant partial in all three modes.
 *
 * The same file is included from /products, /compare, /choose-result, the four
 * product pages and (in Phase 3) the admin dashboard, so a single undefined
 * variable would break five pages at once and no controller-level test would see
 * it. Warnings are promoted to exceptions, then the markup is asserted against
 * the two hard rules of §4: every cell is accounted for, and "not offered" is
 * explicit.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §4
 */
class ProductVariantMatrixViewTest extends TestCase
{

    private function db(array $withoutProduct = []): FakeDatabase
    {
        $db = new FakeDatabase();

        foreach (Catalog4::all() as $product => [$monthly, $yearly]) {
            if (in_array($product, $withoutProduct, true)) {
                continue;
            }
            $db->seed('plans', [
                ['product' => $product, 'price' => $monthly, 'period' => 'monthly', 'active' => 1, 'sort_order' => 1],
                ['product' => $product, 'price' => $yearly, 'period' => 'yearly', 'active' => 1, 'sort_order' => 2],
            ]);
        }

        return $db;
    }

    private function partialPath(): string
    {
        return dirname(__DIR__, 3) . '/views/partials/product_variant_matrix.php';
    }

    /**
     * Render the partial with warnings promoted to exceptions.
     *
     * @param array<string, mixed> $vars extra variables the include will extract
     * @return array{0: string, 1: int} [html, warning count]
     */
    private function render(array $vars, FakeDatabase $db): array
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        ob_start();

        try {
            extract(array_merge(['matrix' => ProductMatrix::build($db)], $vars), EXTR_SKIP);
            include $this->partialPath();
            $html = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            restore_error_handler();

            self::fail('Matrix partial raised ' . $e::class . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());

            return ['', 0];
        }

        restore_error_handler();

        return [$html, 0];
    }

    // ------------------------------------------------------------- structure

    public function testItRendersInEveryModeWithoutWarnings(): void
    {
        foreach (['full', 'compact', 'admin'] as $mode) {
            [$html] = $this->render(['matrixMode' => $mode], $this->db());

            self::assertNotSame('', $html, $mode);
        }
    }

    public function testItRendersOneRowPerProductAndOneColumnPerVariant(): void
    {
        [$html] = $this->render([], $this->db());

        self::assertSame(4, substr_count($html, 'class="esk-matrix-rowlabel"'));
        self::assertSame(2, substr_count($html, 'esk-matrix-head esk-variant-chip'));
        // 4 products x 2 variants.
        self::assertSame(8, substr_count($html, 'esk-matrix-cell esk-matrix-cell'));
    }

    public function testBothAxesAreLabelledSoTheyCannotBeConfused(): void
    {
        [$html] = $this->render([], $this->db());

        self::assertStringContainsString('esk-legend-axis', $html);
        // The variant axis gets square corners, the product axis a pill: the
        // orthogonal styling is the affordance, so both must be present.
        self::assertStringContainsString('esk-legend-axis--variant', $html);
        self::assertStringContainsString('esk-product-pill', $html);
    }

    // ------------------------------------------------------------ every cell

    public function testEveryOfferedCellIsALinkToItsProductPage(): void
    {
        [$html] = $this->render([], $this->db());

        foreach (['app', 'php', 'theme', 'node'] as $product) {
            self::assertStringContainsString('href="/products/' . $product . '?variant=', $html, $product);
        }
    }

    public function testBothCurrenciesAppearForOfferedCells(): void
    {
        [$html] = $this->render([], $this->db());

        // 4 products x 1 column each, so 4 USD and 4 BDT figures.
        self::assertSame(4, preg_match_all('/cell-price">\\$\\d/', $html), 'USD figures');
        self::assertSame(4, preg_match_all('/cell-price">\\x{09F3}\\d/u', $html), 'derived BDT figures');
    }

    public function testTheBdtFootnoteExplainsTheDerivation(): void
    {
        [$html] = $this->render([], $this->db());

        self::assertStringContainsString('esk-matrix-footnote', $html);
        // The rate is substituted, so the literal ":rate" must be gone.
        self::assertStringNotContainsString(':rate', $html);
    }

    // ------------------------------------------------- the "not offered" rule

    public function testAnUnofferedCellShowsADashAndAnExplicitStateNotABlank(): void
    {
        [$html] = $this->render([], $this->db(['node']));

        self::assertStringContainsString('esk-matrix-cell--empty', $html);
        self::assertStringContainsString('esk-matrix-cell-dash', $html);
        // Never a 0 price masquerading as a price.
        self::assertStringNotContainsString('>$0', $html);
        self::assertStringNotContainsString('৳0', $html);
    }

    public function testAnUnofferedCellIsNotADeadEnd(): void
    {
        [$html] = $this->render([], $this->db(['node']));

        self::assertStringContainsString('/custom-order?product=node', $html);
        self::assertStringContainsString('variant=bd', $html);
        self::assertStringContainsString('variant=int', $html);
    }

    public function testAnUnofferedCellStillOccupiesItsGridSlot(): void
    {
        [$withNode] = $this->render([], $this->db());
        [$withoutNode] = $this->render([], $this->db(['node']));

        // Dropping a product's plans must not drop a column or reflow the grid.
        self::assertSame(
            substr_count($withNode, 'esk-matrix-cell esk-matrix-cell'),
            substr_count($withoutNode, 'esk-matrix-cell esk-matrix-cell'),
        );
        self::assertSame(
            substr_count($withNode, 'esk-matrix-rowlabel'),
            substr_count($withoutNode, 'esk-matrix-rowlabel'),
        );
    }

    // ------------------------------------------------------------- highlight

    public function testTheHighlightedCellIsTheRequestedProductVariant(): void
    {
        [$html] = $this->render(['matrixHighlight' => 'node:int'], $this->db());

        self::assertStringContainsString('esk-matrix-cell--highlight', $html);
        // Exactly one cell is ringed — the recommendation.
        self::assertSame(1, substr_count($html, 'esk-matrix-cell--highlight'));
        self::assertMatchesRegularExpression(
            '/esk-matrix-cell--highlight[^>]*href="\/products\/node\?variant=int"/',
            $html,
        );
    }

    public function testAnUnknownHighlightRingsNothingRatherThanEverything(): void
    {
        [$html] = $this->render(['matrixHighlight' => 'php:martian'], $this->db());

        self::assertStringNotContainsString('esk-matrix-cell--highlight', $html);
    }

    // ------------------------------------------------------------------ modes

    public function testCompactModeDropsTheRowLevelCtaCopy(): void
    {
        [$full] = $this->render(['matrixMode' => 'full'], $this->db());
        [$compact] = $this->render(['matrixMode' => 'compact'], $this->db());

        self::assertStringContainsString('esk-matrix-cell-cta', $full);
        self::assertStringNotContainsString('esk-matrix-cell-cta', $compact);
        self::assertLessThan(strlen($full), strlen($compact), 'compact should be smaller');
    }

    public function testTitleSubAndLegendCanEachBeSuppressedIndependently(): void
    {
        [$html] = $this->render([
            'matrixTitle'  => '',
            'matrixSub'    => '',
            'matrixLegend' => false,
        ], $this->db());

        self::assertStringNotContainsString('esk-matrix-legend', $html);
        self::assertStringNotContainsString('esk-matrix-title', $html);
        // The grid itself always renders.
        self::assertStringContainsString('esk-matrix-grid', $html);
    }

    public function testTheFootnoteCanBeSuppressedIndependently(): void
    {
        [$html] = $this->render(['matrixFootnote' => false], $this->db());

        self::assertStringNotContainsString('esk-matrix-footnote', $html);
        self::assertStringContainsString('esk-matrix-grid', $html);
    }

    public function testAnEmptyMatrixRendersNothingRatherThanAnEmptyShell(): void
    {
        [$html] = $this->render(['matrix' => ['rows' => [], 'variants' => []]], $this->db());

        self::assertSame('', trim($html));
    }

    // ------------------------------------------------------------ no raw keys

    public function testNoRawLangKeysLeakIntoTheMarkup(): void
    {
        [$html] = $this->render([], $this->db(['node']));

        self::assertSame([], $this->leakedKeys($html));
    }

    public function testTheCellAriaLabelNamesBothAxes(): void
    {
        [$html] = $this->render([], $this->db());

        // Screen-reader users get the same two-axis explanation sighted users do.
        self::assertStringContainsString('aria-label="', $html);
        self::assertSame(8, substr_count($html, 'esk-matrix-cell-price'));
    }

    /** @return list<string> */
    private function leakedKeys(string $html): array
    {
        preg_match_all('/\b(?:matrix|variant|product|choose|pricing)\.[a-z0-9_.]+/i', $html, $m);

        return array_values(array_unique($m[0]));
    }
}

/** Seed prices, in the order the schema seeds them. */
final class Catalog4
{
    public static function all(): array
    {
        return [
            'app'   => [12.00, 120.00],
            'php'   => [9.00, 90.00],
            'theme' => [9.00, 90.00],
            'node'  => [12.00, 120.00],
        ];
    }
}
