<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\LicenseReminderService;

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
        (new LicenseReminderService())->notifyExpiring();

        $stats = $db->fetch(
            "SELECT
                (SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL) AS customers,
                (SELECT COUNT(*) FROM licenses WHERE deleted_at IS NULL) AS licenses,
                (SELECT COUNT(*) FROM licenses WHERE deleted_at IS NULL AND status = 'active' AND (expires_at IS NULL OR expires_at > NOW())) AS active_licenses,
                (SELECT COUNT(*) FROM payments WHERE status = 'paid') AS paid_payments,
                (SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'paid') AS revenue,
                (SELECT COUNT(*) FROM licenses WHERE deleted_at IS NULL AND expires_at IS NOT NULL AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)) AS expiring_soon,
                (SELECT COUNT(*) FROM contact_messages WHERE read_at IS NULL) AS unread_messages,
                (SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'paid' AND paid_at >= DATE_FORMAT(NOW(), '%Y-%m-01')) AS revenue_this_month,
                (SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'paid' AND paid_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') AND paid_at < DATE_FORMAT(NOW(), '%Y-%m-01')) AS revenue_prev_month"
        );

        $monthlyRows = $db->fetchAll(
            "SELECT DATE_FORMAT(paid_at, '%Y-%m') AS ym, COALESCE(SUM(amount),0) AS total
             FROM payments
             WHERE status = 'paid' AND paid_at >= DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL 11 MONTH)
             GROUP BY ym ORDER BY ym ASC"
        );
        $monthly = [];
        foreach ($monthlyRows as $row) {
            $monthly[$row['ym']] = (float) $row['total'];
        }
        $revenueTrend = [];
        $cursor = new \DateTimeImmutable('first day of this month');
        for ($i = 11; $i >= 0; $i--) {
            $ym = $cursor->modify("-{$i} months")->format('Y-m');
            $revenueTrend[] = ['label' => $cursor->modify("-{$i} months")->format('M'), 'value' => $monthly[$ym] ?? 0.0];
        }

        $licenseByStatus = $db->fetchAll(
            "SELECT status, COUNT(*) AS c FROM licenses WHERE deleted_at IS NULL GROUP BY status ORDER BY c DESC"
        );

        $licenseByProduct = $db->fetchAll(
            "SELECT product, COUNT(*) AS c FROM licenses WHERE deleted_at IS NULL GROUP BY product ORDER BY c DESC"
        );

        $recentPayments = $db->fetchAll(
            "SELECT p.*, c.name AS customer_name FROM payments p
             LEFT JOIN customers c ON p.customer_id = c.id
             ORDER BY p.id DESC LIMIT 8"
        );

        $recentLicenses = $db->fetchAll(
            "SELECT l.*, c.name AS customer_name FROM licenses l
             LEFT JOIN customers c ON l.customer_id = c.id
             WHERE l.deleted_at IS NULL
             ORDER BY l.id DESC LIMIT 8"
        );

        $expiringLicenses = $db->fetchAll(
            "SELECT l.id, l.license_key, l.expires_at, l.status, c.name AS customer_name, c.email AS customer_email
             FROM licenses l
             LEFT JOIN customers c ON l.customer_id = c.id
             WHERE l.deleted_at IS NULL
               AND l.status = 'active'
               AND l.expires_at IS NOT NULL
               AND l.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
             ORDER BY l.expires_at ASC
             LIMIT 10"
        );

        $unreadMessages = $db->fetchAll(
            "SELECT * FROM contact_messages WHERE read_at IS NULL ORDER BY id DESC LIMIT 8"
        );

        $this->view('admin.dashboard', [
            'admin'             => Auth::user(),
            'stats'             => $stats,
            'revenueTrend'      => $revenueTrend,
            'licenseByStatus'   => $licenseByStatus,
            'licenseByProduct'  => $licenseByProduct,
            'recentPayments'    => $recentPayments,
            'recentLicenses'    => $recentLicenses,
            'expiringLicenses'  => $expiringLicenses,
            'unreadMessages'    => $unreadMessages,
        ]);
    }
}