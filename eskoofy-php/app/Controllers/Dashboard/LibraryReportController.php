<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class LibraryReportController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.library_reports.index');
    }

    public function currentlyIssued(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();

        $rows = $db->fetchAll(
            "SELECT bi.*, b.title, b.author, u.name as borrower
             FROM book_issues bi
             LEFT JOIN books b ON bi.book_id = b.id
             LEFT JOIN users u ON bi.user_id = u.id
             WHERE bi.returned_at IS NULL
             ORDER BY bi.issue_date DESC LIMIT 200"
        );

        $this->view('dashboard.library_reports.currently_issued', ['rows' => $rows]);
    }

    public function overdue(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();

        $rows = $db->fetchAll(
            "SELECT bi.*, b.title, b.author, u.name as borrower,
                    DATEDIFF(CURRENT_DATE, bi.due_date) as days_overdue
             FROM book_issues bi
             LEFT JOIN books b ON bi.book_id = b.id
             LEFT JOIN users u ON bi.user_id = u.id
             WHERE bi.returned_at IS NULL AND bi.due_date < CURRENT_DATE
             ORDER BY bi.due_date ASC LIMIT 200"
        );

        $this->view('dashboard.library_reports.overdue', ['rows' => $rows]);
    }

    public function history(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();

        $rows = $db->fetchAll(
            "SELECT bi.*, b.title, b.author, u.name as borrower
             FROM book_issues bi
             LEFT JOIN books b ON bi.book_id = b.id
             LEFT JOIN users u ON bi.user_id = u.id
             ORDER BY bi.issue_date DESC LIMIT 200"
        );

        $this->view('dashboard.library_reports.history', ['rows' => $rows]);
    }
}
