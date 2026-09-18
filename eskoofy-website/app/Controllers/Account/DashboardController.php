<?php
declare(strict_types=1);

namespace App\Controllers\Account;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\ActivityLog;

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

        $spendRows = $db->fetchAll(
            "SELECT DATE_FORMAT(paid_at, '%Y-%m') AS ym, COALESCE(SUM(amount),0) AS total
             FROM payments
             WHERE customer_id = ? AND status = 'paid' AND paid_at >= DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL 5 MONTH)
             GROUP BY ym ORDER BY ym ASC",
            [$this->customerId]
        );
        $spendByMonth = [];
        foreach ($spendRows as $row) {
            $spendByMonth[$row['ym']] = (float) $row['total'];
        }
        $spendTrend = [];
        $cursor = new \DateTimeImmutable('first day of this month');
        for ($i = 5; $i >= 0; $i--) {
            $ym = $cursor->modify("-{$i} months")->format('Y-m');
            $spendTrend[] = ['label' => $cursor->modify("-{$i} months")->format('M'), 'value' => $spendByMonth[$ym] ?? 0.0];
        }

        $customer = Auth::user();

        $renewals = $db->fetchAll(
            "SELECT l.id, l.license_key, l.expires_at, p.name AS plan_name, p.period AS plan_period
             FROM licenses l
             LEFT JOIN plans p ON l.plan_id = p.id
             WHERE l.customer_id = ?
               AND l.deleted_at IS NULL
               AND l.status = 'active'
               AND l.expires_at IS NOT NULL
               AND l.expires_at > NOW()
               AND l.expires_at < DATE_ADD(NOW(), INTERVAL 45 DAY)
             ORDER BY l.expires_at ASC",
            [$this->customerId]
        );

        $this->view('account.dashboard', [
            'customer'   => $customer,
            'licenses'   => $licenses,
            'payments'   => $payments,
            'stats'      => $stats,
            'spendTrend' => $spendTrend,
            'renewals'   => $renewals,
            'notifications' => $this->notifications($customer['id']),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function notifications(int $customerId): array
    {
        try {
            return Database::getInstance()->fetchAll(
                "SELECT pn.id, pn.title, pn.message, pn.link, pn.created_at,
                        (r.read_at IS NOT NULL) AS read_at
                 FROM push_notifications pn
                 LEFT JOIN push_notification_reads r ON r.notification_id = pn.id AND r.customer_id = ?
                 ORDER BY pn.id DESC LIMIT 20",
                [$customerId]
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public function markNotificationRead(int $id): void
    {
        Auth::requireAuth();
        $customer = Auth::user();
        $db = Database::getInstance();

        $exists = $db->fetch("SELECT id FROM push_notifications WHERE id = ?", [$id]);
        if ($exists) {
            $read = $db->fetch(
                "SELECT id FROM push_notification_reads WHERE notification_id = ? AND customer_id = ?",
                [$id, (int) $customer['id']]
            );
            if (!$read) {
                $db->insert('push_notification_reads', [
                    'notification_id' => $id,
                    'customer_id'     => (int) $customer['id'],
                    'read_at'         => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->redirect('/account');
    }

    public function regenerateApiToken(): void
    {
        $db = Database::getInstance();
        $token = bin2hex(random_bytes(32));

        $db->update('customers', [
            'api_token'  => $token,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$this->customerId]);

        // Refresh the in-session customer so the newly generated token shows
        // just once in the UI, exactly as before.
        $user = $db->fetch("SELECT * FROM customers WHERE id = ?", [$this->customerId]);
        if ($user) {
            \App\Core\Auth::login($user);
        }

        ActivityLog::log('customer.regenerated_api_token', 'customer', $this->customerId);

        $this->withSuccess('Your API key has been regenerated. Keep it safe — it replaces the previous key at once.');
        $this->redirect('/account');
    }

    public function paymentsCsv(): void
    {
        $db = Database::getInstance();
        $payments = $db->fetchAll(
            "SELECT * FROM payments WHERE customer_id = ? ORDER BY id DESC",
            [$this->customerId]
        );

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="payments-' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Reference', 'Amount', 'Currency', 'Method', 'Status', 'Paid at', 'Created at']);
        foreach ($payments as $p) {
            fputcsv($out, [
                $p['reference'],
                $p['amount'],
                $p['currency'],
                $p['gateway'],
                $p['status'],
                $p['paid_at'],
                $p['created_at'],
            ]);
        }
        fclose($out);
        exit;
    }
}