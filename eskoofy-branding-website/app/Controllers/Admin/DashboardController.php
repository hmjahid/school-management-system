<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\Analytics\DateRange;
use App\Services\Analytics\LicenseService;
use App\Services\Analytics\RevenueService;
use App\Services\Cache;
use App\Services\Catalog;
use App\Services\LicenseReminderService;
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
        ]);
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