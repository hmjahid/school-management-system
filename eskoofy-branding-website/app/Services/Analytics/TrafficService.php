<?php
declare(strict_types=1);

namespace App\Services\Analytics;

use App\Core\DatabaseInterface;

/**
 * Traffic read models: the visitor → product → checkout → paid funnel and the
 * visitor country split.
 *
 * Visitors are logged by {@see \App\Services\VisitorLogger} into `visitors`,
 * which is the only place the public site records intent before a sale. Payments
 * are the paid end of the funnel. Nothing else joins the two, so this service is
 * where "traffic that became revenue" is measured.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §6.3 (W14, W19)
 */
final class TrafficService
{
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /**
     * The visitor-to-sale funnel for the window.
     *
     * @return list<array{key: string, label: string, value: int}>
     */
    public function funnel(DateRange $range): array
    {
        // Each date predicate appends its own two placeholders, so each stage
        // owns its own params array. `DateRange::where()` takes $params by
        // reference, which means it must be a real variable — never an inline
        // `$params = []` inside the argument list.
        $pVisitors = [];
        $visitors = $this->scalar(
            'SELECT COUNT(*) AS c FROM visitors v
              WHERE v.is_bot = 0 AND ' . $range->where('v.visited_at', $pVisitors),
            $pVisitors
        );

        $pEngaged = [];
        $engaged = $this->scalar(
            "SELECT COUNT(*) AS c FROM visitors v
              WHERE v.is_bot = 0 AND " . $range->where('v.visited_at', $pEngaged) . "
                AND (v.path LIKE '/products/%' OR v.path LIKE '/pricing%'
                     OR v.path LIKE '/compare%' OR v.path LIKE '/choose%')",
            $pEngaged
        );

        $pCheckout = [];
        $checkout = $this->scalar(
            "SELECT COUNT(*) AS c FROM visitors v
              WHERE v.is_bot = 0 AND " . $range->where('v.visited_at', $pCheckout) . "
                AND v.path LIKE '/checkout%'",
            $pCheckout
        );

        $pPaid = [];
        $paid = $this->scalar(
            "SELECT COUNT(*) AS c FROM payments
              WHERE status = 'paid' AND paid_at IS NOT NULL AND " . $range->where('paid_at', $pPaid),
            $pPaid
        );

        return [
            ['key' => 'visitors', 'label' => 'Visitors',      'value' => $visitors],
            ['key' => 'engaged',  'label' => 'Product views', 'value' => $engaged],
            ['key' => 'checkout', 'label' => 'Checkout',      'value' => $checkout],
            ['key' => 'paid',     'label' => 'Paid',          'value' => $paid],
        ];
    }

    /**
     * Visitor country split for the window, top N plus an "Other" bucket.
     *
     * @return array<string, int> country => visits, descending
     */
    public function countries(DateRange $range, int $limit = 6): array
    {
        $all = $this->countryCounts($range);

        $out = [];
        $i = 0;
        foreach ($all as $country => $visits) {
            if ($i++ < $limit) {
                $out[$country] = $visits;
            } else {
                $out['Other'] = ($out['Other'] ?? 0) + $visits;
            }
        }

        return $out;
    }

    /**
     * Top countries by visits in the window, with the prior window alongside
     * for a delta — the dashboard's "what's trending" rows.
     *
     * @return list<array{country: string, visits: int, prior: int, delta_pct: float|null}>
     */
    public function topCountries(DateRange $range, int $limit = 3): array
    {
        $limit = max(1, min(10, $limit));

        $current = $this->countryCounts($range);
        if ($current === []) {
            return [];
        }

        $prior = $this->countryCounts($range->prior());

        $out = [];
        $i = 0;
        foreach ($current as $country => $visits) {
            if ($i++ >= $limit) {
                break;
            }

            $prev = (int) ($prior[$country] ?? 0);

            $out[] = [
                'country'   => (string) $country,
                'visits'    => (int) $visits,
                'prior'     => $prev,
                'delta_pct' => $prev > 0 ? round(((($visits - $prev) / $prev) * 100), 1) : null,
            ];
        }

        return $out;
    }

    /**
     * Raw visitor counts per country, descending, bots excluded. An empty
     * country collapses into "Unknown" rather than a blank legend entry.
     *
     * @return array<string, int> country => visits, descending
     */
    private function countryCounts(DateRange $range): array
    {
        $params = [];

        $rows = $this->db->fetchAll(
            "SELECT COALESCE(NULLIF(v.country, ''), 'Unknown') AS country, COUNT(*) AS c
               FROM visitors v
              WHERE v.is_bot = 0 AND " . $range->where('v.visited_at', $params) . '
              GROUP BY country
              ORDER BY c DESC',
            $params
        );

        $out = [];
        foreach ($rows as $row) {
            $out[(string) ($row['country'] ?? 'Unknown')] = (int) ($row['c'] ?? 0);
        }

        return $out;
    }

    /** @param list<string> $params */
    private function scalar(string $sql, array $params): int
    {
        $row = $this->db->fetch($sql, $params);

        return (int) ($row['c'] ?? 0);
    }
}
