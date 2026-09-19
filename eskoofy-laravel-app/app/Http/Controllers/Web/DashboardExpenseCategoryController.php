<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardExpenseCategoryController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        $rows = ExpenseCategory::query()
            ->withCount('expenses')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.expense-categories.index', [
            'rows' => $rows,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        return view('dashboard.expense-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:64', 'unique:expense_categories,name'],
            'description' => ['nullable', 'string', 'max:2000'],
            'color' => ['nullable', 'string', 'max:16'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        ExpenseCategory::create($data);

        return redirect()->route('dashboard.expense-categories.index')->with('status', __('Category created.'));
    }

    public function edit(Request $request, ExpenseCategory $expenseCategory): View
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        return view('dashboard.expense-categories.edit', [
            'category' => $expenseCategory,
        ]);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:64', 'unique:expense_categories,name,'.$expenseCategory->id],
            'description' => ['nullable', 'string', 'max:2000'],
            'color' => ['nullable', 'string', 'max:16'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $expenseCategory->update($data);

        return redirect()->route('dashboard.expense-categories.index')->with('status', __('Category updated.'));
    }

    public function destroy(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_expenses'), 403);

        $expenseCategory->delete();

        return back()->with('status', __('Category deleted.'));
    }
}
