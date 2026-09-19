<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardBookController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage_books');
        $query = Book::with('category', 'createdBy');
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
            });
        }
        if ($categoryId = $request->integer('category_id')) {
            $query->where('category_id', $categoryId);
        }
        if ($request->has('status') && $request->string('status')->toString() !== '') {
            $query->where('status', $request->boolean('status'));
        }
        $books = $query->latest()->paginate(15)->withQueryString();
        $categories = BookCategory::orderBy('name')->get();

        return view('dashboard.library.books.index', compact('books', 'categories'));
    }

    public function create(): View
    {
        $this->authorize('manage_books');
        $categories = BookCategory::orderBy('name')->get();

        return view('dashboard.library.books.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage_books');
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:50|unique:books,isbn',
            'category_id' => 'nullable|exists:book_categories,id',
            'shelf_location' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:1',
            'purchase_date' => 'nullable|date',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'boolean',
        ]);
        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store('books/covers', 'public');
        }
        $validated['available_quantity'] = $validated['quantity'];
        $validated['created_by'] = $request->user()->id;
        Book::create($validated);

        return redirect()->route('dashboard.library.books.index')->with('status', __('dashboard.book_created'));
    }

    public function show(Book $book): View
    {
        $this->authorize('manage_books');
        $book->load(['category', 'createdBy', 'currentIssues.student', 'currentIssues.teacher', 'currentIssues.issuedBy']);

        return view('dashboard.library.books.show', compact('book'));
    }

    public function edit(Book $book): View
    {
        $this->authorize('manage_books');
        $categories = BookCategory::orderBy('name')->get();

        return view('dashboard.library.books.edit', compact('book', 'categories'));
    }

    public function update(Request $request, Book $book): RedirectResponse
    {
        $this->authorize('manage_books');
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:50|unique:books,isbn,'.$book->id,
            'category_id' => 'nullable|exists:book_categories,id',
            'shelf_location' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:1',
            'purchase_date' => 'nullable|date',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'boolean',
        ]);
        if ($request->hasFile('cover_image')) {
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')->store('books/covers', 'public');
        }
        $diff = $validated['quantity'] - $book->quantity;
        $validated['available_quantity'] = $book->available_quantity + $diff;
        if ($validated['available_quantity'] < 0) {
            $validated['available_quantity'] = 0;
        }
        $book->update($validated);

        return redirect()->route('dashboard.library.books.index')->with('status', __('dashboard.book_updated'));
    }

    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('manage_books');
        if ($book->cover_image) {
            Storage::disk('public')->delete($book->cover_image);
        }
        $book->delete();

        return redirect()->route('dashboard.library.books.index')->with('status', __('dashboard.book_deleted'));
    }
}
