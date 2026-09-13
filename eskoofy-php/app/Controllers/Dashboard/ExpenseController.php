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
        $category = $_GET['category'] ?? '';
        $dateFrom = $_GET['from'] ?? '';
        $dateTo = $_GET['to'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($category !== '') {
            $where .= ' AND (ec.name = ? OR e.category = ?)';
            $params[] = $category;
            $params[] = $category;
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
            "SELECT COUNT(*) as cnt FROM expenses e LEFT JOIN expense_categories ec ON e.expense_category_id = ec.id WHERE {$where}", $params
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

        $categories = $this->db->fetchAll("SELECT id, name FROM expense_categories WHERE is_active = 1 ORDER BY name ASC");
        $totalAmount = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(e.amount), 0) as total FROM expenses e LEFT JOIN expense_categories ec ON e.expense_category_id = ec.id WHERE {$where}", $params
        )['total'] ?? 0);

        $now = new \App\Core\Support\Carbon();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $budgetRows = $this->db->fetchAll(
            "SELECT b.*, ec.name as category_name
             FROM budgets b
             LEFT JOIN expense_categories ec ON b.expense_category_id = ec.id
             WHERE b.period_type = 'monthly' AND b.period_start <= ? AND b.period_end >= ?",
            [$now->toDateString(), $now->toDateString()]
        );

        $budgetStatus = [];
        foreach ($budgetRows as $b) {
            $amount = (float) ($b['amount'] ?? 0);
            $spent = (float) ($this->db->fetch(
                "SELECT COALESCE(SUM(amount), 0) as total FROM expenses e WHERE e.expense_category_id = ? AND e.date BETWEEN ? AND ?",
                [$b['expense_category_id'] ?? 0, $monthStart->toDateString(), $monthEnd->toDateString()]
            )['total'] ?? 0);
            $variance = $amount - $spent;
            $budgetStatus[] = (object) [
                'category' => $b['category_name'] ?? 'Uncategorized',
                'budget'   => $amount,
                'spent'    => $spent,
                'variance' => $variance,
                'pct'      => $amount > 0 ? min(100, (int) round(($spent / $amount) * 100)) : 0,
                'over'     => $spent > $amount,
            ];
        }

        $this->view('dashboard.expenses.index', [
            'rows'        => $this->paginateRows($rows, $total, $perPage, $page, \App\Models\Expense::class),
            'expenses'    => $this->paginateRows($rows, $total, $perPage, $page, \App\Models\Expense::class),
            'total'       => $totalAmount,
            'page'        => $page,
            'perPage'     => $perPage,
            'lastPage'    => max(1, (int) ceil($total / $perPage)),
            'category'    => $category,
            'dateFrom'    => $dateFrom,
            'dateTo'      => $dateTo,
            'categories'  => new \App\Core\Support\Collection(\App\Models\ExpenseCategory::hydrate($categories)),
            'totalAmount' => $totalAmount,
            'budgetStatus' => new \App\Core\Support\Collection($budgetStatus),
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.expenses.create', [
            'accounts'   => new \App\Core\Support\Collection(\App\Models\ChartOfAccount::hydrate(
                $this->db->fetchAll("SELECT id, code, name_en FROM chart_of_accounts WHERE type = 'expense' AND is_active = 1 ORDER BY code ASC")
            )),
            'categories' => new \App\Core\Support\Collection(\App\Models\ExpenseCategory::hydrate(
                $this->db->fetchAll("SELECT id, name FROM expense_categories WHERE is_active = 1 ORDER BY name ASC")
            )),
        ]);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $expense = $this->db->fetch("SELECT * FROM expenses WHERE id = ? LIMIT 1", [$id]);
        if (!$expense) {
            Session::getInstance()->flash('error', 'Expense not found.');
            $this->redirect('/dashboard/expenses');
            return;
        }

        $this->view('dashboard.expenses.edit', [
            'expense'    => \App\Models\Expense::newFromRow($expense),
            'accounts'   => new \App\Core\Support\Collection(\App\Models\ChartOfAccount::hydrate(
                $this->db->fetchAll("SELECT id, code, name_en FROM chart_of_accounts WHERE type = 'expense' AND is_active = 1 ORDER BY code ASC")
            )),
            'categories' => new \App\Core\Support\Collection(\App\Models\ExpenseCategory::hydrate(
                $this->db->fetchAll("SELECT id, name FROM expense_categories WHERE is_active = 1 ORDER BY name ASC")
            )),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'amount'               => 'required|numeric',
            'date'                 => 'required',
            'expense_category_id'  => 'numeric',
            'chart_of_account_id'  => 'numeric',
            'vendor'               => 'max:191',
            'payment_method'       => 'max:32',
            'note'                 => 'max:2000',
        ]);

        $this->db->insert('expenses', [
            'amount'              => $data['amount'],
            'date'                => $data['date'],
            'expense_category_id' => $data['expense_category_id'] ?? null,
            'category'            => $this->categoryName((int) ($data['expense_category_id'] ?? 0)),
            'chart_of_account_id' => $data['chart_of_account_id'] ?? null,
            'vendor'              => $data['vendor'] ?? null,
            'payment_method'      => $data['payment_method'] ?? null,
            'note'                => $data['note'] ?? null,
            'created_by'          => Auth::id(),
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
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
            'amount'               => 'required|numeric',
            'date'                 => 'required',
            'expense_category_id'  => 'numeric',
            'chart_of_account_id'  => 'numeric',
            'vendor'               => 'max:191',
            'payment_method'       => 'max:32',
            'note'                 => 'max:2000',
        ]);

        $this->db->update('expenses', [
            'amount'              => $data['amount'],
            'date'                => $data['date'],
            'expense_category_id' => $data['expense_category_id'] ?? null,
            'category'            => $this->categoryName((int) ($data['expense_category_id'] ?? 0)),
            'chart_of_account_id' => $data['chart_of_account_id'] ?? null,
            'vendor'              => $data['vendor'] ?? null,
            'payment_method'      => $data['payment_method'] ?? null,
            'note'                => $data['note'] ?? null,
            'updated_at'          => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Expense updated.');
        $this->redirect('/dashboard/expenses');
    }

    public function export(): void
    {
        Auth::requireAuth();
        $rows = $this->db->fetchAll(
            "SELECT e.*, ec.name as category_name
             FROM expenses e
             LEFT JOIN expense_categories ec ON e.expense_category_id = ec.id
             ORDER BY e.date DESC"
        );

        $filename = 'expenses_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['date', 'category', 'vendor', 'amount', 'payment_method', 'note']);
        foreach ($rows as $e) {
            fputcsv($out, [
                $e['date'] ?? '',
                $this->escapeCsv($e['category_name'] ?? ''),
                $this->escapeCsv($e['vendor'] ?? ''),
                number_format((float) ($e['amount'] ?? 0), 2, '.', ''),
                $this->escapeCsv($e['payment_method'] ?? ''),
                $this->escapeCsv($e['note'] ?? ''),
            ]);
        }
        fclose($out);
        exit;
    }

    protected function escapeCsv(?string $value): string
    {
        $value = (string) $value;
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }

    protected function categoryName(int $categoryId): string
    {
        if ($categoryId <= 0) {
            return '';
        }
        $row = $this->db->fetch("SELECT name FROM expense_categories WHERE id = ? LIMIT 1", [$categoryId]);
        return (string) ($row['name'] ?? '');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('expenses', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Expense deleted.');
        $this->redirect('/dashboard/expenses');
    }
}
