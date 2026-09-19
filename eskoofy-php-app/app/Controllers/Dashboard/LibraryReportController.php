<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\QueryBuilder;
use App\Core\Schema;
use App\Core\Support\LengthAwarePaginator;
use App\Models\Book;
use App\Models\BookIssue;

class LibraryReportController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();

        $totalBooks = 0;
        $totalIssues = 0;
        $issuedBooks = 0;
        $overdueBooks = 0;
        $totalFines = 0.0;
        $lostBooks = 0;

        try {
            if (Schema::hasTable('books')) {
                $totalBooks = Book::query()->whereNull('deleted_at')->count();
            }
            if (Schema::hasTable('book_issues')) {
                $totalIssues = BookIssue::query()->whereNull('deleted_at')->count();
                $issuedBooks = BookIssue::issued()->whereNull('deleted_at')->count();
                $overdueBooks = BookIssue::overdue()->whereNull('deleted_at')->count();
                $lostBooks = BookIssue::query()->whereNull('deleted_at')->where('status', BookIssue::STATUS_LOST)->count();

                $row = $db->fetch(
                    'SELECT COALESCE(SUM(late_fee), 0) AS total FROM book_issues WHERE fine_paid = 1 AND deleted_at IS NULL'
                );
                $totalFines = (float) ($row['total'] ?? 0);
            }
        } catch (\Throwable) {
        }

        $this->view('dashboard.library.reports.index', [
            'totalBooks'   => $totalBooks,
            'totalIssues'  => $totalIssues,
            'issuedBooks'  => $issuedBooks,
            'overdueBooks' => $overdueBooks,
            'totalFines'   => $totalFines,
            'lostBooks'    => $lostBooks,
        ]);
    }

    public function currentlyIssued(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();

        $rows = $db->fetchAll(
            "SELECT bi.*, b.title, b.author,
                    COALESCE(CONCAT_WS(' ', s.first_name, s.last_name), tu.name) as borrower
             FROM book_issues bi
             LEFT JOIN books b ON bi.book_id = b.id
             LEFT JOIN students s ON bi.student_id = s.id
             LEFT JOIN teachers t ON bi.teacher_id = t.id
             LEFT JOIN users tu ON t.user_id = tu.id
             WHERE bi.return_date IS NULL
             ORDER BY bi.issue_date DESC LIMIT 200"
        );

        $this->view('dashboard.library_reports.currently_issued', ['rows' => $rows]);
    }

    public function overdue(): void
    {
        Auth::requireAuth();
        $issues = $this->reportIssuesPaginator(BookIssue::overdue());
        $this->view('dashboard.library.reports.index', ['view' => 'overdue', 'issues' => $issues]);
    }

    public function history(): void
    {
        Auth::requireAuth();
        $query = BookIssue::query()->whereNull('deleted_at');

        $status = request('status', '');
        if ($status !== '') {
            $query->where('status', $status);
        }

        $search = request('search', '');
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->whereRaw(
                'EXISTS (SELECT 1 FROM books b WHERE b.id = book_issues.book_id AND b.deleted_at IS NULL AND (b.title LIKE ? OR b.author LIKE ?))',
                [$like, $like]
            );
        }

        $issues = $this->reportIssuesPaginator($query);
        $this->view('dashboard.library.reports.index', ['view' => 'history', 'issues' => $issues]);
    }

    public function issued(): void
    {
        Auth::requireAuth();
        $issues = $this->reportIssuesPaginator(BookIssue::issued());
        $this->view('dashboard.library.reports.index', ['view' => 'issued', 'issues' => $issues]);
    }

    private function reportIssuesPaginator(QueryBuilder $query): LengthAwarePaginator
    {
        $page = max(1, (int) request('page', 1));
        $perPage = 15;

        try {
            $total = $query->whereNull('deleted_at')->count();
            $rows = $query->orderBy('created_at', 'desc')
                ->limit($perPage)
                ->offset(($page - 1) * $perPage)
                ->get();
            return $this->paginateRows($rows, $total, $perPage, $page);
        } catch (\Throwable) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }
    }
}
