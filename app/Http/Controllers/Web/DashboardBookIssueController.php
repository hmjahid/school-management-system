<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookIssue;
use App\Models\LibrarySetting;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardBookIssueController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('issue_books');
        $query = BookIssue::with(['book', 'student', 'teacher.user', 'issuedBy']);
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('book', fn($b) => $b->where('title', 'like', "%{$search}%"))
                  ->orWhereHas('student', fn($s) => $s->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                  ->orWhereHas('teacher.user', fn($t) => $t->where('name', 'like', "%{$search}%"));
            });
        }
        $issues = $query->latest()->paginate(15)->withQueryString();
        return view('dashboard.library.issues.index', compact('issues'));
    }

    public function create(): View
    {
        $this->authorize('issue_books');
        $books = Book::where('status', true)->where('available_quantity', '>', 0)->orderBy('title')->get();
        $students = Student::orderBy('first_name')->limit(500)->get();
        $teachers = Teacher::with('user')->limit(500)->get()->sortBy(fn($t) => $t->user?->name);
        $settings = LibrarySetting::getSettings();
        return view('dashboard.library.issues.create', compact('books', 'students', 'teachers', 'settings'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('issue_books');
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'student_id' => 'nullable|exists:students,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string|max:1000',
        ]);
        if (!$validated['student_id'] && !$validated['teacher_id']) {
            return back()->withErrors(['borrower' => __('Select a student or teacher.')])->withInput();
        }
        $book = Book::findOrFail($validated['book_id']);
        if (!$book->isAvailable()) {
            return back()->with('error', __('dashboard.book_not_available'))->withInput();
        }
        $validated['issued_by'] = $request->user()->id;
        $validated['status'] = BookIssue::STATUS_ISSUED;
        $book->decrement('available_quantity');
        BookIssue::create($validated);
        return redirect()->route('dashboard.library.issues.index')->with('status', __('dashboard.issue_created'));
    }

    public function show(BookIssue $issue): View
    {
        $this->authorize('issue_books');
        $issue->load(['book', 'student', 'teacher.user', 'issuedBy']);
        $settings = LibrarySetting::getSettings();
        return view('dashboard.library.issues.show', compact('issue', 'settings'));
    }

    public function returnBook(BookIssue $issue): RedirectResponse
    {
        $this->authorize('issue_books');
        if ($issue->status !== BookIssue::STATUS_ISSUED) {
            return back()->with('error', __('Book is not currently issued.'));
        }
        $settings = LibrarySetting::getSettings();
        $issue->return_date = now();
        $lateFee = $issue->calculateLateFee($settings->late_fee_per_day);
        $issue->late_fee = $lateFee;
        $issue->status = BookIssue::STATUS_RETURNED;
        $issue->save();
        $issue->book()->increment('available_quantity');
        return back()->with('status', __('dashboard.book_returned'));
    }

    public function collectFine(BookIssue $issue): RedirectResponse
    {
        $this->authorize('collect_dues');
        if ($issue->status !== BookIssue::STATUS_RETURNED) {
            return back()->with('error', __('Can only collect fine for returned books.'));
        }
        $issue->update(['fine_paid' => true]);
        return back()->with('status', __('dashboard.fine_collected'));
    }

    public function markLost(BookIssue $issue): RedirectResponse
    {
        $this->authorize('issue_books');
        if ($issue->status !== BookIssue::STATUS_ISSUED) {
            return back()->with('error', __('Only issued books can be marked as lost.'));
        }
        $issue->update(['status' => BookIssue::STATUS_LOST]);
        return back()->with('status', __('Book marked as lost.'));
    }

    public function destroy(BookIssue $issue): RedirectResponse
    {
        $this->authorize('issue_books');
        if ($issue->status === BookIssue::STATUS_ISSUED) {
            $issue->book()->increment('available_quantity');
        }
        $issue->delete();
        return redirect()->route('dashboard.library.issues.index')->with('status', __('Issue record deleted.'));
    }
}
