<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class BudgetController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $year = $_GET['year'] ?? date('Y');
        $category = (int) ($_GET['category_id'] ?? 0);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = "YEAR(b.period_start) = ?";
        $params = [(int) $year];
        if ($category > 0) {
            $where .= " AND b.expense_category_id = ?";
            $params[] = $category;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM budgets b WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT b.*, ec.name as category_name
             FROM budgets b
             LEFT JOIN expense_categories ec ON b.expense_category_id = ec.id
             WHERE {$where}
             ORDER BY b.period_start DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $totalBudget = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM budgets b WHERE YEAR(b.period_start) = ?",
            [$year]
        )['total'] ?? 0);

        $categories = $this->db->fetchAll("SELECT id, name FROM expense_categories WHERE is_active = 1 ORDER BY name ASC");

        $this->view('dashboard.budgets.index', [
            'rows'         => $rows,
            'budgets'      => $rows,
            'total'        => $total,
            'page'         => $page,
            'perPage'      => $perPage,
            'lastPage'     => max(1, (int) ceil($total / $perPage)),
            'year'         => $year,
            'categoryId'   => $category,
            'categories'   => $categories,
            'totalBudget'  => $totalBudget,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $categories = $this->db->fetchAll("SELECT id, name FROM expense_categories WHERE is_active = 1 ORDER BY name ASC");
        $this->view('dashboard.budgets.create', ['categories' => $categories]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'expense_category_id' => 'required|numeric',
            'period_type'    => 'required|in:monthly,yearly,custom',
            'period_start'   => 'required',
            'period_end'     => 'required',
            'amount'         => 'required|numeric',
            'notes'          => 'max:2000',
        ]);

        $this->db->insert('budgets', [
            'expense_category_id' => $data['expense_category_id'],
            'period_type'    => $data['period_type'],
            'period_start'   => $data['period_start'],
            'period_end'     => $data['period_end'],
            'amount'         => $data['amount'],
            'notes'          => $data['notes'] ?? null,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Budget created.');
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
            'expense_category_id' => 'required|numeric',
            'period_type'    => 'required|in:monthly,yearly,custom',
            'period_start'   => 'required',
            'period_end'     => 'required',
            'amount'         => 'required|numeric',
            'notes'          => 'max:2000',
        ]);

        $this->db->update('budgets', [
            'expense_category_id' => $data['expense_category_id'],
            'period_type'    => $data['period_type'],
            'period_start'   => $data['period_start'],
            'period_end'     => $data['period_end'],
            'amount'         => $data['amount'],
            'notes'          => $data['notes'] ?? null,
            'updated_at'     => date('Y-m-d H:i:s'),
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