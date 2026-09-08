<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class DashboardExpenseController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        $query = Expense::query()->with(['account', 'creator']);

        if ($request->filled('from')) {
            $query->where('date', '>=', $request->string('from')->toString());
        }
        if ($request->filled('to')) {
            $query->where('date', '<=', $request->string('to')->toString());
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        $rows = $query->orderByDesc('date')->orderByDesc('id')->paginate(20)->withQueryString();
        $total = (clone $query)->sum('amount');

        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $budgets = Budget::query()
            ->with('category')
            ->where('period_type', 'monthly')
            ->where('period_start', '<=', $now)
            ->where('period_end', '>=', $now)
            ->get();

        $budgetStatus = $budgets->map(function (Budget $budget) use ($monthStart, $monthEnd) {
            $spent = Expense::query()
                ->where('expense_category_id', $budget->expense_category_id)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->sum('amount');

            $amount = (float) $budget->amount;
            $spent = (float) $spent;
            $variance = $amount - $spent;

            return (object) [
                'category' => $budget->category?->name ?? __('Uncategorized'),
                'budget' => $amount,
                'spent' => $spent,
                'variance' => $variance,
                'pct' => $amount > 0 ? min(100, round(($spent / $amount) * 100)) : 0,
                'over' => $spent > $amount,
            ];
        });

        return view('dashboard.expenses.index', [
            'rows' => $rows,
            'total' => $total,
            'categories' => ExpenseCategory::where('is_active', true)->orderBy('name')->get(),
            'budgetStatus' => $budgetStatus,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        return view('dashboard.expenses.create', [
            'accounts' => ChartOfAccount::where('type', ChartOfAccount::TYPE_EXPENSE)->where('is_active', true)->orderBy('code')->get(),
            'categories' => ExpenseCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        $data = $request->validate([
            'expense_category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'category' => ['nullable', 'string', 'max:64'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'vendor' => ['nullable', 'string', 'max:191'],
            'payment_method' => ['nullable', 'string', 'max:32'],
            'note' => ['nullable', 'string', 'max:2000'],
            'chart_of_account_id' => ['nullable', 'integer', 'exists:chart_of_accounts,id'],
        ]);

        $data['created_by'] = $request->user()->id;

        Expense::create($data);

        return redirect()->route('dashboard.expenses.index')->with('status', __('Expense recorded.'));
    }

    public function edit(Request $request, Expense $expense): View
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        return view('dashboard.expenses.edit', [
            'expense' => $expense,
            'accounts' => ChartOfAccount::where('type', ChartOfAccount::TYPE_EXPENSE)->where('is_active', true)->orderBy('code')->get(),
            'categories' => ExpenseCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        $data = $request->validate([
            'expense_category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'category' => ['nullable', 'string', 'max:64'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'vendor' => ['nullable', 'string', 'max:191'],
            'payment_method' => ['nullable', 'string', 'max:32'],
            'note' => ['nullable', 'string', 'max:2000'],
            'chart_of_account_id' => ['nullable', 'integer', 'exists:chart_of_accounts,id'],
        ]);

        $expense->update($data);

        return redirect()->route('dashboard.expenses.index')->with('status', __('Expense updated.'));
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        $expense->delete();

        return back()->with('status', __('Expense deleted.'));
    }

    public function export(Request $request)
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        $rows = Expense::query()->with(['account'])->orderByDesc('date')->get();

        $csv = "date,category,vendor,amount,payment_method,note\n";
        foreach ($rows as $e) {
            $csv .= sprintf("%s,%s,%s,%s,%s,%s\n",
                $e->date?->toDateString(),
                $this->escape($e->category),
                $this->escape($e->vendor),
                number_format((float) $e->amount, 2, '.', ''),
                $this->escape($e->payment_method),
                $this->escape($e->note),
            );
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="expenses_'.now()->format('Ymd_His').'.csv"',
        ]);
    }

    protected function escape(?string $value): string
    {
        $value = (string) $value;
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
