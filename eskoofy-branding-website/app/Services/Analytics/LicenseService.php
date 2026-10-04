<?php
declare(strict_types=1);

namespace App\Services\Analytics;

use App\Core\DatabaseInterface;
use App\Services\Catalog;
use App\Services\VariantResolver;

/**
 * License read models, including the **derived status** that the dashboard,
 * license list and Product x Variant matrix all share.
 *
 * Why this exists — `licenses.status` is a stored column that only ever holds
 * `active|suspended|cancelled` (see Admin\LicenseController::updateStatus).
 * Expiry is *never stored*; it is implied by `expires_at`. The old dashboard
 * therefore had two bugs at once:
 *
 *   - its donut legend carried an `expired` swatch that no row could ever have,
 *     because nothing writes that value; and
 *   - licenses that were `status = 'active'` but past `expires_at` were counted
 *     in the donut's green "active" wedge, disagreeing with the `active_licenses`
 *     KPI, which did exclude them.
 *
 * One canonical SQL expression, reused everywhere, removes the disagreement.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §7.3 (B2)
 */
final class LicenseService
{
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CANCELLED = 'cancelled';

    /** Ceiling on the expiring-soon panel so a bad `$limit` cannot pull every row. */
    public const EXPIRING_MAX_LIMIT = 100;

    /** Canonical status order and display colours. */
    public const STATUSES = [
        self::STATUS_ACTIVE    => ['label' => 'Active',    'tone' => 'success'],
        self::STATUS_SUSPENDED => ['label' => 'Suspended', 'tone' => 'warning'],
        self::STATUS_EXPIRED   => ['label' => 'Expired',   'tone' => 'muted'],
        self::STATUS_CANCELLED => ['label' => 'Cancelled', 'tone' => 'danger'],
    ];

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /**
     * The canonical derived-status expression.
     *
     * A row that is administratively suspended or cancelled keeps that status
     * regardless of dates; otherwise a past `expires_at` means expired.
     * `expires_at IS NULL` means perpetual, which counts as active.
     */
    public static function derivedStatusSql(string $alias = 'l'): string
    {
        return "CASE
            WHEN {$alias}.status IN ('suspended', 'cancelled') THEN {$alias}.status
            WHEN {$alias}.expires_at IS NOT NULL AND {$alias}.expires_at <= NOW() THEN 'expired'
            ELSE 'active'
        END";
    }

    /** Restrict a query to licenses that are genuinely usable right now. */
    public static function activeSql(string $alias = 'l'): string
    {
        return "{$alias}.deleted_at IS NULL
            AND {$alias}.status = 'active'
            AND ({$alias}.expires_at IS NULL OR {$alias}.expires_at > NOW())";
    }

    /**
     * License counts per derived status.
     *
     * @param array{product?: ?string, variant?: ?string} $filters
     * @return array<string, int>
     */
    public function countsByStatus(array $filters = []): array
    {
        $params = [];
        $where = ['l.deleted_at IS NULL'];

        if (! empty($filters['product'])) {
            $where[] = 'l.product = ?';
            $params[] = $filters['product'];
        }
        if (! empty($filters['variant'])) {
            $where[] = 'l.variant = ?';
            $params[] = $filters['variant'];
        }

        $rows = $this->db->fetchAll(
            // Alias must not collide with the physical `l.status` column: in
            // MySQL/MariaDB `GROUP BY status` binds to the real column and
            // silently discards the derived expression, which made every
            // expired license report as 'active' and never emit 'expired'.
            'SELECT ' . self::derivedStatusSql('l') . ' AS derived_status, COUNT(*) AS c
               FROM licenses l
              WHERE ' . implode(' AND ', $where) . '
              GROUP BY derived_status',
            $params
        );

        $out = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach ($rows as $row) {
            $out[(string) $row['derived_status']] = (int) $row['c'];
        }

        return $out;
    }

    /**
     * Licenses expiring within `$days`, restricted to genuinely active rows.
     *
     * This is the fix for B1: the old `expiring_soon` KPI had no status filter
     * while the list beneath it did, so suspended and cancelled licenses
     * inflated the tile and then never appeared in the table.
     *
     * @return list<array<string, mixed>>
     */
    public function expiring(int $days = 30, int $limit = 10, array $filters = []): array
    {
        $params = [];
        $where = [
            self::activeSql('l'),
            // `BETWEEN` already excludes NULL and anything already past, so this
            // narrows activeSql's perpetual-licence allowance to real expiries.
            'l.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ' . max(1, $days) . ' DAY)',
        ];

        foreach (['product' => 'l.product', 'variant' => 'l.variant'] as $key => $column) {
            if (! empty($filters[$key])) {
                $where[] = "{$column} = ?";
                $params[] = $filters[$key];
            }
        }

        // Clamped: a caller-supplied limit must not be able to pull the whole table.
        $limit = min(self::EXPIRING_MAX_LIMIT, max(1, $limit));

        return $this->db->fetchAll(
            'SELECT l.id, l.license_key, l.expires_at, l.status, l.product, l.variant,
                    c.name AS customer_name, c.email AS customer_email
               FROM licenses l
               LEFT JOIN customers c ON c.id = l.customer_id
              WHERE ' . implode(' AND ', $where) . '
              ORDER BY l.expires_at ASC
              LIMIT ' . $limit,
            $params
        );
    }

    /**
     * How many licenses expire within `$days` — the KPI that must agree with
     * {@see expiring()} for the same window.
     */
    public function expiringCount(int $days = 30, array $filters = []): int
    {
        $params = [];
        $where = [
            self::activeSql('l'),
            'l.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ' . max(1, $days) . ' DAY)',
        ];

        foreach (['product' => 'l.product', 'variant' => 'l.variant'] as $key => $column) {
            if (! empty($filters[$key])) {
                $where[] = "{$column} = ?";
                $params[] = $filters[$key];
            }
        }

        $row = $this->db->fetch(
            'SELECT COUNT(*) AS c FROM licenses l WHERE ' . implode(' AND ', $where),
            $params
        );

        return (int) ($row['c'] ?? 0);
    }

    /**
     * License counts grouped by product, in Catalog order, always four rows so
     * the Product x Variant matrix never loses an axis. Colours come from
     * Catalog rather than a local map (fix for B3 — `node` had no colour and
     * silently rendered as the app's blue).
     *
     * @return array<string, int>
     */
    public function countsByProduct(array $filters = []): array
    {
        $params = [];
        $where = ['l.deleted_at IS NULL'];

        if (! empty($filters['variant'])) {
            $where[] = 'l.variant = ?';
            $params[] = $filters['variant'];
        }
        if (! empty($filters['status'])) {
            $where[] = self::derivedStatusSql('l') . ' = ?';
            $params[] = $filters['status'];
        }

        $rows = $this->db->fetchAll(
            'SELECT l.product, COUNT(*) AS c
               FROM licenses l
              WHERE ' . implode(' AND ', $where) . '
              GROUP BY l.product',
            $params
        );

        $out = array_fill_keys(Catalog::keys(), 0);
        foreach ($rows as $row) {
            $out[(string) $row['product']] = (int) $row['c'];
        }

        return $out;
    }

    /**
     * The Product x Variant matrix: one cell per (product, variant) pair.
     *
     * Every cell is returned, including ones with no licenses, because "zero
     * licenses" and "not offered" are different states and the UI must be able
     * to render them differently. A cell is *offered* when its product has at
     * least one active plan — plans are variant-agnostic, so the variant only
     * decides currency and gateways.
     *
     * @return array{
     *   products: list<string>,
     *   variants: list<string>,
     *   offered: array<string, bool>,
     *   plan_counts: array<string, int>,
     *   cells: array<string, array<string, array<string, mixed>>>
     * }
     */
    public function matrix(array $filters = []): array
    {
        $products = Catalog::keys();
        $variants = VariantResolver::all();

        $planCounts = $this->activePlanCounts();

        $params = [];
        $where = ['l.deleted_at IS NULL'];

        if (! empty($filters['status'])) {
            $where[] = self::derivedStatusSql('l') . ' = ?';
            $params[] = $filters['status'];
        }

        $rows = $this->db->fetchAll(
            'SELECT l.product, l.variant,
                    COUNT(*) AS total,
                    SUM(CASE WHEN ' . self::activeSql('l') . ' THEN 1 ELSE 0 END) AS active
               FROM licenses l
              WHERE ' . implode(' AND ', $where) . '
              GROUP BY l.product, l.variant',
            $params
        );

        $index = [];
        foreach ($rows as $row) {
            $index[(string) $row['product'] . '|' . (string) $row['variant']] = $row;
        }

        $offered = [];
        foreach ($products as $product) {
            foreach ($variants as $variant) {
                $offered[$product . '|' . $variant] =
                    VariantResolver::isOffered($product, $planCounts);
            }
        }

        $cells = [];
        foreach ($products as $product) {
            $cells[$product] = [];
            foreach ($variants as $variant) {
                $row = $index[$product . '|' . $variant] ?? null;
                $cells[$product][$variant] = [
                    'total'    => (int) ($row['total'] ?? 0),
                    'active'   => (int) ($row['active'] ?? 0),
                    'offered'  => $offered[$product . '|' . $variant],
                    'licenses' => '/admin/licenses?product=' . rawurlencode($product)
                        . '&variant=' . rawurlencode($variant),
                    'request'  => '/custom-order?product=' . rawurlencode($product)
                        . '&variant=' . rawurlencode($variant),
                ];
            }
        }

        return [
            'products'    => $products,
            'variants'    => $variants,
            'offered'     => $offered,
            'plan_counts' => $planCounts,
            'cells'       => $cells,
        ];
    }

    /**
     * Active plan count per product — the "is this cell purchasable" signal.
     *
     * @return array<string, int>
     */
    public function activePlanCounts(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT product, COUNT(*) AS c FROM plans WHERE active = 1 GROUP BY product'
        );

        $out = array_fill_keys(Catalog::keys(), 0);
        foreach ($rows as $row) {
            $out[(string) $row['product']] = (int) $row['c'];
        }

        return $out;
    }

    /**
     * License counts split by variant, in VariantResolver order.
     *
     * @return array<string, int>
     */
    public function countsByVariant(?string $product = null): array
    {
        $params = [];
        $where = ['l.deleted_at IS NULL'];

        if ($product !== null && $product !== '') {
            $where[] = 'l.product = ?';
            $params[] = $product;
        }

        $rows = $this->db->fetchAll(
            'SELECT l.variant, COUNT(*) AS c
               FROM licenses l
              WHERE ' . implode(' AND ', $where) . '
              GROUP BY l.variant',
            $params
        );

        $out = array_fill_keys(VariantResolver::all(), 0);
        foreach ($rows as $row) {
            $key = VariantResolver::normalize($row['variant'] ?? null) ?? VariantResolver::INT;
            $out[$key] = (int) $row['c'];
        }

        return $out;
    }

    /**
     * Labels + colours for a derived status value, with a safe fallback for
     * unexpected data rather than a blank pill.
     *
     * @return array{key: string, label: string, tone: string}
     */
    public static function statusMeta(?string $status): array
    {
        $key = strtolower(trim((string) $status));

        $meta = self::STATUSES[$key] ?? null;

        return [
            'key'   => $meta !== null ? $key : ($key !== '' ? $key : 'unknown'),
            'label' => $meta['label'] ?? (ucfirst($key) ?: 'Unknown'),
            'tone'  => $meta['tone'] ?? 'muted',
        ];
    }
}