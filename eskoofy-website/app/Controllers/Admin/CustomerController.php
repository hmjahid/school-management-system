<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\ActivityLog;

class CustomerController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $params = [];
        $where = 'deleted_at IS NULL';
        if ($q !== '') {
            $where .= ' AND (name LIKE ? OR email LIKE ?)';
            $params[] = "%{$q}%";
            $params[] = "%{$q}%";
        }

        $rows = Database::getInstance()->fetchAll(
            "SELECT c.*,
                (SELECT COUNT(*) FROM licenses WHERE customer_id = c.id AND deleted_at IS NULL) AS license_count,
                (SELECT COALESCE(SUM(amount),0) FROM payments WHERE customer_id = c.id AND status = 'paid') AS total_spent
             FROM customers c
             WHERE {$where}
             ORDER BY c.id DESC",
            $params
        );

        $this->view('admin.customers', ['admin' => Auth::user(), 'customers' => $rows, 'q' => $q]);
    }

    public function show(int $id): void
    {
        $db = Database::getInstance();
        $customer = $db->fetch("SELECT * FROM customers WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$customer) {
            $this->withError('Customer not found.');
            $this->redirect('/admin/customers');
        }

        $licenses = $db->fetchAll(
            "SELECT l.*, p.name AS plan_name FROM licenses l
             LEFT JOIN plans p ON l.plan_id = p.id
             WHERE l.customer_id = ? AND l.deleted_at IS NULL ORDER BY l.id DESC",
            [$id]
        );

        $payments = $db->fetchAll(
            "SELECT * FROM payments WHERE customer_id = ? ORDER BY id DESC LIMIT 20",
            [$id]
        );

        $this->view('admin.customer-detail', [
            'admin'    => Auth::user(),
            'customer' => $customer,
            'licenses' => $licenses,
            'payments' => $payments,
        ]);
    }

    public function update(int $id): void
    {
        $db = Database::getInstance();
        $customer = $db->fetch("SELECT * FROM customers WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$customer) {
            $this->withError('Customer not found.');
            $this->redirect('/admin/customers');
        }

        $data = $this->validate([
            'name'    => 'required|max:120',
            'status'  => 'in:active,suspended',
        ]);

        $db->update('customers', [
            'name'       => $data['name'],
            'company'    => $_POST['company'] ?? null,
            'country'    => $_POST['country'] ?? null,
            'status'     => $data['status'],
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        ActivityLog::log('admin.updated_customer', 'admin', (int) Auth::id(), ['customer_id' => $id]);

        $this->withSuccess('Customer updated.');
        $this->redirect('/admin/customers/' . $id);
    }
}
