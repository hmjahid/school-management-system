<?php
declare(strict_types=1);

namespace App\Services;

/**
 * The single source of truth for the four Eskoofy products.
 *
 * Taxonomy (see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §3):
 *   - **Product** = one of the four deployment targets below. Never "variant".
 *   - **Variant**  = `bd` or `int`, owned by {@see VariantResolver}. Never a product.
 *
 * Every consumer — dashboard charts, the product pills, the Product x Variant
 * matrix, the pricing table, the nav — reads labels, colours and ordering from
 * here so a product can never go missing or be mis-coloured again.
 */
final class Catalog
{
    public const APP   = 'app';
    public const PHP   = 'php';
    public const THEME = 'theme';
    public const NODE  = 'node';

    /**
     * Product definitions in canonical display order.
     *
     * @var array<string, array<string, mixed>>
     */
    private const PRODUCTS = [
        self::APP => [
            'code'        => self::APP,
            'order'       => 1,
            'label_key'   => 'catalog.product.app',
            'tag_key'     => 'catalog.product.app_tag',
            'desc_key'    => 'catalog.product.app_desc',
            'color'       => '#4f46e5', // indigo
            'icon'        => 'layers',
            'page'        => '/products/app',
            'order_path'  => '/checkout',
            'stack'       => 'Laravel 12',
            'requires'    => 'vps',
        ],
        self::PHP => [
            'code'        => self::PHP,
            'order'       => 2,
            'label_key'   => 'catalog.product.php',
            'tag_key'     => 'catalog.product.php_tag',
            'desc_key'    => 'catalog.product.php_desc',
            'color'       => '#0284c7', // sky
            'icon'        => 'tool',
            'page'        => '/products/php',
            'order_path'  => '/checkout',
            'stack'       => 'Raw PHP 8.2',
            'requires'    => 'shared',
        ],
        self::THEME => [
            'code'        => self::THEME,
            'order'       => 3,
            'label_key'   => 'catalog.product.theme',
            'tag_key'     => 'catalog.product.theme_tag',
            'desc_key'    => 'catalog.product.theme_desc',
            'color'       => '#7c3aed', // violet
            'icon'        => 'layout',
            'page'        => '/products/theme',
            'order_path'  => '/checkout',
            'stack'       => 'WordPress',
            'requires'    => 'wordpress',
        ],
        self::NODE => [
            'code'        => self::NODE,
            'order'       => 4,
            'label_key'   => 'catalog.product.node',
            'tag_key'     => 'catalog.product.node_tag',
            'desc_key'    => 'catalog.product.node_desc',
            'color'       => '#059669', // emerald
            'icon'        => 'globe',
            'page'        => '/products/node',
            'order_path'  => '/checkout',
            'stack'       => 'Next.js + Prisma',
            'requires'    => 'vps',
        ],
    ];

    /** Default colour for a product code that is not in the catalog. */
    private const FALLBACK_COLOR = '#64748b';

    /** @return list<string> canonical product codes, in display order */
    public static function keys(): array
    {
        return array_keys(self::PRODUCTS);
    }

    /** @return list<string> canonical product codes, in display order */
    public static function productKeys(): array
    {
        return self::keys();
    }

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return self::PRODUCTS;
    }

    public static function has(?string $code): bool
    {
        return $code !== null && isset(self::PRODUCTS[strtolower(trim($code))]);
    }

    /**
     * @return array<string, mixed>
     * @throws \InvalidArgumentException when the code is not a known product.
     */
    public static function get(string $code): array
    {
        $code = strtolower(trim($code));
        if (!isset(self::PRODUCTS[$code])) {
            throw new \InvalidArgumentException("Unknown Eskoofy product: {$code}");
        }

        return self::PRODUCTS[$code];
    }

    /** Same as {@see get()} but never throws — unknown codes yield a stub. */
    public static function find(?string $code): array
    {
        if (!self::has($code)) {
            return [
                'code'      => (string) $code,
                'order'     => 99,
                'label_key' => 'catalog.product.unknown',
                'tag_key'   => 'catalog.product.unknown',
                'desc_key'  => 'catalog.product.unknown',
                'color'     => self::FALLBACK_COLOR,
                'icon'      => 'layers',
                'page'      => '/products',
                'order_path' => '/custom-order',
                'stack'     => '',
                'requires'  => '',
            ];
        }

        return self::get((string) $code);
    }

    /**
 * Human label for a product.
 *
 * Never returns a raw lang key: `I18n::t()` echoes the key back when it is
 * missing, which would put `catalog.product.node` straight into the admin UI.
 * The hard-coded fallbacks keep the dashboard legible even if a translation is
 * ever dropped from a locale file.
 */
public static function label(string $code): string
    {
        $fallbacks = [
            self::APP   => 'School App',
            self::PHP   => 'Raw PHP',
            self::THEME => 'WP Theme',
            self::NODE  => 'Node.js App',
        ];

        $key = (string) (self::find($code)['label_key'] ?? '');
        $text = $key !== '' ? (string) __($key) : '';

        if ($text === '' || $text === $key) {
            return $fallbacks[$code] ?? ucfirst(strtolower(trim($code)));
        }

        return $text;
    }

    public static function color(?string $code): string
    {
        if (!self::has($code)) {
            return self::FALLBACK_COLOR;
        }

        return (string) self::get((string) $code)['color'];
    }

    public static function icon(?string $code): string
    {
        return (string) self::find($code)['icon'];
    }

    public static function page(?string $code): string
    {
        return (string) self::find($code)['page'];
    }

    /**
     * Colour lookup table for chart series, in canonical order.
     *
     * @return array<string, string>
     */
    public static function colors(): array
    {
        $out = [];
        foreach (self::PRODUCTS as $code => $product) {
            $out[$code] = (string) $product['color'];
        }

        return $out;
    }

    /**
     * Reorder an arbitrary set of product codes into canonical display order,
     * dropping duplicates. Unknown codes are appended last, preserving their
     * incoming order.
     *
     * @param iterable<mixed> $codes
     * @return list<string>
     */
    public static function sortCodes(iterable $codes): array
    {
        $seen = [];
        $unknown = [];
        foreach ($codes as $code) {
            $code = strtolower(trim((string) $code));
            if ($code === '' || isset($seen[$code])) {
                continue;
            }
            if (self::has($code)) {
                $seen[$code] = true;
            } else {
                $unknown[] = $code;
            }
        }

        $ordered = array_values(array_filter(
            self::keys(),
            static fn (string $code): bool => isset($seen[$code])
        ));

        return array_merge($ordered, $unknown);
    }
}