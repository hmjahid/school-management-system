<?php
declare(strict_types=1);

namespace App\Services\Analytics;

use App\Core\DatabaseInterface;
use App\Gateways\GatewayFactory;
use App\Services\VariantResolver;

/**
 * Revenue read models: trends, variant split, MRR, and the MRR-vs-collected
 * reconciliation.
 *
 * Two fixes live here.
 *
 * B5 — the old 12-month series was anchored to the first of the month
 *   (`DATE_SUB(DATE_FORMAT(NOW(),'%Y-%m-01'), INTERVAL 11 MONTH)`), which can
 *   drop a partial month at the left edge. Bucketing is now a rolling window
 *   plus a PHP zero-fill over {@see DateRange::buckets()}, so the series is dense
 *   and its labels always match the window.
 *
 * B4 — MRR came from `subscriptions ⋈ plans` while revenue came from `payments`,
 *   and the two were never reconciled. The dashboard could show non-zero MRR
 *   against zero collected revenue with no explanation. {@see reconcile()} now
 *   states that gap explicitly instead of leaving two numbers side by side.
 *
 * B12 — MRR/ARR ignored `plans.active`, so a retired plan kept inflating the
 *   recurring-revenue figures.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §7.3
 */
final class RevenueService
{
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /**
     * SQL expression that converts a money column to USD.
     *
     * `payments.currency` mixes BDT and USD rows, so a bare `SUM(amount)`
     * adds takings together across currencies — a nonsensical total (BDT 144
     * summed with USD 45) that then gets rendered with a `$` symbol. MRR is
     * USD-canonical, so every collected figure is normalised to USD here to
     * keep the reconciliation comparable.
     *
     * @param string $column        qualified money column, e.g. `p.amount`
     * @param string $currencyColumn qualified currency column, e.g. `p.currency`
     */
    private function usdExpression(string $column, string $currencyColumn = 'currency'): string
    {
        return "CASE WHEN {$column} IS NULL THEN 0"
            . " WHEN LOWER(COALESCE({$currencyColumn}, 'USD')) IN ('BDT', '৳')"
            . ' THEN ' . $column . ' / ' . GatewayFactory::bdtRate()
            . " ELSE {$column} END";
    }

    /**
     * Total paid revenue inside a window, with the prior window alongside.
     *
     * @param array{product?: ?string, variant?: ?string} $filters
     * @return array{current: float, prior: float, delta_pct: float|null}
     */
    public function totals(DateRange $range, array $filters = []): array
    {
        $current = $this->sumPaid($range, $filters);
        $prior = $this->sumPaid($range->prior(), $filters);

        return [
            'current'   => $current,
            'prior'     => $prior,
            'delta_pct' => $prior > 0 ? round((($current - $prior) / $prior) * 100, 1) : null,
        ];
    }

    /**
     * Paid revenue bucketed across the window, zero-filled so no month vanishes.
     *
     * @param array{product?: ?string, variant?: ?string} $filters
     * @return list<array{key: string, label: string, value: float}>
     */
    public function trend(DateRange $range, array $filters = []): array
    {
        [$from, $where, $params] = $this->paymentsQuery('paid_at', $range, $filters);
        $qualified = str_starts_with($from, 'payments p') ? 'p.' : '';

        $rows = $this->db->fetchAll(
            'SELECT ' . $range->sqlBucketExpression($qualified . 'paid_at') . ' AS bucket,
                    COALESCE(SUM(' . $this->usdExpression($qualified . 'amount', $qualified . 'currency') . '), 0) AS total
               FROM ' . $from . '
              WHERE ' . $where . '
              GROUP BY bucket
              ORDER BY bucket ASC',
            $params
        );

        $byBucket = [];
        foreach ($rows as $row) {
            $byBucket[(string) $row['bucket']] = (float) $row['total'];
        }

        $out = [];
        foreach ($range->buckets() as $start) {
            $key = $start->format($range->keyFormat());
            $out[] = [
                'key'   => $key,
                'label' => $range->bucketLabel($start),
                'value' => $byBucket[$key] ?? 0.0,
            ];
        }

        return $out;
    }

    /**
     * Paid revenue split by variant, in VariantResolver order (BD first).
     *
     * @return array<string, float>
     */
    public function byVariant(DateRange $range): array
    {
        $params = [];
        $where = ["status = 'paid'", 'paid_at IS NOT NULL', $range->where('paid_at', $params)];

        $rows = $this->db->fetchAll(
            'SELECT variant, COALESCE(SUM(' . $this->usdExpression('amount') . '), 0) AS total
               FROM payments
              WHERE ' . implode(' AND ', $where) . '
              GROUP BY variant',
            $params
        );

        $out = array_fill_keys(VariantResolver::all(), 0.0);
        foreach ($rows as $row) {
            $key = VariantResolver::normalize($row['variant'] ?? null) ?? VariantResolver::INT;
            $out[$key] += (float) $row['total'];
        }

        return $out;
    }

    /**
     * Paid revenue split by product, in Catalog order, always four entries.
     *
     * Payments have no `product` column — it comes from the plan a payment
     * settles. Payments whose plan was deleted land in a null bucket and are
     * reported separately rather than silently dropped.
     *
     * @return array{by_product: array<string, float>, unattributed: float}
     */
    public function byProduct(DateRange $range): array
    {
        $params = [];
        $where = ["p.status = 'paid'", 'p.paid_at IS NOT NULL', $range->where('p.paid_at', $params)];

        $rows = $this->db->fetchAll(
            'SELECT pl.product, COALESCE(SUM(' . $this->usdExpression('p.amount', 'p.currency') . '), 0) AS total
               FROM payments p
               LEFT JOIN plans pl ON pl.id = p.plan_id
              WHERE ' . implode(' AND ', $where) . '
              GROUP BY pl.product',
            $params
        );

        $byProduct = array_fill_keys(\App\Services\Catalog::keys(), 0.0);
        $unattributed = 0.0;

        foreach ($rows as $row) {
            $product = $row['product'] ?? null;
            if ($product !== null && isset($byProduct[(string) $product])) {
                $byProduct[(string) $product] += (float) $row['total'];
            } else {
                $unattributed += (float) $row['total'];
            }
        }

        return ['by_product' => $byProduct, 'unattributed' => $unattributed];
    }

    /**
     * Monthly recurring revenue and annual run rate.
     *
     * A yearly plan contributes `price / 12`; a monthly plan contributes `price`.
     * Retired plans (`plans.active = 0`) are excluded — see B12.
     *
     * Product/variant filters apply here too, via the same joins the collected
     * revenue uses: `plans.product` and the license's `variant` snapshot.
     * Without this the reconciliation compared a filtered collection against a
     * global MRR and reported a gap that meant nothing.
     *
     * @param array{product?: ?string, variant?: ?string} $filters
     * @return array{mrr: float, arr: float, active_subscriptions: int}
     */
    public function mrr(array $filters = []): array
    {
        $params = [];
        $from   = 'subscriptions s JOIN plans pl ON pl.id = s.plan_id';
        $where  = ["s.status = 'active'", 'pl.active = 1'];

        if (! empty($filters['product'])) {
            $where[] = 'pl.product = ?';
            $params[] = $filters['product'];
        }

        if (! empty($filters['variant'])) {
            // The variant a subscription was sold under is the license snapshot
            // (immutable), not the customer's current market.
            $from .= ' JOIN licenses lic ON lic.id = s.license_id';
            $where[] = 'lic.variant = ?';
            $params[] = $filters['variant'];
        }

        $rows = $this->db->fetchAll(
            'SELECT s.id, pl.price, pl.period
               FROM ' . $from . '
              WHERE ' . implode(' AND ', $where),
            $params
        );

        $mrr = 0.0;
        foreach ($rows as $row) {
            $price = (float) $row['price'];
            $mrr += in_array(strtolower((string) $row['period']), ['yearly', 'annual'], true)
                ? $price / 12
                : $price;
        }

        return [
            'mrr'                 => round($mrr, 2),
            'arr'                 => round($mrr * 12, 2),
            'active_subscriptions' => count($rows),
        ];
    }

    /**
     * All-time collected revenue in USD, irrespective of the selected range.
     *
     * Used for the "Collected (all time)" figure. Currency-normalised for the
     * same reason as {@see totals()}; it must stay comparable with MRR.
     */
    public function lifetimeTotal(): float
    {
        $row = $this->db->fetch(
            'SELECT COALESCE(SUM(' . $this->usdExpression('amount') . '), 0) AS total
               FROM payments
              WHERE status = \'paid\''
        );

        return (float) ($row['total'] ?? 0);
    }

    /**
     * Reconcile MRR against money actually collected.
     *
     * MRR and revenue are different things measured on different tables:
     * MRR is the *committed* recurring value of live subscriptions, revenue is
     * *collected* cash from paid payments. In a healthy business they track each
     * other; when they diverge, the dashboard has to say so rather than print
     * two unrelated numbers side by side.
     *
     * @return array{
     *   mrr: float, arr: float, active_subscriptions: int,
     *   collected_window: float, collected_prior: float,
     *   gap: float, gap_pct: float|null, status: string, note: string
     * }
     */
    public function reconcile(DateRange $range, array $filters = []): array
    {
        // Scope MRR the same way the collected figure is scoped, otherwise a
        // filtered view reconciles a slice against the whole.
        $mrr = $this->mrr($filters);
        $totals = $this->totals($range, $filters);

        $collected = $totals['current'];
        $gap = round($collected - ($mrr['mrr'] * max(1, $range->spanInDays() / 30)), 2);

        // Treat "within 20%" of the expected monthly run-rate as reconciled.
        $expected = $mrr['mrr'] * max(1, $range->spanInDays() / 30);
        $tolerance = max(1.0, $expected * 0.2);

        if ($expected <= 0 && $collected <= 0) {
            $status = 'empty';
            $note = 'No active subscriptions and no collected revenue in this window.';
        } elseif ($expected <= 0) {
            $status = 'revenue_without_mrr';
            $note = 'Payments were collected but no active subscriptions exist — '
                . 'one-off or already-cancelled sales.';
        } elseif ($collected <= 0) {
            $status = 'mrr_without_revenue';
            $note = 'Active subscriptions exist but nothing was collected in this window — '
                . 'likely billed outside it, or awaiting payment.';
        } elseif (abs($gap) <= $tolerance) {
            $status = 'reconciled';
            $note = 'Collected revenue is in line with committed MRR for this window.';
        } else {
            $status = 'divergent';
            $note = $gap >= 0
                ? 'Collected revenue exceeds committed MRR — check for one-off sales.'
                : 'Collected revenue falls short of committed MRR — check for failed or late payments.';
        }

        return [
            'mrr'                  => $mrr['mrr'],
            'arr'                  => $mrr['arr'],
            'active_subscriptions' => $mrr['active_subscriptions'],
            'collected_window'     => $collected,
            'collected_prior'      => $totals['prior'],
            'gap'                  => $gap,
            'gap_pct'              => $totals['delta_pct'],
            'status'               => $status,
            'note'                 => $note,
        ];
    }

    /**
     * Gateway mix for the window — which payment methods actually carry revenue.
     *
     * @return array<string, float>
     */
    public function byGateway(DateRange $range): array
    {
        $params = [];
        $where = ["status = 'paid'", 'paid_at IS NOT NULL', $range->where('paid_at', $params)];

        $rows = $this->db->fetchAll(
            'SELECT gateway, COALESCE(SUM(' . $this->usdExpression('amount') . '), 0) AS total
               FROM payments
              WHERE ' . implode(' AND ', $where) . '
              GROUP BY gateway',
            $params
        );

        $out = [];
        foreach ($rows as $row) {
            $gateway = strtolower(trim((string) ($row['gateway'] ?? ''))) ?: 'manual';
            $out[$gateway] = ($out[$gateway] ?? 0.0) + (float) $row['total'];
        }

        arsort($out);

        return $out;
    }

    /**
 * Build the FROM + WHERE for a payments aggregate.
 *
 * `payments` carries `variant` directly but has **no** `product` column — the
 * product comes from the plan a payment settles. So a product filter requires
 * the join, and every query here goes through this one method to get it right.
 *
     * @param array{product?: ?string, variant?: ?string} $filters
     * @return array{0: string, 1: string, 2: list<string>} [from, where, params]
     */
    private function paymentsQuery(string $dateColumn, DateRange $range, array $filters = []): array
    {
        // `payments` has no `product` column, so a product filter needs the join
        // — and with it every column reference has to be qualified. Decide that
        // first, then build the WHERE and the bound params exactly once: calling
        // DateRange::where() twice appends a second set of date params that the
        // SQL has no placeholders for ("Invalid parameter number").
        $joined = ! empty($filters['product']);
        $from   = $joined ? 'payments p JOIN plans pl ON pl.id = p.plan_id' : 'payments';

        $q      = $joined ? 'p.' : '';
        $params = [];

        $where = [
            $q . "status = 'paid'",
            $q . "{$dateColumn} IS NOT NULL",
            $range->where($q . $dateColumn, $params),
        ];

        if ($joined) {
            $where[] = 'pl.product = ?';
            $params[] = $filters['product'];
        }

        if (! empty($filters['variant'])) {
            $where[] = $q . 'variant = ?';
            $params[] = $filters['variant'];
        }

        return [$from, implode(' AND ', $where), $params];
    }

    /**
     * Collected revenue in a window, honouring product/variant filters.
     *
     * @param array{product?: ?string, variant?: ?string} $filters
     */
    private function sumPaid(DateRange $range, array $filters = []): float
    {
        [$from, $where, $params] = $this->paymentsQuery('paid_at', $range, $filters);

        $row = $this->db->fetch(
            'SELECT COALESCE(SUM(' . $this->usdExpression($from === 'payments' ? 'amount' : 'p.amount', $from === 'payments' ? 'currency' : 'p.currency') . '), 0) AS total
               FROM ' . $from . ' WHERE ' . $where,
            $params
        );

        return (float) ($row['total'] ?? 0);
    }
}