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
                (SELECT COUNT(*) FROM expenses e WHERE e.expense_category_id = ec.id) as expenses_count,
                (SELECT COALESCE(SUM(e.amount), 0) FROM expenses e WHERE e.expense_category_id = ec.id) as total_amount
             FROM expense_categories ec
             WHERE ec.deleted_at IS NULL
             ORDER BY ec.name ASC"
        );

        $this->view('dashboard.expense-categories.index', [
            'rows' => $this->paginateRows($rows, count($rows), max(1, count($rows)), 1, \App\Models\ExpenseCategory::class),
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.expense-categories.create');
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'        => 'required|max:64',
            'description' => 'max:2000',
            'color'       => 'max:16',
        ]);

        $this->db->insert('expense_categories', [
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'color'       => $data['color'] ?? null,
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Category created.');
        $this->redirect('/dashboard/expense-categories');
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $category = $this->db->fetch("SELECT * FROM expense_categories WHERE id = ? LIMIT 1", [$id]);
        if (!$category) {
            Session::getInstance()->flash('error', 'Category not found.');
            $this->redirect('/dashboard/expense-categories');
            return;
        }

        $this->view('dashboard.expense-categories.edit', [
            'category' => \App\Models\ExpenseCategory::newFromRow($category),
        ]);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $category = $this->db->fetch("SELECT * FROM expense_categories WHERE id = ? LIMIT 1", [$id]);
        if (!$category) {
            Session::getInstance()->flash('error', 'Category not found.');
            $this->redirect('/dashboard/expense-categories');
            return;
        }

        $this->view('dashboard.expense-categories.edit', [
            'category' => \App\Models\ExpenseCategory::newFromRow($category),
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $category = $this->db->fetch("SELECT * FROM expense_categories WHERE id = ? LIMIT 1", [$id]);
        if (!$category) {
            Session::getInstance()->flash('error', 'Category not found.');
            $this->redirect('/dashboard/expense-categories');
            return;
        }

        $data = $this->validate([
            'name'        => 'required|max:64',
            'description' => 'max:2000',
            'color'       => 'max:16',
        ]);

        $this->db->update('expense_categories', [
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'color'       => $data['color'] ?? null,
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Category updated.');
        $this->redirect('/dashboard/expense-categories');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->update('expense_categories', [
            'deleted_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Category deleted.');
        $this->redirect('/dashboard/expense-categories');
    }
}
