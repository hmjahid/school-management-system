<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DatabaseInterface;
use App\Gateways\GatewayFactory;

/**
 * The single source of truth for the two Eskoofy variants: `bd` and `int`.
 *
 * A *variant* is a market build profile — the same codebase shipped with a
 * different configuration (language, currency, ministry links, payment
 * gateways). It is NOT one of the four products; those belong to
 * {@see Catalog}. Nothing outside this class may branch on a variant value.
 *
 * Prices stay USD-canonical (see `database/schema.sql`); BDT figures are always
 * *derived* for display via {@see toBdt()} / {@see displayAmount()}, never stored.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §3
 */
final class VariantResolver
{
    public const BD  = 'bd';
    public const INT = 'int';

    /** Canonical order: Bangladesh first, then International. */
    private const ALL = [self::BD, self::INT];

    /** @return list<string> */
    public static function all(): array
    {
        return self::ALL;
    }

    public static function isValid(mixed $value): bool
    {
        return is_string($value) && in_array(strtolower(trim($value)), self::ALL, true);
    }

    /**
     * Normalise arbitrary input to a valid variant, or null when unrecognised.
     * Callers that must not silently coerce should use {@see isValid()} first.
     */
    public static function normalize(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = strtolower(trim($value));

        return in_array($value, self::ALL, true) ? $value : null;
    }

    /** Coerce to a valid variant, falling back to `int`. */
    public static function normalizeOrInt(mixed $value): string
    {
        return self::normalize($value) ?? self::INT;
    }

    /**
     * Human label for a variant.
     *
     * @param string $locale `en` or `bn`.
     */
    public static function label(?string $variant, string $locale = 'en'): string
    {
        $variant = self::normalizeOrInt($variant);
        $key = 'variant.' . $variant;

        if (function_exists('__')) {
            $translated = (string) __($key, [], $locale);
            if ($translated !== $key && $translated !== '') {
                return $translated;
            }
        }

        return $variant === self::BD ? 'Bangladesh (BD)' : 'International (INT)';
    }

    /** Compact label for chips and matrix column headers. */
    public static function shortLabel(?string $variant, string $locale = 'en'): string
    {
        $variant = self::normalizeOrInt($variant);
        $key = 'variant.' . $variant . '_short';

        if (function_exists('__')) {
            $translated = (string) __($key, [], $locale);
            if ($translated !== $key && $translated !== '') {
                return $translated;
            }
        }

        return self::label($variant, $locale);
    }

    /** Two-letter uppercase code, for badges and table columns. */
    public static function code(?string $variant): string
    {
        return strtoupper(self::normalizeOrInt($variant));
    }

    /** Currency symbol a customer in this variant pays in. */
    public static function currencySymbol(?string $variant): string
    {
        return self::normalizeOrInt($variant) === self::BD ? '৳' : '$';
    }

    /** Currency code a customer in this variant pays in. */
    public static function currencyCode(?string $variant): string
    {
        return self::normalizeOrInt($variant) === self::BD ? 'BDT' : 'USD';
    }

    /**
     * Live USD → BDT rate. Prices are USD-canonical; this derives the BD figure.
     */
    public static function rate(): float
    {
        $rate = GatewayFactory::bdtRate();

        return $rate > 0 ? $rate : 110.0;
    }

    public static function toBdt(float $usd): float
    {
        return round($usd * self::rate(), 2);
    }

    /**
     * Format a USD-canonical amount for display in the given variant.
     *
     * @return array{amount: float, symbol: string, currency: string, derived: bool, rate: float|null}
     *         `derived` is true when the figure was converted from USD, so the
     *         UI can label it as derived rather than presenting a second price list.
     */
    public static function displayAmount(float $usd, ?string $variant): array
    {
        $variant = self::normalizeOrInt($variant);

        if ($variant === self::BD) {
            return [
                'amount'   => self::toBdt($usd),
                'symbol'   => '৳',
                'currency' => 'BDT',
                'derived'  => true,
                'rate'     => self::rate(),
            ];
        }

        return [
            'amount'   => $usd,
            'symbol'   => '$',
            'currency' => 'USD',
            'derived'  => false,
            'rate'     => null,
        ];
    }

    /**
     * Resolve the variant for an existing customer.
     *
     * Resolution order — first hit wins:
     *   1. the explicit `customers.variant` column (admin-editable)
     *   2. the variant of their most recent payment
     *   3. `GatewayFactory::isBdCountry($customer['country'])`
     *   4. `int`
     *
     * @param array<string, mixed> $customer
     */
    public static function forCustomer(array $customer, ?DatabaseInterface $db = null): string
    {
        $explicit = self::normalize($customer['variant'] ?? null);
        if ($explicit !== null) {
            return $explicit;
        }

        $id = isset($customer['id']) ? (int) $customer['id'] : 0;
        if ($id > 0) {
            $fromPayment = self::latestPaymentVariant($id, $db);
            if ($fromPayment !== null) {
                return $fromPayment;
            }
        }

        if (GatewayFactory::isBdCountry(self::countryOf($customer))) {
            return self::BD;
        }

        return self::INT;
    }

    /**
     * Variant to stamp on a *new* license.
     *
     * Unlike {@see forCustomer()} this never touches payment history — the value
     * is snapshotted onto `licenses.variant` at issue time and is immutable from
     * then on, so historical license counts never re-bucket when a customer moves.
     *
     * @param array<string, mixed> $customer
     */
    public static function forNewLicense(array $customer): string
    {
        $explicit = self::normalize($customer['variant'] ?? null);
        if ($explicit !== null) {
            return $explicit;
        }

        return GatewayFactory::isBdCountry(self::countryOf($customer)) ? self::BD : self::INT;
    }

    /** Variant of a customer's most recent payment, or null when they have none. */
    public static function latestPaymentVariant(int $customerId, ?DatabaseInterface $db = null): ?string
    {
        if ($customerId <= 0) {
            return null;
        }

        try {
            $db = $db ?? \App\Core\Database::getInstance();
            $row = $db->fetch(
                'SELECT variant FROM payments WHERE customer_id = ? ORDER BY id DESC LIMIT 1',
                [$customerId]
            );
        } catch (\Throwable) {
            // No payments table (fresh install) or no database in tests.
            return null;
        }

        return self::normalize($row['variant'] ?? null);
    }

    /**
     * Is this combination of product and variant purchasable?
     *
     * A product x variant cell is offered when the product has at least one
     * active plan. Plans are variant-agnostic (USD-canonical), so a cell is
     * offered as soon as its product has stock — the variant only decides
     * currency and payment gateways.
     *
     * @param array<string, mixed> $planCounts product code => active plan count
     */
    public static function isOffered(string $product, array $planCounts): bool
    {
        $product = strtolower(trim($product));

        return (int) ($planCounts[$product] ?? 0) > 0;
    }

    /**
     * Gateways a customer in this variant can pay with, from the existing
     * gateway configuration. Display convenience only — the authoritative list
     * comes from {@see GatewayFactory::gatewaysForCountry()}.
     *
     * @return list<string>
     */
    public static function gatewayCodes(?string $variant): array
    {
        return self::normalizeOrInt($variant) === self::BD
            ? GatewayFactory::bdGateways()
            : GatewayFactory::intGateways();
    }

    /** @param array<string, mixed> $customer */
    private static function countryOf(array $customer): ?string
    {
        // `country` is the schema column; `country_code` is accepted because
        // settings, the geo detector and API payloads all use that spelling.
        foreach (['country', 'country_code'] as $field) {
            $value = $customer[$field] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}