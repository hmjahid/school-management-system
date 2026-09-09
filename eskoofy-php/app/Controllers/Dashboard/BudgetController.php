<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class BudgetController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $year = $_GET['year'] ?? date('Y');
        $category = $_GET['category'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = "YEAR(b.budget_date) = ?";
        $params = [(int) $year];
        if ($category !== '') {
            $where .= " AND b.category = ?";
            $params[] = $category;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM budgets b WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT b.*, u.name as creator_name
             FROM budgets b
             LEFT JOIN users u ON b.created_by = u.id
             WHERE {$where}
             ORDER BY b.budget_date DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $totalBudget = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM budgets WHERE YEAR(budget_date) = ? AND type = 'income'",
            [$year]
        )['total'] ?? 0);

        $totalExpense = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM budgets WHERE YEAR(budget_date) = ? AND type = 'expense'",
            [$year]
        )['total'] ?? 0);

        $this->view('dashboard.budgets.index', [
            'rows'         => $rows,
            'budgets' => $rows,
            'total'        => $total,
            'page'         => $page,
            'perPage'      => $perPage,
            'lastPage'     => max(1, (int) ceil($total / $perPage)),
            'year'         => $year,
            'category'     => $category,
            'totalBudget'  => $totalBudget,
            'totalExpense' => $totalExpense,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.budgets.create', []);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'title'       => 'required|max:255',
            'type'        => 'required|in:income,expense',
            'amount'      => 'required|numeric',
            'category'    => 'required|max:100',
            'budget_date' => 'required',
            'description' => 'max:1000',
        ]);

        $this->db->insert('budgets', [
            'title'        => $data['title'],
            'type'         => $data['type'],
            'amount'       => $data['amount'],
            'category'     => $data['category'],
            'budget_date'  => $data['budget_date'],
            'description'  => $data['description'] ?? null,
            'created_by'   => Auth::id(),
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Budget entry created.');
        $this->redirect('/dashboard/budgets');
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $budget = $this->db->fetch("SELECT * FROM budgets WHERE id = ? LIMIT 1", [$id]);
        if (!$budget) {
            Session::getInstance()->flash('error', 'Budget entry not found.');
            $this->redirect('/dashboard/budgets');
            return;
        }

        $data = $this->validate([
            'title'       => 'required|max:255',
            'type'        => 'required|in:income,expense',
            'amount'      => 'required|numeric',
            'category'    => 'required|max:100',
            'budget_date' => 'required',
            'description' => 'max:1000',
        ]);

        $this->db->update('budgets', [
            'title'        => $data['title'],
            'type'         => $data['type'],
            'amount'       => $data['amount'],
            'category'     => $data['category'],
            'budget_date'  => $data['budget_date'],
            'description'  => $data['description'] ?? null,
            'updated_at'   => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Budget entry updated.');
        $this->redirect('/dashboard/budgets');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('budgets', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Budget entry deleted.');
        $this->redirect('/dashboard/budgets');
    }
}
