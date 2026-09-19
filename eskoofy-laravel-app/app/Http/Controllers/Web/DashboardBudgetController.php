<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardBudgetController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        $rows = Budget::query()
            ->with('category')
            ->orderByDesc('period_start')
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.budgets.index', [
            'rows' => $rows,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        return view('dashboard.budgets.create', [
            'categories' => ExpenseCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        $data = $request->validate([
            'expense_category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'period_type' => ['required', 'in:monthly,yearly,custom'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Budget::create($data);

        return redirect()->route('dashboard.budgets.index')->with('status', __('Budget created.'));
    }

    public function edit(Request $request, Budget $budget): View
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        return view('dashboard.budgets.edit', [
            'budget' => $budget,
            'categories' => ExpenseCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Budget $budget): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        $data = $request->validate([
            'expense_category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'period_type' => ['required', 'in:monthly,yearly,custom'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $budget->update($data);

        return redirect()->route('dashboard.budgets.index')->with('status', __('Budget updated.'));
    }

    public function destroy(Request $request, Budget $budget): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        $budget->delete();

        return back()->with('status', __('Budget deleted.'));
    }
}
