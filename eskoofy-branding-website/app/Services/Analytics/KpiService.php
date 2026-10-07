<?php
declare(strict_types=1);

namespace App\Services\Analytics;

use App\Core\DatabaseInterface;

/**
 * Dense, zero-filled sparkline series for the KPI tiles and flow widgets.
 *
 * Every series is bucketed by the active {@see DateRange} and then zero-filled
 * over {@see DateRange::buckets()}, so a bucket with no rows renders as a real
 * zero instead of a phantom gap — the same rule the revenue chart has always
 * followed.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §6.3 (W1–W5, W13)
 */
final class KpiService
{
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /**
     * New licenses created in the window.
     *
     * @param array{product?: ?string, variant?: ?string} $filters
     * @return list<array{key: string, label: string, value: float}>
     */
    public function licensesSeries(DateRange $range, array $filters = []): array
    {
        $params = [];
        $where = ['l.deleted_at IS NULL', $range->where('l.created_at', $params)];

        if (!empty($filters['product'])) {
            $where[] = 'l.product = ?';
            $params[] = $filters['product'];
        }
        if (!empty($filters['variant'])) {
            $where[] = 'l.variant = ?';
            $params[] = $filters['variant'];
        }

        return $this->denseSeries($range, $this->db->fetchAll(
            'SELECT ' . $range->sqlBucketExpression('l.created_at') . ' AS bucket, COUNT(*) AS total
               FROM licenses l
              WHERE ' . implode(' AND ', $where) . '
              GROUP BY bucket',
            $params
        ));
    }

    /**
     * New customers created in the window. Customers carry no product/variant
     * filter of their own, so only the date window applies.
     *
     * @return list<array{key: string, label: string, value: float}>
     */
    public function customersSeries(DateRange $range): array
    {
        $params = [];
        $where = ['c.deleted_at IS NULL', $range->where('c.created_at', $params)];

        return $this->denseSeries($range, $this->db->fetchAll(
            'SELECT ' . $range->sqlBucketExpression('c.created_at') . ' AS bucket, COUNT(*) AS total
               FROM customers c
              WHERE ' . implode(' AND ', $where) . '
              GROUP BY bucket',
            $params
        ));
    }

    /**
     * License activations in the window (the "demand" side of the funnel).
     *
     * @param array{product?: ?string, variant?: ?string} $filters
     * @return list<array{key: string, label: string, value: float}>
     */
    public function activationsSeries(DateRange $range, array $filters = []): array
    {
        $params = [];
        $where = ['a.activated_at IS NOT NULL', $range->where('a.activated_at', $params)];

        $join = 'license_activations a LEFT JOIN licenses l ON l.id = a.license_id';

        if (!empty($filters['product'])) {
            $where[] = 'l.product = ?';
            $params[] = $filters['product'];
        }
        if (!empty($filters['variant'])) {
            $where[] = 'l.variant = ?';
            $params[] = $filters['variant'];
        }

        return $this->denseSeries($range, $this->db->fetchAll(
            'SELECT ' . $range->sqlBucketExpression('a.activated_at') . ' AS bucket, COUNT(*) AS total
               FROM ' . $join . '
              WHERE ' . implode(' AND ', $where) . '
              GROUP BY bucket',
            $params
        ));
    }

    /**
     * Deactivations in the window — the second line of the activation-flow chart.
     *
     * @return list<array{key: string, label: string, value: float}>
     */
    public function deactivationsSeries(DateRange $range): array
    {
        $params = [];
        $where = ['a.deactivated_at IS NOT NULL', $range->where('a.deactivated_at', $params)];

        return $this->denseSeries($range, $this->db->fetchAll(
            'SELECT ' . $range->sqlBucketExpression('a.deactivated_at') . ' AS bucket, COUNT(*) AS total
               FROM license_activations a
              WHERE ' . implode(' AND ', $where) . '
              GROUP BY bucket',
            $params
        ));
    }

    /**
     * Repeat purchases in the window: paid payments whose license was created
     * before the payment, i.e. the customer already owned the product. The
     * complement is a first purchase. A correlated subquery is used rather than
     * a window function so this runs on MySQL 5.7 (shared hosting).
     *
     * @param array{product?: ?string, variant?: ?string} $filters
     * @return array{new: list<array<string, mixed>>, renewal: list<array<string, mixed>>}
     */
    public function revenueSplit(DateRange $range, array $filters = []): array
    {
        $params = [];
        $joined = !empty($filters['product']);
        $from = $joined ? 'payments p JOIN licenses l ON l.id = p.license_id' : 'payments p';
        $q = 'p.';

        $where = [$q . "status = 'paid'", $q . 'paid_at IS NOT NULL', $range->where($q . 'paid_at', $params)];

        if ($joined) {
            $where[] = 'l.product = ?';
            $params[] = $filters['product'];
        }
        if (!empty($filters['variant'])) {
            $where[] = $q . 'variant = ?';
            $params[] = $filters['variant'];
        }

        $renewalPredicate = '(' . $q . 'license_id IS NOT NULL AND EXISTS (
            SELECT 1 FROM payments p2
             WHERE p2.license_id = ' . $q . 'license_id
               AND p2.status = \'paid\'
               AND p2.paid_at IS NOT NULL
               AND p2.paid_at < ' . $q . 'paid_at
        ))';

        $rows = $this->db->fetchAll(
            'SELECT ' . $range->sqlBucketExpression($q . 'paid_at') . ' AS bucket,
                    SUM(CASE WHEN ' . $renewalPredicate . ' THEN 1 ELSE 0 END) AS renewals,
                    SUM(CASE WHEN ' . $renewalPredicate . ' THEN 0 ELSE 1 END) AS fresh
               FROM ' . $from . '
              WHERE ' . implode(' AND ', $where) . '
              GROUP BY bucket',
            $params
        );

        return [
            'new'     => $this->denseSeries($range, $rows, 'fresh'),
            'renewal' => $this->denseSeries($range, $rows, 'renewals'),
        ];
    }

    /**
     * Map grouped rows onto every bucket in the range, defaulting to zero.
     *
     * @param list<array<string, mixed>> $rows
     * @param string $valueKey column holding the aggregate
     * @return list<array{key: string, label: string, value: float}>
     */
    private function denseSeries(DateRange $range, array $rows, string $valueKey = 'total'): array
    {
        $by = [];
        foreach ($rows as $row) {
            $by[(string) ($row['bucket'] ?? '')] = (float) ($row[$valueKey] ?? 0);
        }

        $out = [];
        foreach ($range->buckets() as $start) {
            $key = $start->format($range->keyFormat());
            $out[] = [
                'key'   => $key,
                'label' => $range->bucketLabel($start),
                'value' => $by[$key] ?? 0.0,
            ];
        }

        return $out;
    }
}
