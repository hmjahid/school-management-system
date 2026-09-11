<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;

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
        $db = Database::getInstance();

        $rows = $db->fetchAll(
            "SELECT bi.*, b.title, b.author,
                    COALESCE(CONCAT_WS(' ', s.first_name, s.last_name), tu.name) as borrower,
                    DATEDIFF(CURRENT_DATE, bi.due_date) as days_overdue
             FROM book_issues bi
             LEFT JOIN books b ON bi.book_id = b.id
             LEFT JOIN students s ON bi.student_id = s.id
             LEFT JOIN teachers t ON bi.teacher_id = t.id
             LEFT JOIN users tu ON t.user_id = tu.id
             WHERE bi.return_date IS NULL AND bi.due_date < CURRENT_DATE
             ORDER BY bi.due_date ASC LIMIT 200"
        );

        $this->view('dashboard.library_reports.overdue', ['rows' => $rows]);
    }

    public function history(): void
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
             ORDER BY bi.issue_date DESC LIMIT 200"
        );

        $this->view('dashboard.library_reports.history', ['rows' => $rows]);
    }
}
