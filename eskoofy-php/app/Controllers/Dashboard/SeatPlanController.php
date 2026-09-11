<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class SeatPlanController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;

        $where = '1=1';
        $params = [];
        if (isset($_GET['published']) && $_GET['published'] !== '') {
            $where .= ' AND is_published = ?';
            $params[] = (int) (bool) $_GET['published'];
        }

        $total = (int) ($db->fetch("SELECT COUNT(*) as cnt FROM exams WHERE {$where}", $params)['cnt'] ?? 0);
        $offset = ($page - 1) * $perPage;

        $examRows = $db->fetchAll(
            "SELECT * FROM exams WHERE {$where} ORDER BY start_date DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $exams = $this->paginateRows($examRows, $total, $perPage, $page, \App\Models\Exam::class);

        $this->view('dashboard.seat_plans.index', ['exams' => $exams]);
    }

    public function generate(int $examId): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();
        $exam = $db->fetch("SELECT * FROM exams WHERE id = ?", [$examId]);
        if (!$exam) {
            Session::getInstance()->flash('error', 'Exam not found.');
            $this->redirect('/dashboard/seat-plans');
            return;
        }

        $students = $db->fetchAll(
            "SELECT s.id, s.admission_number, u.name, c.name as class_name, sec.name as section_name, s.roll_number
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             WHERE s.status = 'active'
             ORDER BY c.name, sec.name, s.roll_number, u.name ASC"
        );

        $seating = [];
        $i = 1;
        foreach ($students as $s) {
            $seating[] = [
                'room'    => 'Room ' . (1 + intdiv($i - 1, 30)),
                'seat'    => (($i - 1) % 30) + 1,
                'student' => $s,
            ];
            $i++;
        }

        $this->view('dashboard.seat_plans.generate', [
            'exam'    => $exam,
            'seating' => $seating,
        ]);
    }
}
