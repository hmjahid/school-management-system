<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class FeePaymentController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $method = $_GET['payment_method'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND (fp.invoice_number LIKE ? OR u.name LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }
        if ($status !== '') {
            $where .= ' AND fp.status = ?';
            $params[] = $status;
        }
        if ($method !== '') {
            $where .= ' AND fp.payment_method = ?';
            $params[] = $method;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM fee_payments fp LEFT JOIN students s ON fp.student_id = s.id LEFT JOIN users u ON s.user_id = u.id WHERE {$where}",
            $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT fp.*, u.name as student_name, s.admission_number, f.name as fee_name, c.name as class_name
             FROM fee_payments fp
             LEFT JOIN students s ON fp.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN fees f ON fp.fee_id = f.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             WHERE {$where}
             ORDER BY fp.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('dashboard.fee-payments.index', [
            'rows'     => $rows,
            'payments' => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'search'   => $search,
            'status'   => $status,
            'method'   => $method,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'student_id'      => 'required|numeric',
            'fee_id'          => 'required|numeric',
            'amount_paid'     => 'required|numeric',
            'payment_method'  => 'required|max:50',
            'payment_date'    => 'required',
            'reference_number'=> 'max:100',
            'notes'           => 'max:1000',
        ]);

        $fee = $this->db->fetch("SELECT * FROM fees WHERE id = ? LIMIT 1", [$data['fee_id']]);
        if (!$fee) {
            Session::getInstance()->flash('error', 'Fee type not found.');
            $this->back();
            return;
        }

        $existing = $this->db->fetch(
            "SELECT id, amount_paid, balance FROM fee_payments WHERE student_id = ? AND fee_id = ? LIMIT 1",
            [$data['student_id'], $data['fee_id']]
        );

        $amountPaid = (float) $data['amount_paid'];
        $totalFee = (float) $fee['amount'];

        if ($existing) {
            $newPaid = (float) $existing['amount_paid'] + $amountPaid;
            $newBalance = $totalFee - $newPaid;
            $newStatus = $newBalance <= 0 ? 'paid' : ($newPaid > 0 ? 'partial' : 'pending');

            $this->db->update('fee_payments', [
                'amount_paid'     => $newPaid,
                'balance'         => max(0, $newBalance),
                'status'          => $newStatus,
                'payment_method'  => $data['payment_method'],
                'payment_date'    => $data['payment_date'],
                'reference_number'=> $data['reference_number'] ?? null,
                'approved_by'     => Auth::id(),
                'approved_at'     => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ], 'id = ?', [$existing['id']]);

            $invoiceNumber = 'FP-' . str_pad((string) $existing['id'], 8, '0', STR_PAD_LEFT);
        } else {
            $balance = max(0, $totalFee - $amountPaid);
            $payStatus = $balance <= 0 ? 'paid' : ($amountPaid > 0 ? 'partial' : 'pending');

            $paymentId = $this->db->insert('fee_payments', [
                'student_id'      => $data['student_id'],
                'fee_id'          => $data['fee_id'],
                'amount_paid'     => $amountPaid,
                'balance'         => $balance,
                'status'          => $payStatus,
                'payment_method'  => $data['payment_method'],
                'payment_date'    => $data['payment_date'],
                'reference_number'=> $data['reference_number'] ?? null,
                'notes'           => $data['notes'] ?? null,
                'approved_by'     => Auth::id(),
                'approved_at'     => date('Y-m-d H:i:s'),
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);

            $invoiceNumber = 'FP-' . str_pad((string) $paymentId, 8, '0', STR_PAD_LEFT);
            $this->db->update('fee_payments', ['invoice_number' => $invoiceNumber], 'id = ?', [$paymentId]);
        }

        Session::getInstance()->flash('success', 'Fee payment recorded successfully.');
        $this->redirect('/dashboard/fee-payments');
    }

    public function receipt(int $id): void
    {
        Auth::requireAuth();
        $payment = $this->db->fetch(
            "SELECT fp.*, u.name as student_name, s.admission_number, f.name as fee_name, f.fee_type, f.amount as fee_amount,
                    c.name as class_name, au.name as approved_by_name
             FROM fee_payments fp
             LEFT JOIN students s ON fp.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN fees f ON fp.fee_id = f.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN users au ON fp.approved_by = au.id
             WHERE fp.id = ? LIMIT 1",
            [$id]
        );

        if (!$payment) {
            Session::getInstance()->flash('error', 'Payment not found.');
            $this->redirect('/dashboard/fee-payments');
            return;
        }

        $settings = $this->db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");

        $this->view('dashboard.fee-payments.receipt', [
            'payment'  => $payment,
            'settings' => $settings,
        ]);
    }
}
