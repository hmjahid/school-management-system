<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookIssue;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardLibraryReportController extends Controller
{
    public function index(): View
    {
        $this->authorize('view_library_reports');
        $totalBooks = Book::count();
        $totalIssues = BookIssue::count();
        $issuedBooks = BookIssue::issued()->count();
        $overdueBooks = BookIssue::overdue()->count();
        $totalFines = BookIssue::where('fine_paid', true)->sum('late_fee');
        $lostBooks = BookIssue::where('status', BookIssue::STATUS_LOST)->count();
        return view('dashboard.library.reports.index', compact(
            'totalBooks', 'totalIssues', 'issuedBooks', 'overdueBooks', 'totalFines', 'lostBooks'
        ));
    }

    public function currentlyIssued(Request $request): View
    {
        $this->authorize('view_library_reports');
        $issues = BookIssue::with(['book', 'student', 'teacher.user', 'issuedBy'])
            ->issued()
            ->latest()
            ->paginate(15)
            ->withQueryString();
        return view('dashboard.library.reports.index', compact('issues') + ['view' => 'issued']);
    }

    public function overdue(Request $request): View
    {
        $this->authorize('view_library_reports');
        $issues = BookIssue::with(['book', 'student', 'teacher.user', 'issuedBy'])
            ->overdue()
            ->latest()
            ->paginate(15)
            ->withQueryString();
        return view('dashboard.library.reports.index', compact('issues') + ['view' => 'overdue']);
    }

    public function history(Request $request): View
    {
        $this->authorize('view_library_reports');
        $query = BookIssue::with(['book', 'student', 'teacher.user', 'issuedBy']);
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($search = $request->string('search')->toString()) {
            $query->whereHas('book', fn($b) => $b->where('title', 'like', "%{$search}%"));
        }
        $issues = $query->latest()->paginate(15)->withQueryString();
        return view('dashboard.library.reports.index', compact('issues') + ['view' => 'history']);
    }
}
