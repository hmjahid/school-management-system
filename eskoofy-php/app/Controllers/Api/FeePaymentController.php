<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;

/**
 * Fee payment API — statuses, methods, show, update, approve, cancel.
 * Parity with eskoofy-app Api\FeePaymentController.
 */
class FeePaymentController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getStatuses(): void
    {
        $this->success(['pending', 'paid', 'partial', 'cancelled', 'refunded'], 'Payment statuses retrieved');
    }

    public function getPaymentMethods(): void
    {
        $this->success(['cash', 'bank', 'cheque', 'online', 'mobile'], 'Payment methods retrieved');
    }

    public function show(int $id): void
    {
        $row = $this->db->fetch(
            "SELECT fp.*, u.name AS student_name, f.name AS fee_name
             FROM fee_payments fp
             LEFT JOIN students s ON s.id = fp.student_id
             LEFT JOIN users u ON u.id = s.user_id
             LEFT JOIN fees f ON f.id = fp.fee_id
             WHERE fp.id = ? LIMIT 1",
            [$id]
        );
        if (! $row) {
            $this->error('Payment not found', 404);
        }
        $this->success($row, 'Payment retrieved');
    }

    public function update(int $id): void
    {
        $payment = $this->db->fetch("SELECT * FROM fee_payments WHERE id = ? LIMIT 1", [$id]);
        if (! $payment) {
            $this->error('Payment not found', 404);
        }

        $updates = [];
        foreach (['amount', 'paid_amount', 'fine_amount', 'discount_amount', 'payment_method', 'transaction_id', 'notes'] as $field) {
            if (isset($_POST[$field])) {
                $updates[$field] = $_POST[$field];
            }
        }
        if (isset($updates['paid_amount'])) {
            $paid = (float) $updates['paid_amount'];
            $updates['balance'] = max(0, (float) ($payment['amount'] ?? 0) - $paid);
        }
        $updates['updated_at'] = date('Y-m-d H:i:s');
        $this->db->update('fee_payments', $updates, 'id = ?', [$id]);

        $this->success(['id' => $id], 'Payment updated');
    }

    public function approve(int $id): void
    {
        $payment = $this->db->fetch("SELECT * FROM fee_payments WHERE id = ? LIMIT 1", [$id]);
        if (! $payment) {
            $this->error('Payment not found', 404);
        }
        $this->db->update('fee_payments', [
            'status'      => 'paid',
            'approved_by' => \App\Core\Auth::id() ?: 0,
            'approved_at' => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
        $this->success(['id' => $id], 'Payment approved');
    }

    public function cancel(int $id): void
    {
        $payment = $this->db->fetch("SELECT * FROM fee_payments WHERE id = ? LIMIT 1", [$id]);
        if (! $payment) {
            $this->error('Payment not found', 404);
        }
        $this->db->update('fee_payments', [
            'status'     => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
        $this->success(['id' => $id], 'Payment cancelled');
    }

    public function store(int $feeId): void
    {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $amount    = (float) ($_POST['amount'] ?? 0);
        if ($studentId < 1 || $amount <= 0) {
            $this->error('Student and a positive amount are required.', 422);
        }

        $invoice = 'INV-' . date('YmdHis') . '-' . random_int(100, 999);
        $newId = $this->db->insert('fee_payments', [
            'invoice_number' => $invoice,
            'student_id'     => $studentId,
            'fee_id'         => $feeId,
            'amount'         => $amount,
            'paid_amount'    => $amount,
            'balance'        => 0,
            'payment_date'   => date('Y-m-d'),
            'payment_method' => trim((string) ($_POST['payment_method'] ?? 'cash')),
            'transaction_id' => trim((string) ($_POST['transaction_id'] ?? '')),
            'status'         => 'paid',
            'created_by'     => \App\Core\Auth::id() ?: 0,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        $this->json(['success' => true, 'message' => 'Payment recorded', 'data' => ['id' => $newId]], 201);
    }
}