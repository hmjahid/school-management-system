<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\ActivityLog;

/**
 * Admin subscription list — MRR/ARR snapshot + per-subscription status, plus
 * the pause / resume / cancel lifecycle actions.
 */
class SubscriptionController extends Controller
{
    private const STATUSES = ['active', 'paused', 'cancelled', 'expired'];

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

    /**
     * Change a subscription's lifecycle state. `cancelled` is terminal until an
     * admin resumes it; the license expiry itself is never touched here — that
     * is owned by LicenseManager so renewal stacking stays in one place.
     */
    public function setStatus(int $id, string $status): void
    {
        $status = strtolower(trim($status));
        if (!in_array($status, self::STATUSES, true)) {
            $this->withError('Invalid subscription status.');
            $this->redirect('/admin/subscriptions');
        }

        $db = Database::getInstance();
        $row = $db->fetch("SELECT * FROM subscriptions WHERE id = ?", [$id]);
        if (!$row) {
            $this->withError('Subscription not found.');
            $this->redirect('/admin/subscriptions');
        }

        $db->update('subscriptions', [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        ActivityLog::log('admin.subscription_status', 'admin', (int) Auth::id(), [
            'subscription_id' => $id,
            'license_id'      => (int) ($row['license_id'] ?? 0),
            'from'            => (string) ($row['status'] ?? ''),
            'to'              => $status,
        ]);

        $this->withSuccess('Subscription marked as ' . $status . '.');
        $this->redirect('/admin/subscriptions');
    }
}