<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Gateways\GatewayFactory;

class PaymentController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $gateway = $_GET['gateway'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND (p.invoice_number LIKE ? OR p.reference_number LIKE ? OR p.transaction_id LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($status !== '') {
            $where .= ' AND p.payment_status = ?';
            $params[] = $status;
        }
        if ($gateway !== '') {
            $where .= ' AND p.payment_method = ?';
            $params[] = $gateway;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM payments p WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT p.*, u.name as creator_name
             FROM payments p
             LEFT JOIN users u ON p.created_by = u.id
             WHERE {$where}
             ORDER BY p.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $gateways = $this->db->fetchAll(
            "SELECT code, name FROM payment_gateways WHERE is_active = 1 ORDER BY name ASC"
        );

        $this->view('dashboard.payments.index', [
            'rows'      => $rows,
            'payments' => $rows,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'lastPage'  => max(1, (int) ceil($total / $perPage)),
            'search'    => $search,
            'status'    => $status,
            'gateway'   => $gateway,
            'gateways'  => $gateways,
        ]);
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $payment = $this->db->fetch(
            "SELECT p.*, u.name as creator_name, s.admission_number, st.name as student_name, c.name as class_name
             FROM payments p
             LEFT JOIN users u ON p.created_by = u.id
             LEFT JOIN students s ON p.paymentable_id = s.id
             LEFT JOIN users st ON s.user_id = st.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             WHERE p.id = ? LIMIT 1",
            [$id]
        );

        if (!$payment) {
            Session::getInstance()->flash('error', 'Payment not found.');
            $this->redirect('/dashboard/payments');
            return;
        }

        $this->view('dashboard.payments.show', ['payment' => $payment]);
    }

    public function refund(int $id): void
    {
        Auth::requireAuth();
        $payment = $this->db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [$id]);
        if (!$payment) {
            Session::getInstance()->flash('error', 'Payment not found.');
            $this->redirect('/dashboard/payments');
            return;
        }

        if ($payment['payment_status'] !== 'completed') {
            Session::getInstance()->flash('error', 'Only completed payments can be refunded.');
            $this->redirect("/dashboard/payments/{$id}");
            return;
        }

        $gatewayCode = $payment['payment_method'] ?? 'offline';
        $gateway = $this->db->fetch(
            "SELECT * FROM payment_gateways WHERE code = ? LIMIT 1",
            [$gatewayCode]
        );

        $refunded = false;

        if ($gateway && ($payment['transaction_id'] ?? '') !== '') {
            try {
                $adapter = GatewayFactory::makeFromPaymentRecord($payment, $gateway);
                $result = $adapter->refund(
                    $payment['transaction_id'],
                    (float) ($payment['paid_amount'] ?: $payment['amount']),
                    'Refunded by ' . (Auth::user()['name'] ?? 'admin')
                );
                $refunded = $result['success'] ?? false;
                if (!$refunded) {
                    Session::getInstance()->flash('error', 'Gateway refund failed: ' . ($result['message'] ?? 'Unknown error'));
                    $this->redirect("/dashboard/payments/{$id}");
                    return;
                }
            } catch (\Throwable $e) {
                error_log('Gateway refund error: ' . $e->getMessage());
                Session::getInstance()->flash('error', 'Gateway refund error: ' . $e->getMessage());
                $this->redirect("/dashboard/payments/{$id}");
                return;
            }
        }

        $this->db->update('payments', [
            'payment_status' => 'refunded',
            'notes'          => ($payment['notes'] ? $payment['notes'] . "\n" : '') . 'Refunded by ' . (Auth::user()['name'] ?? 'admin') . ($refunded ? ' (gateway)' : ' (manual)'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->db->insert('refunds', [
            'payment_id'  => $id,
            'student_id'  => $payment['paymentable_id'],
            'amount'      => $payment['paid_amount'] ?: $payment['amount'],
            'reason'      => 'Refund requested for invoice ' . ($payment['invoice_number'] ?? ''),
            'status'      => 'pending',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Payment refunded successfully.');
        $this->redirect("/dashboard/payments/{$id}");
    }

    public function markPaid(string $id): void
    {
        Auth::requireAuth();
        $payment = $this->db->fetch("SELECT * FROM fee_payments WHERE id = ? LIMIT 1", [$id]);
        if (!$payment) {
            Session::getInstance()->flash('error', 'Fee payment not found.');
            $this->redirect('/dashboard/fee-payments');
            return;
        }

        $this->db->update('fee_payments', [
            'status'      => 'paid',
            'paid_amount' => $payment['amount'],
            'balance'     => 0,
            'updated_at'  => date('Y-m-d H:i:s'),
            'approved_at' => date('Y-m-d H:i:s'),
            'approved_by' => Auth::id(),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Fee marked as paid.');
        $this->redirect('/dashboard/fee-payments');
    }
}
