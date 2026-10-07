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
        foreach ($rows as $i => $row) {
            $country = (string) ($row['country'] ?? 'Unknown');
            if ($i < $limit) {
                $out[$country] = (int) $row['c'];
            } else {
                $out['Other'] = ($out['Other'] ?? 0) + (int) $row['c'];
            }
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
