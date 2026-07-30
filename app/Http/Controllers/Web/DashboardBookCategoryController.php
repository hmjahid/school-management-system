<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BookCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardBookCategoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage_books');
        $categories = BookCategory::withCount('books')->orderBy('name')->get();
        return view('dashboard.library.categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage_books');
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:book_categories,name',
            'description' => 'nullable|string|max:1000',
        ]);
        BookCategory::create($validated);
        return redirect()->route('dashboard.library.categories.index')->with('status', __('dashboard.category_created'));
    }

    public function update(Request $request, BookCategory $category): RedirectResponse
    {
        $this->authorize('manage_books');
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:book_categories,name,' . $category->id,
            'description' => 'nullable|string|max:1000',
        ]);
        $category->update($validated);
        return redirect()->route('dashboard.library.categories.index')->with('status', __('dashboard.category_updated'));
    }

    public function destroy(BookCategory $category): RedirectResponse
    {
        $this->authorize('manage_books');
        if ($category->books()->count() > 0) {
            return back()->with('error', __('Cannot delete category with assigned books.'));
        }
        $category->delete();
        return redirect()->route('dashboard.library.categories.index')->with('status', __('dashboard.category_deleted'));
    }
}
