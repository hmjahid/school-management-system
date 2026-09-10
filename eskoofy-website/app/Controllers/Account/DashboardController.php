<?php
declare(strict_types=1);

namespace App\Controllers\Account;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{
    private int $customerId;

    public function __construct()
    {
        Auth::requireAuth();
        $this->customerId = (int) Auth::id();
    }

    public function index(): void
    {
        $db = Database::getInstance();
        $licenses = $db->fetchAll(
            "SELECT l.*, p.name AS plan_name, p.period AS plan_period, p.price AS plan_price
             FROM licenses l
             LEFT JOIN plans p ON l.plan_id = p.id
             WHERE l.customer_id = ? AND l.deleted_at IS NULL
             ORDER BY l.created_at DESC",
            [$this->customerId]
        );

        $payments = $db->fetchAll(
            "SELECT * FROM payments WHERE customer_id = ? ORDER BY id DESC LIMIT 8",
            [$this->customerId]
        );

        $stats = $db->fetch(
            "SELECT
                (SELECT COUNT(*) FROM licenses WHERE customer_id = ? AND deleted_at IS NULL) AS total_licenses,
                (SELECT COUNT(*) FROM licenses WHERE customer_id = ? AND deleted_at IS NULL AND status = 'active' AND (expires_at IS NULL OR expires_at > NOW())) AS active_licenses,
                (SELECT COUNT(*) FROM payments WHERE customer_id = ? AND status = 'paid') AS paid_payments,
                (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE customer_id = ? AND status = 'paid') AS total_spent,
                (SELECT COUNT(*) FROM license_activations la JOIN licenses l ON la.license_id = l.id WHERE l.customer_id = ? AND la.deactivated_at IS NULL) AS total_activations",
            [$this->customerId, $this->customerId, $this->customerId, $this->customerId, $this->customerId]
        );

        $this->view('account.dashboard', [
            'customer' => Auth::user(),
            'licenses' => $licenses,
            'payments' => $payments,
            'stats'    => $stats,
        ]);
    }
}
