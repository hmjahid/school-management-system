<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

/**
 * Admin subscription list — MRR/ARR snapshot + per-subscription status.
 */
class SubscriptionController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT s.*, c.name AS customer_name, c.email AS customer_email,
                    l.license_key, p.name AS plan_name, p.product, p.price, p.currency, p.period
             FROM subscriptions s
             JOIN customers c ON c.id = s.customer_id
             JOIN licenses l ON l.id = s.license_id
             JOIN plans p ON p.id = s.plan_id
             ORDER BY s.updated_at DESC"
        );

        // MRR/ARR estimate: sum of active subscriptions' plan price (USD) per period.
        $mrr = 0.0;
        $arr = 0.0;
        $active = 0;
        foreach ($rows as $r) {
            if (($r['status'] ?? '') === 'active') {
                ++$active;
                $price = (float) $r['price'];
                $mrr += ($r['period'] ?? '') === 'yearly' ? $price / 12 : $price;
                $arr += ($r['period'] ?? '') === 'yearly' ? $price : $price * 12;
            }
        }

        $this->view('admin.subscriptions', [
            'admin'  => Auth::user(),
            'rows'   => $rows,
            'mrr'    => $mrr,
            'arr'    => $arr,
            'active' => $active,
        ]);
    }
}