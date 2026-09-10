<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $db = Database::getInstance();
        $stats = $db->fetch(
            "SELECT
                (SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL) AS customers,
                (SELECT COUNT(*) FROM licenses WHERE deleted_at IS NULL) AS licenses,
                (SELECT COUNT(*) FROM licenses WHERE deleted_at IS NULL AND status = 'active' AND (expires_at IS NULL OR expires_at > NOW())) AS active_licenses,
                (SELECT COUNT(*) FROM payments WHERE status = 'paid') AS paid_payments,
                (SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'paid') AS revenue,
                (SELECT COUNT(*) FROM licenses WHERE deleted_at IS NULL AND expires_at IS NOT NULL AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)) AS expiring_soon,
                (SELECT COUNT(*) FROM contact_messages WHERE read_at IS NULL) AS unread_messages"
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

        $this->view('admin.dashboard', [
            'admin'          => Auth::user(),
            'stats'          => $stats,
            'recentPayments' => $recentPayments,
            'recentLicenses' => $recentLicenses,
        ]);
    }
}
