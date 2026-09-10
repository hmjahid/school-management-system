<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\ActivityLog;

class PaymentController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $rows = Database::getInstance()->fetchAll(
            "SELECT p.*, c.name AS customer_name, c.email AS customer_email
             FROM payments p
             LEFT JOIN customers c ON p.customer_id = c.id
             ORDER BY p.id DESC LIMIT 200"
        );

        $this->view('admin.payments', ['admin' => Auth::user(), 'payments' => $rows]);
    }

    public function updateStatus(int $id): void
    {
        $data = $this->validate(['status' => 'required|in:paid,failed,refunded']);
        $db = Database::getInstance();

        $payment = $db->fetch("SELECT * FROM payments WHERE id = ?", [$id]);
        if (!$payment) {
            $this->withError('Payment not found.');
            $this->redirect('/admin/payments');
        }

        $update = ['status' => $data['status'], 'updated_at' => date('Y-m-d H:i:s')];
        if ($data['status'] === 'paid' && !$payment['paid_at']) {
            $update['paid_at'] = date('Y-m-d H:i:s');
        }

        $db->update('payments', $update, 'id = ?', [$id]);

        ActivityLog::log('admin.updated_payment', 'admin', (int) Auth::id(), [
            'payment_id' => $id,
            'status'     => $data['status'],
        ]);

        $this->withSuccess('Payment marked as ' . $data['status'] . '.');
        $this->redirect('/admin/payments');
    }
}
