<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Catalog;
use App\Services\ProductMatrix;
use App\Services\VariantResolver;
use Tests\FakeDatabase;
use Tests\TestCase;

/**
 * The Product x Variant matrix is the spine of Phase 2: the same 4x2 grid is
 * rendered on /products, /pricing, /compare, /choose and (later) the admin
 * dashboard, so its shape and its "not offered" rule have to be exact.
 *
 * See docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §4.
 */
class ProductMatrixTest extends TestCase
{
    private function db(): FakeDatabase
    {
        $db = new FakeDatabase();

        // The four seeded products, monthly + yearly, as in database/schema.sql.
        $db->seed('plans', [
            ['product' => 'app', 'price' => 12.00, 'active' => 1, 'sort_order' => 1],
            ['product' => 'app', 'price' => 120.00, 'active' => 1, 'sort_order' => 2],
            ['product' => 'php', 'price' => 9.00, 'active' => 1, 'sort_order' => 1],
            ['product' => 'php', 'price' => 90.00, 'active' => 1, 'sort_order' => 2],
            ['product' => 'theme', 'price' => 9.00, 'active' => 1, 'sort_order' => 1],
            ['product' => 'theme', 'price' => 90.00, 'active' => 1, 'sort_order' => 2],
            ['product' => 'node', 'price' => 12.00, 'active' => 1, 'sort_order' => 1],
            ['product' => 'node', 'price' => 120.00, 'active' => 1, 'sort_order' => 2],
        ]);

        return $db;
    }

    // ------------------------------------------------------------------ shape

    public function testMatrixIsFourProductsByTwoVariants(): void
    {
        $matrix = ProductMatrix::build($this->db());

        self::assertCount(4, $matrix['rows']);
        self::assertSame(
            ['app', 'php', 'theme', 'node'],
            array_column($matrix['rows'], 'code'),
        );
        self::assertSame(
            [VariantResolver::BD, VariantResolver::INT],
            array_column($matrix['variants'], 'code'),
        );
    }

    public function testEveryRowHasOneCellPerVariant(): void
    {
        $matrix = ProductMatrix::build($this->db());

        foreach ($matrix['rows'] as $row) {
            $cells = $row['cells'];

            self::assertCount(2, $cells, $row['code'] . ' must have a cell per variant');

            foreach ($matrix['variants'] as $variant) {
                $code = $variant['code'];
                self::assertArrayHasKey($code, $cells, $row['code'] . '/' . $code);
                self::assertArrayHasKey('offered', $cells[$code]);
                self::assertArrayHasKey('href', $cells[$code]);
            }
        }
    }

    public function testRowOrderMatchesCatalogDisplayOrder(): void
    {
        $matrix = ProductMatrix::build($this->db());

        self::assertSame(
            array_values(Catalog::keys()),
            array_column($matrix['rows'], 'code'),
        );
    }

    // ------------------------------------------------------------------ prices

    public function testEntryPriceIsTheCheapestActivePlan(): void
    {
        $matrix = ProductMatrix::build($this->db());

        foreach ($matrix['rows'] as $row) {
            self::assertSame(2, $row['plan_count'], $row['code']);

            foreach ($row['cells'] as $code => $cell) {
                self::assertTrue($cell['offered']);
                // Cheapest of monthly 12 / yearly 120 is the monthly figure.
                self::assertSame(
                    $row['code'] === 'php' || $row['code'] === 'theme' ? 9.00 : 12.00,
                    $cell['price']['usd'],
                    $row['code'] . '/' . $code,
                );
            }
        }
    }

    public function testIntCellIsUnconvertedUsd(): void
    {
        $matrix = ProductMatrix::build($this->db());
        $cell = $matrix['rows'][0]['cells'][VariantResolver::INT];

        self::assertSame('USD', $cell['price']['currency']);
        self::assertSame('$', $cell['price']['symbol']);
        self::assertFalse($cell['price']['derived']);
    }

    public function testBdCellIsDerivedFromUsdAtTheSharedRate(): void
    {
        $matrix = ProductMatrix::build($this->db());
        $row = $matrix['rows'][0];
        $bd = $row['cells'][VariantResolver::BD]['price'];
        $int = $row['cells'][VariantResolver::INT]['price'];

        self::assertSame('BDT', $bd['currency']);
        self::assertTrue($bd['derived'], 'BDT must be flagged as derived, not a second price list');
        self::assertSame($int['usd'], $bd['usd'], 'both cells must quote the same USD price');
        self::assertEqualsWithDelta(
            VariantResolver::toBdt((float) $int['usd']),
            (float) $bd['amount'],
            0.001,
        );
    }

    // ------------------------------------------------------- not offered rule

    public function testProductWithNoActivePlansIsNotOfferedInEveryVariant(): void
    {
        $db = $this->db();
        foreach ($db->rows('plans') as $i => $row) {
            if ($row['product'] === 'node') {
                unset($db->tables['plans'][$i]);
            }
        }
        $db->tables['plans'] = array_values($db->tables['plans']);

        $matrix = ProductMatrix::build($db);
        $node = $matrix['rows'][3];

        self::assertSame('node', $node['code']);

        foreach ($node['cells'] as $code => $cell) {
            self::assertFalse($cell['offered'], 'node/' . $code);
            self::assertArrayNotHasKey('price', array_filter($cell['price']));
        }
    }

    public function testNotOfferedCellPointsAtCustomOrderAndKeepsItsProduct(): void
    {
        $db = $this->db();
        $db->tables['plans'] = array_values(array_filter(
            $db->rows('plans'),
            static fn (array $row): bool => $row['product'] !== 'node',
        ));

        $matrix = ProductMatrix::build($db);
        $cell = $matrix['rows'][3]['cells'][VariantResolver::BD];

        self::assertFalse($cell['offered']);
        self::assertStringStartsWith(ProductMatrix::REQUEST_PATH, $cell['href']);
        self::assertStringContainsString('product=node', $cell['href']);
        self::assertStringContainsString('variant=bd', $cell['href']);
    }

    /**
     * The original bug: a product without plans rendered as an empty <td>, which
     * read as "we forgot" rather than "not offered yet". Rule 2 of the UX spec
     * requires an explicit state with a CTA, never a blank and never a 0.
     */
    public function testNotOfferedCellCarriesPlanCountAndAnEmptyPrice(): void
    {
        $db = $this->db();
        $db->tables['plans'] = array_values(array_filter(
            $db->rows('plans'),
            static fn (array $row): bool => $row['product'] !== 'node',
        ));

        $matrix = ProductMatrix::build($db);
        $cell = $matrix['rows'][3]['cells'][VariantResolver::INT];

        self::assertFalse($cell['offered']);
        self::assertSame(0, $cell['plan_count']);
        self::assertFalse($cell['price']['available']);
    }

    public function testInactivePlansDoNotCount(): void
    {
        $db = $this->db();
        $db->tables['plans'] = array_values(array_map(
            static fn (array $row): array => $row['product'] === 'app' ? array_merge($row, ['active' => 0]) : $row,
            $db->rows('plans'),
        ));

        $matrix = ProductMatrix::build($db);
        $app = $matrix['rows'][0];

        self::assertSame(0, $app['plan_count']);

        foreach ($app['cells'] as $code => $cell) {
            self::assertFalse($cell['offered'], 'app/' . $code);
        }
    }

    // ----------------------------------------------------------------- links

    public function testOfferedCellLinksToItsProductPageWithTheVariantPreselected(): void
    {
        $matrix = ProductMatrix::build($this->db());

        foreach ($matrix['rows'] as $row) {
            foreach ($row['cells'] as $code => $cell) {
                // The product page, not checkout, and the variant arrives as a
                // query param so the page can open on the right market build.
                self::assertSame(
                    Catalog::page($row['code']) . '?variant=' . $code,
                    $cell['href'],
                    $row['code'] . '/' . $code,
                );
                self::assertStringNotContainsString('checkout', $cell['href']);
            }
        }
    }

    // ------------------------------------------------------------------ parts

    public function testVariantColumnsCarryDisplayAndPaymentMetadata(): void
    {
        $matrix = ProductMatrix::build($this->db());
        $columns = $matrix['variants'];

        foreach ($columns as $column) {
            self::assertNotSame('', (string) $column['label']);
            self::assertNotSame('', (string) $column['short']);
            self::assertNotSame('', (string) $column['desc']);
            self::assertContains($column['currency'], ['BDT', 'USD']);
            self::assertNotEmpty($column['gateways']);
        }

        $bd = $columns[0];
        $int = $columns[1];

        self::assertTrue($bd['derived']);
        self::assertFalse($int['derived']);
    }

    public function testMatrixExposesTheBdtRateForTheFootnote(): void
    {
        $matrix = ProductMatrix::build($this->db());

        self::assertSame(VariantResolver::rate(), $matrix['rate']);
        self::assertTrue($matrix['has_bdt']);
    }

    public function testBuildSurvivesAMissingPlansTable(): void
    {
        $matrix = ProductMatrix::build(new FakeDatabase());

        self::assertCount(4, $matrix['rows']);

        foreach ($matrix['rows'] as $row) {
            self::assertSame(0, $row['plan_count']);

            foreach ($row['cells'] as $cell) {
                self::assertFalse($cell['offered']);
                self::assertStringStartsWith(ProductMatrix::REQUEST_PATH, $cell['href']);
            }
        }
    }
}
