<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class ExpenseCategoryController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $rows = $this->db->fetchAll(
            "SELECT ec.*,
                (SELECT COUNT(*) FROM expenses e WHERE e.expense_category_id = ec.id) as expense_count,
                (SELECT COALESCE(SUM(e.amount), 0) FROM expenses e WHERE e.expense_category_id = ec.id) as total_amount
             FROM expense_categories ec
             ORDER BY ec.name ASC"
        );

        $this->view('dashboard.expense-categories.index', [
            'rows' => $this->paginateRows($rows, count($rows), max(1, count($rows)), 1, \App\Models\ExpenseCategory::class),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'        => 'required|max:255',
            'description' => 'max:500',
        ]);

        $this->db->insert('expense_categories', [
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Category created.');
        $this->redirect('/dashboard/expense-categories');
    }
}
