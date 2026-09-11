<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class ExpenseController extends Controller
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
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND (e.title LIKE ? OR e.description LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }
        if ($categoryId > 0) {
            $where .= ' AND e.expense_category_id = ?';
            $params[] = $categoryId;
        }
        if ($dateFrom !== '') {
            $where .= ' AND e.date >= ?';
            $params[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $where .= ' AND e.date <= ?';
            $params[] = $dateTo;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM expenses e WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT e.*, ec.name as category_name, u.name as creator_name
             FROM expenses e
             LEFT JOIN expense_categories ec ON e.expense_category_id = ec.id
             LEFT JOIN users u ON e.created_by = u.id
             WHERE {$where}
             ORDER BY e.date DESC, e.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $categories = $this->db->fetchAll("SELECT id, name FROM expense_categories ORDER BY name ASC");
        $totalAmount = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM expenses e WHERE {$where}", $params
        )['total'] ?? 0);

        $this->view('dashboard.expenses.index', [
            'rows'        => $rows,
            'expenses' => $rows,
            'total'       => $total,
            'page'        => $page,
            'perPage'     => $perPage,
            'lastPage'    => max(1, (int) ceil($total / $perPage)),
            'search'      => $search,
            'categoryId'  => $categoryId,
            'dateFrom'    => $dateFrom,
            'dateTo'      => $dateTo,
            'categories'  => $categories,
            'totalAmount' => $totalAmount,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'title'       => 'required|max:255',
            'amount'      => 'required|numeric',
            'date'        => 'required',
            'category_id' => 'numeric',
            'description' => 'max:1000',
            'payment_method' => 'max:50',
        ]);

        $this->db->insert('expenses', [
            'title'          => $data['title'],
            'amount'         => $data['amount'],
            'date'           => $data['date'],
            'category_id'    => $data['category_id'] ?? null,
            'description'    => $data['description'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'created_by'     => Auth::id(),
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Expense recorded.');
        $this->redirect('/dashboard/expenses');
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $expense = $this->db->fetch("SELECT * FROM expenses WHERE id = ? LIMIT 1", [$id]);
        if (!$expense) {
            Session::getInstance()->flash('error', 'Expense not found.');
            $this->redirect('/dashboard/expenses');
            return;
        }

        $data = $this->validate([
            'title'       => 'required|max:255',
            'amount'      => 'required|numeric',
            'date'        => 'required',
            'category_id' => 'numeric',
            'description' => 'max:1000',
            'payment_method' => 'max:50',
        ]);

        $this->db->update('expenses', [
            'title'          => $data['title'],
            'amount'         => $data['amount'],
            'date'           => $data['date'],
            'category_id'    => $data['category_id'] ?? null,
            'description'    => $data['description'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'updated_at'     => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Expense updated.');
        $this->redirect('/dashboard/expenses');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('expenses', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Expense deleted.');
        $this->redirect('/dashboard/expenses');
    }
}
