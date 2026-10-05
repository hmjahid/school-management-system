<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DatabaseInterface;
use App\Models\Plan;

/**
 * Builds the 4-row x 2-column Product x Variant matrix that the public site
 * renders in five places (`/products`, `/pricing`, `/compare`, `/choose`
 * result, and the homepage badge strips).
 *
 * Rows are **products** ({@see Catalog}); columns are **variants**
 * ({@see VariantResolver}). The component
 * `views/partials/product_variant_matrix.php` consumes the array this builds
 * and does no querying of its own, so the admin widget (W8) can hand it live
 * license/revenue counts instead of prices and get the same markup.
 *
 * Two rules from the spec are enforced here rather than in the view:
 *
 *  1. **A "not offered" cell is a first-class state.** It is never a blank and
 *     never a `0`; the cell carries `offered => false` and a request-CTA href.
 *  2. **One currency per column.** Prices are USD-canonical in `plans`, so the
 *     BD column derives Taka via {@see VariantResolver::toBdt()} and is flagged
 *     `derived` for the footnote. There is only ever one price list.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §4
 */
final class ProductMatrix
{
    /** Where a visitor goes to ask for an unoffered product x variant cell. */
    public const REQUEST_PATH = '/custom-order';

    /**
     * Build the matrix from the `plans` table.
     *
     * @param array<string, string|int> $metrics product code => value, injected by
     *        the admin widget to add license counts per row. Public callers omit it.
     */
    public static function build(?DatabaseInterface $db = null, array $metrics = []): array
    {
        $planCounts = self::planCounts($db);

        $rows = [];
        foreach (Catalog::all() as $product) {
            $code = (string) $product['code'];

            $rows[] = [
                'code'      => $code,
                'product'   => $product,
                'label'     => Catalog::label($code),
                'tag'       => (string) __((string) $product['tag_key']),
                'desc'      => (string) __((string) $product['desc_key']),
                'color'     => (string) $product['color'],
                'icon'      => (string) $product['icon'],
                'stack'     => (string) $product['stack'],
                'requires'  => (string) $product['requires'],
                'page'      => (string) $product['page'],
                'plan_count' => (int) ($planCounts[$code] ?? 0),
                'metric'    => $metrics[$code] ?? null,
                'cells'     => self::cells($code, $db),
            ];
        }

        return [
            'rows'      => $rows,
            'variants'  => self::variantColumns(),
            'rate'      => VariantResolver::rate(),
            'has_bdt'   => in_array(VariantResolver::BD, array_column(self::variantColumns(), 'code'), true),
        ];
    }

    /**
     * The two variant columns, with the display metadata the header needs.
     *
     * @return list<array{code: string, label: string, short: string, desc: string, symbol: string, currency: string, gateways: list<string>}>
     */
    public static function variantColumns(): array
    {
        $columns = [];

        foreach (VariantResolver::all() as $variant) {
            $columns[] = [
                'code'     => $variant,
                'label'    => VariantResolver::label($variant),
                'short'    => VariantResolver::shortLabel($variant),
                'desc'     => (string) __('variant.' . $variant . '_desc'),
                'symbol'   => VariantResolver::currencySymbol($variant),
                'currency' => VariantResolver::currencyCode($variant),
                'derived'  => $variant === VariantResolver::BD,
                'gateways' => VariantResolver::gatewayCodes($variant),
            ];
        }

        return $columns;
    }

    /**
     * The two cells of one product row.
     *
     * @return array<string, array<string, mixed>> keyed by variant code
     */
    private static function cells(string $product, ?DatabaseInterface $db): array
    {
        $plans = self::plans($product, $db);
        $counts = [$product => count($plans)];
        $cells = [];

        foreach (VariantResolver::all() as $variant) {
            $offered = VariantResolver::isOffered($product, $counts);

            $cells[$variant] = [
                'product'    => $product,
                'variant'    => $variant,
                'offered'    => $offered,
                'plans'      => $plans,
                'plan_count' => count($plans),
                'price'      => self::entryPrice($plans, $variant),
                'href'       => self::cellHref($product, $variant, $offered),
            ];
        }

        return $cells;
    }

    /**
     * Where a cell links to.
     *
     * An offered cell goes to the product page with the variant preselected; an
     * unoffered cell goes to the custom-order form carrying both axes, because
     * "not offered" must never be a dead end.
     */
    public static function cellHref(string $product, string $variant, bool $offered): string
    {
        if (!$offered) {
            return self::REQUEST_PATH . '?' . http_build_query([
                'product' => $product,
                'variant' => $variant,
            ]);
        }

        $page = Catalog::page($product);
        $separator = str_contains($page, '?') ? '&' : '?';

        return $page . $separator . http_build_query(['variant' => $variant]);
    }

    /**
     * Cheapest active plan, formatted for one variant's column.
     *
     * @return array{usd: float, amount: float, symbol: string, currency: string, derived: bool, period: string|null, available: bool}
     */
    public static function entryPrice(array $plans, string $variant): array
    {
        $usd = null;
        $period = null;

        foreach ($plans as $plan) {
            $price = (float) ($plan['price'] ?? 0);
            if ($usd === null || $price < $usd) {
                $usd = $price;
                $period = (string) ($plan['period'] ?? 'monthly');
            }
        }

        if ($usd === null) {
            $usd = 0.0;
        }

        $display = VariantResolver::displayAmount($usd, $variant);

        return [
            'usd'       => $usd,
            'amount'    => (float) $display['amount'],
            'symbol'    => (string) $display['symbol'],
            'currency'  => (string) $display['currency'],
            'derived'   => (bool) $display['derived'],
            'rate'      => $display['rate'],
            'period'    => $period,
            'available' => !empty($plans),
        ];
    }

    /**
     * Active plan count per product code. Drives the "is this cell offered"
     * decision, so a product with no active plans shows as unoffered rather
     * than as a purchasable cell with no price.
     *
     * @return array<string, int>
     */
    public static function planCounts(?DatabaseInterface $db = null): array
    {
        $counts = [];

        foreach (Catalog::keys() as $code) {
            $counts[$code] = count(self::plans($code, $db));
        }

        return $counts;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function plans(string $product, ?DatabaseInterface $db): array
    {
        try {
            if ($db === null) {
                return array_values(Plan::activeFor($product));
            }

            return array_values($db->fetchAll(
                'SELECT * FROM plans WHERE product = ? AND active = 1 ORDER BY sort_order ASC',
                [$product]
            ));
        } catch (\Throwable) {
            // No `plans` table yet (fresh install) or no database in tests. An
            // empty product degrades to "not offered", which is the safe
            // reading: we must not advertise a price we cannot verify.
            return [];
        }
    }
}
