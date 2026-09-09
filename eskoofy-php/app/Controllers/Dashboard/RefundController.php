<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class RefundController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $status = $_GET['status'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($status !== '') {
            $where .= ' AND r.status = ?';
            $params[] = $status;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM refunds r WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT r.*, u.name as student_name, u2.name as processed_by_name
             FROM refunds r
             LEFT JOIN students s ON r.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN users u2 ON r.processed_by = u2.id
             WHERE {$where}
             ORDER BY r.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('dashboard.refunds.index', [
            'rows'     => $rows,
            'refunds' => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'status'   => $status,
        ]);
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $refund = $this->db->fetch(
            "SELECT r.*, u.name as student_name, s.admission_number, u2.name as processed_by_name
             FROM refunds r
             LEFT JOIN students s ON r.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN users u2 ON r.processed_by = u2.id
             WHERE r.id = ? LIMIT 1",
            [$id]
        );

        if (!$refund) {
            Session::getInstance()->flash('error', 'Refund not found.');
            $this->redirect('/dashboard/refunds');
            return;
        }

        $this->view('dashboard.refunds.show', ['refund' => $refund]);
    }

    public function process(int $id): void
    {
        Auth::requireAuth();
        $refund = $this->db->fetch("SELECT * FROM refunds WHERE id = ? LIMIT 1", [$id]);
        if (!$refund) {
            Session::getInstance()->flash('error', 'Refund not found.');
            $this->redirect('/dashboard/refunds');
            return;
        }

        $this->db->update('refunds', [
            'status'       => 'processed',
            'processed_by' => Auth::id(),
            'processed_at' => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Refund processed.');
        $this->redirect("/dashboard/refunds/{$id}");
    }

    public function cancel(int $id): void
    {
        Auth::requireAuth();
        $refund = $this->db->fetch("SELECT * FROM refunds WHERE id = ? LIMIT 1", [$id]);
        if (!$refund) {
            Session::getInstance()->flash('error', 'Refund not found.');
            $this->redirect('/dashboard/refunds');
            return;
        }

        $this->db->update('refunds', [
            'status'     => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Refund cancelled.');
        $this->redirect("/dashboard/refunds/{$id}");
    }
}
