<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Settings;
use App\Services\Analytics\ActivityFeed;
use App\Services\Analytics\DateRange;
use App\Services\Analytics\KpiService;
use App\Services\Analytics\LicenseService;
use App\Services\Analytics\RevenueService;
use App\Services\Analytics\TrafficService;
use App\Services\Cache;
use App\Services\Catalog;
use App\Services\LicenseReminderService;
use App\Services\ProductMatrix;
use App\Services\VariantResolver;

class DashboardController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $db = Database::getInstance();

        // Best-effort expiring-soon reminders (non-blocking).
        //
        // TODO(phase-5): this fires customer email on every dashboard page view,
        // so an admin refreshing the page is a mail loop. It moves to
        // routes/cron.php behind App\Services\Cron\ExpiringNotifier; leaving it
        // here until that lands keeps renewal emails working in the meantime.
        (new LicenseReminderService())->notifyExpiring();

        $range = DateRange::fromRequest($_GET);
        $filters = self::filtersFromRequest($_GET);

        $licenses = new LicenseService($db);
        $revenue = new RevenueService($db);

        // Derived-status counts replace the old raw `GROUP BY status`, whose
        // legend carried an `expired` swatch no row could ever hold while
        // past-expiry `active` rows landed in the green wedge (bug B2).
        $licenseByStatus = $licenses->countsByStatus($filters);

        // Product counts come from Catalog, which knows all four products —
        // the old local `$productColors` map had no `node` entry, so the Node.js
        // bar rendered in the app's blue (bug B3).
        $licenseByProduct = $licenses->countsByProduct($filters);

        // `expiring_soon` now uses the same predicate as the list beneath it, so
        // the tile and the table can no longer disagree (bug B1).
        $expiringLicenses = $licenses->expiring(30, 10, $filters);

        $stats = [
            'customers'    => (int) $db->count('customers', 'deleted_at IS NULL'),
            'licenses'     => array_sum($licenseByStatus),
            'active_licenses' => $licenseByStatus[LicenseService::STATUS_ACTIVE] ?? 0,
            // All-time collected, normalised to USD by RevenueService — a raw
            // SUM(amount) here would add BDT rows to USD rows and render the
            // nonsense total with a '$' symbol.
            'revenue'      => $revenue->lifetimeTotal(),
            'unread_messages' => (int) $db->count('contact_messages', 'read_at IS NULL'),
            'expiring_soon' => $licenses->expiringCount(30, $filters),
        ];

        $mrr = $revenue->mrr($filters);
        $revenueTotals = $revenue->totals($range, $filters);

        $stats['revenue_this_month'] = $revenueTotals['current'];
        $stats['revenue_prev_month'] = $revenueTotals['prior'];
        $stats['monthly_licenses'] = $this->countLicensesByPeriod('monthly', $filters);
        $stats['yearly_licenses'] = $this->countLicensesByPeriod('yearly', $filters);
        $stats['active_subscriptions'] = $mrr['active_subscriptions'];
        // `pl.active = 1` is now respected — a retired plan no longer inflates
        // recurring revenue (bug B12).
        $stats['mrr'] = $mrr['mrr'];
        $stats['arr'] = $mrr['arr'];
        $stats['revenue_pending'] = (float) ($db->fetch("SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE status = 'pending'")['total'] ?? 0);

        $revenueTrend = Cache::remember(
            'revenue',
            'trend:' . $range->cacheKey() . ':' . md5(json_encode($filters)),
            static fn (): array => $revenue->trend($range, $filters)
        );

        $recentPayments = $db->fetchAll(
            'SELECT p.*, c.name AS customer_name FROM payments p
             LEFT JOIN customers c ON p.customer_id = c.id
             ORDER BY p.id DESC LIMIT 8'
        );

        $recentLicenses = $db->fetchAll(
            'SELECT l.*, c.name AS customer_name FROM licenses l
             LEFT JOIN customers c ON l.customer_id = c.id
             WHERE l.deleted_at IS NULL
             ORDER BY l.id DESC LIMIT 8'
        );

        $unreadMessages = $db->fetchAll(
            'SELECT * FROM contact_messages WHERE read_at IS NULL ORDER BY id DESC LIMIT 8'
        );

        // Only the deep-dive widgets below do extra work; the summary numbers
        // above stay exactly as they were.
        $kpi = new KpiService($db);
        $traffic = new TrafficService($db);
        $feed = new ActivityFeed($db);

        $licensesSpark = $kpi->licensesSeries($range, $filters);
        $customersSpark = $kpi->customersSeries($range);
        $activationsSpark = $kpi->activationsSeries($range, $filters);
        $deactivationsSpark = $kpi->deactivationsSeries($range);
        $revenueSplit = $kpi->revenueSplit($range, $filters);

        $revenueByVariant = $revenue->byVariant($range);
        $mrrByProduct = $revenue->mrrByProduct($filters);
        $gwMix = $revenue->byGateway($range);
        $topCustomers = $revenue->topCustomers($range, 5, $filters);

        $funnel = $traffic->funnel($range);
        $countries = $traffic->countries($range);
        $renewalRisk = $licenses->renewalRisk(6, $filters);

        // "What's trending": top plans / countries / fastest-growing gateway,
        // range- and filter-aware like every other widget, cached under the
        // same groups and TTLs as their parent aggregates.
        $trending = [
            'plans'     => Cache::remember(
                'revenue',
                'trending-plans:' . $range->cacheKey() . ':' . md5(json_encode($filters)),
                static fn (): array => $revenue->topPlans($range, 3, $filters)
            ),
            'countries' => Cache::remember(
                'traffic',
                'trending-countries:' . $range->cacheKey(),
                static fn (): array => $traffic->topCountries($range, 3)
            ),
            'gateway'   => Cache::remember(
                'revenue',
                'trending-gateway:' . $range->cacheKey(),
                static fn (): array => $revenue->fastestGrowingGateway($range)
            ),
        ];

        // Prior-period counts for the KPI deltas.
        $customersNow = $this->countInRange('customers', 'created_at', $range, $filters);
        $customersPrev = $this->countInRange('customers', 'created_at', $range->prior(), $filters);
        $licensesNow = $this->countInRange('licenses', 'created_at', $range, $filters);
        $licensesPrev = $this->countInRange('licenses', 'created_at', $range->prior(), $filters);
        $activationsNow = $this->countInRange('license_activations', 'activated_at', $range, $filters);
        $activationsPrev = $this->countInRange('license_activations', 'activated_at', $range->prior(), $filters);

        $renewalTotal = (int) array_sum(array_column($revenueSplit['new'], 'value'))
            + (int) array_sum(array_column($revenueSplit['renewal'], 'value'));

        $kpis = [
            'revenue' => [
                'current' => $revenueTotals['current'],
                'delta'   => $revenueTotals['delta_pct'],
                'spark'   => array_column($revenueTrend, 'value'),
            ],
            'customers' => [
                'current' => $customersNow,
                'prev'    => $customersPrev,
                'spark'   => array_column($customersSpark, 'value'),
            ],
            'licenses' => [
                'current' => $licensesNow,
                'prev'    => $licensesPrev,
                'spark'   => array_column($licensesSpark, 'value'),
            ],
            'activations' => [
                'current' => $activationsNow,
                'prev'    => $activationsPrev,
                'spark'   => array_column($activationsSpark, 'value'),
            ],
            'renewals' => [
                'current' => (int) array_sum(array_column($revenueSplit['renewal'], 'value')),
                'spark'   => array_column($revenueSplit['renewal'], 'value'),
            ],
        ];

        $matrix = Cache::remember(
            'matrix',
            'admin:' . md5(json_encode($filters)),
            static function () use ($db, $licenseByProduct): array {
                $metrics = [];
                foreach ($licenseByProduct as $code => $count) {
                    $metrics[$code] = $count . ' license' . ((int) $count === 1 ? '' : 's');
                }

                $built = ProductMatrix::build($db, $metrics);

                // Admin cells drill through to the filtered license list (or the
                // custom-order queue for a combination that is not offered), not
                // the public product page.
                foreach ($built['rows'] as &$row) {
                    foreach (VariantResolver::all() as $variant) {
                        if (!isset($row['cells'][$variant])) {
                            continue;
                        }
                        $offered = (bool) $row['cells'][$variant]['offered'];
                        $row['cells'][$variant]['href'] = $offered
                            ? '/admin/licenses?product=' . rawurlencode((string) $row['code']) . '&variant=' . $variant
                            : '/admin/custom-requests';
                    }
                }
                unset($row);

                return $built;
            }
        );

        $this->view('admin.dashboard', [
            'admin'            => Auth::user(),
            'stats'            => $stats,
            'range'            => $range,
            'filters'          => $filters,
            'revenueTrend'     => $revenueTrend,
            'licenseByStatus'  => $licenseByStatus,
            'licenseByProduct' => $licenseByProduct,
            'licenseStatuses'  => LicenseService::STATUSES,
            'productCatalog'   => Catalog::all(),
            'reconciliation'   => $revenue->reconcile($range, $filters),
            'recentPayments'   => $recentPayments,
            'recentLicenses'   => $recentLicenses,
            'expiringLicenses' => $expiringLicenses,
            'unreadMessages'   => $unreadMessages,
            'kpis'             => $kpis,
            'revenueByVariant' => $revenueByVariant,
            'mrrByProduct'     => $mrrByProduct,
            'byGateway'        => $gwMix,
            'funnel'           => $funnel,
            'countries'        => $countries,
            'renewalRisk'      => $renewalRisk,
            'revenueSplit'     => $revenueSplit,
            'licensesSpark'    => $licensesSpark,
            'customersSpark'   => $customersSpark,
            'activationsSpark' => $activationsSpark,
            'deactivationsSpark' => $deactivationsSpark,
            'topCustomers'     => $topCustomers,
            'activityFeed'     => $feed->recent(8),
            'matrix'           => $matrix,
            'renewalTotal'     => $renewalTotal,
            'trending'         => $trending,
            // Blank by default: the gauge card degrades to plain MRR/ARR.
            'arrTarget'        => Settings::get('analytics.arr_target', ''),
        ]);
    }

    /**
     * Count rows in a date window, honouring the product/variant filters where
     * the table supports them. Used for KPI period-over-period deltas.
     *
     * @param array{product?: ?string, variant?: ?string} $filters
     */
    private function countInRange(string $table, string $dateColumn, DateRange $range, array $filters = []): int
    {
        $db = Database::getInstance();

        $params = [];
        $where = [$range->where($dateColumn, $params)];

        if (in_array($table, ['customers', 'licenses'], true)) {
            $where[] = 'deleted_at IS NULL';
        }

        if ($table === 'licenses') {
            if (!empty($filters['product'])) {
                $where[] = 'product = ?';
                $params[] = $filters['product'];
            }
            if (!empty($filters['variant'])) {
                $where[] = 'variant = ?';
                $params[] = $filters['variant'];
            }
        }

        $row = $db->fetch(
            'SELECT COUNT(*) AS c FROM ' . $table . ' WHERE ' . implode(' AND ', $where),
            $params
        );

        return (int) ($row['c'] ?? 0);
    }

    /**
     * Normalise the product/variant filters from the query string.
     *
     * @param array<string, mixed> $query
     * @return array{product: ?string, variant: ?string}
     */
    public static function filtersFromRequest(array $query): array
    {
        return [
            'product' => Catalog::has((string) ($query['product'] ?? ''))
                ? strtolower(trim((string) $query['product']))
                : null,
            'variant' => VariantResolver::isValid($query['variant'] ?? null)
                ? VariantResolver::normalize($query['variant'])
                : null,
        ];
    }

    /**
     * License count joined to plans of a given billing period.
     *
     * @param array{product?: ?string, variant?: ?string} $filters
     */
    private function countLicensesByPeriod(string $period, array $filters): int
    {
        $db = Database::getInstance();

        $periods = $period === 'yearly' ? ['yearly', 'annual'] : ['monthly'];

        $placeholders = implode(', ', array_fill(0, count($periods), '?'));

        // Bind values only, in placeholder order, and add a predicate for each
        // one. A literal such as `deleted_at IS NULL` belongs in the SQL; adding
        // it to $params instead shifts every binding ("Invalid parameter number").
        $params = $periods;
        $extra  = '';
        if (! empty($filters['product'])) {
            $extra .= ' AND l.product = ?';
            $params[] = $filters['product'];
        }
        if (! empty($filters['variant'])) {
            $extra .= ' AND l.variant = ?';
            $params[] = $filters['variant'];
        }

        $row = $db->fetch(
            'SELECT COUNT(*) AS c FROM licenses l
               JOIN plans pl ON pl.id = l.plan_id
              WHERE l.deleted_at IS NULL
                AND pl.period IN (' . $placeholders . ')' . $extra,
            $params
        );

        return (int) ($row['c'] ?? 0);
    }
}