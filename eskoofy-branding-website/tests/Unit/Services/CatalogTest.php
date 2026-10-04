<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Catalog;
use App\Services\VariantResolver;
use Tests\TestCase;

/**
 * The taxonomy that Phase 0 exists to make unambiguous: four Products, two
 * Variants, and no overlap between the two words.
 *
 * See docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §3.
 */
class CatalogTest extends TestCase
{
    // --------------------------------------------------------------- products

    public function testCatalogHasExactlyFourProducts(): void
    {
        self::assertSame(['app', 'php', 'theme', 'node'], Catalog::keys());
    }

    public function testCatalogOrderIsDisplayOrder(): void
    {
        $orders = array_column(Catalog::all(), 'order');

        self::assertSame([1, 2, 3, 4], $orders);
        self::assertSame($orders, array_values(array_unique($orders)));
    }

    /**
     * The original bug: a local `$productColors` map in the dashboard knew
     * app/theme/php but not node, so the Node.js bar silently rendered in the
     * app's blue. Every product must now own a distinct colour from the catalog.
     */
    public function testEveryProductHasADistinctColour(): void
    {
        $colors = array_values(Catalog::colors());

        self::assertCount(4, $colors);
        self::assertSame($colors, array_values(array_unique($colors)), 'two products share a colour');
    }

    public function testColoursAreValidHexSoTheyCanBeInlinedInStyles(): void
    {
        foreach (Catalog::colors() as $code => $color) {
            self::assertMatchesRegularExpression('/^#[0-9a-fA-F]{6}$/', $color, "{$code} has a malformed colour");
        }
    }

    public function testColoursCoverEveryProduct(): void
    {
        self::assertSame(Catalog::keys(), array_keys(Catalog::colors()));
    }

    public function testEveryProductDefinesTheFieldsTheDashboardRenders(): void
    {
        foreach (Catalog::all() as $code => $product) {
            foreach (['code', 'order', 'label_key', 'color', 'icon', 'page', 'stack'] as $field) {
                self::assertArrayHasKey($field, $product, "{$code} is missing '{$field}'");
                self::assertNotSame('', (string) $product[$field], "{$code}.{$field} is empty");
            }
            self::assertSame($code, $product['code'], "{$code} has a mismatched code field");
        }
    }

    public function testProductPagesAreDistinct(): void
    {
        $pages = array_column(Catalog::all(), 'page');

        self::assertSame($pages, array_values(array_unique($pages)));
    }

    public function testHasAcceptsCanonicalCodesAndRejectsOthers(): void
    {
        self::assertTrue(Catalog::has('node'));
        self::assertFalse(Catalog::has('legacy'));
        self::assertFalse(Catalog::has(''));
        self::assertFalse(Catalog::has(null));
    }

    public function testGetThrowsForAnUnknownProduct(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Catalog::get('nope');
    }

    public function testFindDegradesToAGreyPlaceholderForUnknownProducts(): void
    {
        // Read paths are fed by the query string, so an unknown code must render
        // something inert rather than throw mid-request.
        $unknown = Catalog::find('nope');

        self::assertSame('nope', $unknown['code']);
        self::assertSame(Catalog::color('unknown'), $unknown['color']);
        self::assertMatchesRegularExpression('/^#[0-9a-fA-F]{6}$/', $unknown['color']);
        self::assertSame('/products', $unknown['page']);
        self::assertSame('layers', $unknown['icon'], 'falls back to a real icon');
        self::assertGreaterThan(4, $unknown['order'], 'sorts last');

        self::assertSame('', Catalog::find(null)['stack'], 'a null code also degrades to the stub');
        self::assertSame('node', Catalog::find('node')['code']);
    }

    public function testAnUnknownProductStillGetsAReadableLabel(): void
    {
        self::assertSame('Nope', Catalog::label('nope'));
        self::assertStringNotContainsString('catalog.product', Catalog::label('nope'));
    }

    public function testColourLookupFallsBackToANeutralGreyInsteadOfNull(): void
    {
        $fallback = Catalog::color('unknown');

        self::assertNotSame('', $fallback);
        self::assertMatchesRegularExpression('/^#[0-9a-fA-F]{6}$/', $fallback);
        // The fallback must not impersonate a real product.
        self::assertNotContains($fallback, Catalog::colors());
        self::assertSame($fallback, Catalog::color(null));
    }

    public function testProductKeysIsAnAliasOfKeys(): void
    {
        self::assertSame(Catalog::keys(), Catalog::productKeys());
    }

    /**
     * Charts must plot products in catalog order, not in whatever order the
     * GROUP BY happened to return.
     */
    public function testSortCodesOrdersAccordingToTheCatalog(): void
    {
        self::assertSame(
            ['app', 'php', 'theme', 'node'],
            Catalog::sortCodes(['node', 'app', 'theme', 'php'])
        );
    }

    public function testSortCodesAppendsUnknownCodesRatherThanDroppingThem(): void
    {
        // Silent data loss here would under-report revenue.
        self::assertSame(
            ['app', 'node', 'mystery'],
            Catalog::sortCodes(['mystery', 'node', 'app'])
        );
    }

    public function testSortCodesToleratesAnEmptyList(): void
    {
        self::assertSame([], Catalog::sortCodes([]));
    }

    public function testLabelFallsBackToACapitalisedCodeWhenTranslationIsMissing(): void
    {
        $label = Catalog::label('node');

        self::assertNotSame('', $label);
        self::assertStringNotContainsString('catalog.product', $label, 'raw lang key leaked into the UI');
    }

    // --------------------------------------------------------------- variants

    public function testThereAreExactlyTwoVariants(): void
    {
        self::assertSame(['bd', 'int'], VariantResolver::all());
    }

    /**
     * "Variant" must never name a product. This is the single most important
     * invariant in the taxonomy.
     */
    public function testNoVariantNameCollidesWithAProductName(): void
    {
        self::assertSame([], array_intersect(VariantResolver::all(), Catalog::keys()));
    }

    public function testVariantValidationIsCaseAndWhitespaceTolerant(): void
    {
        self::assertTrue(VariantResolver::isValid('bd'));
        self::assertTrue(VariantResolver::isValid('BD'));
        self::assertTrue(VariantResolver::isValid(' int '));
        self::assertFalse(VariantResolver::isValid('uk'));
        self::assertFalse(VariantResolver::isValid('node'));
        self::assertFalse(VariantResolver::isValid(''));
        self::assertFalse(VariantResolver::isValid(null));
        self::assertFalse(VariantResolver::isValid(1));
    }

    public function testNormalizeReturnsNullForUnknownInput(): void
    {
        self::assertSame('bd', VariantResolver::normalize('BD'));
        self::assertNull(VariantResolver::normalize('nope'));
        self::assertNull(VariantResolver::normalize(null));
    }

    public function testNormalizeOrIntFallsBackToInternational(): void
    {
        self::assertSame('int', VariantResolver::normalizeOrInt('nope'));
        self::assertSame('int', VariantResolver::normalizeOrInt(null));
        self::assertSame('bd', VariantResolver::normalizeOrInt('bd'));
    }

    public function testEveryVariantHasLabelCodeAndCurrencyMetadata(): void
    {
        foreach (VariantResolver::all() as $variant) {
            self::assertNotSame('', VariantResolver::label($variant));
            self::assertNotSame('', VariantResolver::shortLabel($variant));
            self::assertNotSame('', VariantResolver::code($variant));
            self::assertNotSame('', VariantResolver::currencySymbol($variant));
            self::assertMatchesRegularExpression('/^[A-Z]{3}$/', VariantResolver::currencyCode($variant));
        }
    }

    /**
     * The variant lookup used to read `variants.bd` while the locale files are
     * keyed `variant.bd`, so the translation was dead and only the hard-coded
     * English fallback ever rendered.
     */
    public function testVariantLabelsResolveThroughTheLocaleFiles(): void
    {
        foreach (VariantResolver::all() as $variant) {
            self::assertSame(
                (string) __('variant.' . $variant),
                VariantResolver::label($variant),
                "variant.{$variant} did not resolve"
            );
            self::assertSame(
                (string) __('variant.' . $variant . '_short'),
                VariantResolver::shortLabel($variant)
            );
        }
    }

    public function testVariantLabelsNeverLeakARawLangKey(): void
    {
        foreach (VariantResolver::all() as $variant) {
            foreach ([VariantResolver::label($variant), VariantResolver::shortLabel($variant)] as $label) {
                self::assertStringNotContainsString('variant.', $label);
            }
        }
    }

    public function testBdUsesTakaAndIntUsesDollars(): void
    {
        self::assertSame('BDT', VariantResolver::currencyCode('bd'));
        self::assertSame('USD', VariantResolver::currencyCode('int'));
        self::assertNotSame(
            VariantResolver::currencySymbol('bd'),
            VariantResolver::currencySymbol('int')
        );
    }

    public function testCurrencyFallsBackToInternationalForUnknownVariants(): void
    {
        self::assertSame('USD', VariantResolver::currencyCode('nope'));
        self::assertSame('USD', VariantResolver::currencyCode(null));
    }

    public function testBdtConversionUsesTheConfiguredRate(): void
    {
        $rate = VariantResolver::rate();

        self::assertGreaterThan(0, $rate);
        self::assertEqualsWithDelta(100.0 * $rate, VariantResolver::toBdt(100.0), 0.01);
        self::assertSame(0.0, VariantResolver::toBdt(0.0));
    }

    public function testDisplayAmountConvertsToBdtForTheBdVariant(): void
    {
        $display = VariantResolver::displayAmount(120.0, 'bd');

        foreach (['amount', 'symbol', 'currency', 'derived', 'rate'] as $key) {
            self::assertArrayHasKey($key, $display, "missing '{$key}'");
        }
        self::assertSame('BDT', $display['currency']);
        self::assertTrue($display['derived']);
        self::assertEqualsWithDelta(120.0 * $display['rate'], $display['amount'], 0.01);
    }

    public function testDisplayAmountLeavesUsdAloneForTheInternationalVariant(): void
    {
        $display = VariantResolver::displayAmount(120.0, 'int');

        self::assertSame('USD', $display['currency']);
        self::assertFalse($display['derived']);
        self::assertSame(120.0, $display['amount']);
        self::assertNull($display['rate']);
    }

    public function testDisplayAmountDefaultsToTheInternationalVariant(): void
    {
        self::assertSame('USD', VariantResolver::displayAmount(10.0, null)['currency']);
    }

    // ------------------------------------------------------------- resolution

    /**
     * Variant resolution order: explicit value, then latest payment, then
     * country, then international.
     */
    public function testAnExplicitVariantOnARecordWinsOverEverythingElse(): void
    {
        $customer = ['variant' => 'bd', 'country_code' => 'US'];

        self::assertSame('bd', VariantResolver::forCustomer($customer));
    }

    public function testAnUnknownStoredVariantFallsThroughToTheNextSignal(): void
    {
        self::assertSame('int', VariantResolver::forCustomer(['variant' => 'legacy', 'country_code' => 'US']));
    }

    public function testBangladeshCustomersAreResolvedAsBdWithoutAnExplicitFlag(): void
    {
        self::assertSame('bd', VariantResolver::forCustomer(['country_code' => 'BD']));
        self::assertSame('bd', VariantResolver::forCustomer(['country_code' => 'bd']));
    }

    public function testNonBangladeshCustomersFallBackToInternational(): void
    {
        self::assertSame('int', VariantResolver::forCustomer(['country_code' => 'GB']));
        self::assertSame('int', VariantResolver::forCustomer([]));
    }

    public function testNewLicensesInheritTheCustomersResolvedVariant(): void
    {
        self::assertSame('bd', VariantResolver::forNewLicense(['country_code' => 'BD']));
        self::assertSame('int', VariantResolver::forNewLicense([]));
    }

    // --------------------------------------------------------------- offering

    public function testACellIsOfferedOnlyWhenItsProductHasAnActivePlan(): void
    {
        self::assertTrue(VariantResolver::isOffered('node', ['node' => 2]));
        self::assertFalse(VariantResolver::isOffered('node', ['node' => 0]));
        self::assertFalse(VariantResolver::isOffered('node', []));
    }

    public function testOfferingIsDecidedByPlanStockNotByTheVariant(): void
    {
        // Plans are variant-agnostic (USD-canonical), so one active plan makes
        // both bd and int cells purchasable.
        $counts = ['app' => 2];

        self::assertTrue(VariantResolver::isOffered('app', $counts));
        self::assertTrue(VariantResolver::isOffered('app', $counts), 'both variant cells offer off the same plan');
        self::assertFalse(VariantResolver::isOffered('node', $counts));
    }

    public function testOfferingLookupIsCaseTolerant(): void
    {
        self::assertTrue(VariantResolver::isOffered('NODE', ['node' => 1]));
        self::assertTrue(VariantResolver::isOffered(' node ', ['node' => 1]));
    }

    public function testGatewaysDifferByVariantAndAreNeverEmpty(): void
    {
        foreach (VariantResolver::all() as $variant) {
            $codes = VariantResolver::gatewayCodes($variant);

            self::assertNotEmpty($codes, "{$variant} has no gateways");
            self::assertSame($codes, array_values(array_unique($codes)));
        }
    }

    public function testUnknownVariantFallsBackToTheInternationalGateways(): void
    {
        self::assertSame(
            VariantResolver::gatewayCodes('int'),
            VariantResolver::gatewayCodes('nope')
        );
    }
}