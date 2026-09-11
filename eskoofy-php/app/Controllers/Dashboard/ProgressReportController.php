<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Support\Collection;

class ProgressReportController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;

        $where = "s.status = 'active'";
        $params = [];
        if (!empty($_GET['class_id'])) {
            $where .= ' AND s.class_id = ?';
            $params[] = (int) $_GET['class_id'];
        }
        if (!empty($_GET['section_id'])) {
            $where .= ' AND s.section_id = ?';
            $params[] = (int) $_GET['section_id'];
        }
        if (!empty($_GET['batch_id'])) {
            $where .= ' AND s.batch_id = ?';
            $params[] = (int) $_GET['batch_id'];
        }

        $total = (int) ($db->fetch("SELECT COUNT(*) as cnt FROM students s WHERE {$where}", $params)['cnt'] ?? 0);
        $offset = ($page - 1) * $perPage;

        $studentRows = $db->fetchAll(
            "SELECT s.*, u.name as user_name, c.name as class_name, sec.name as section_name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             WHERE {$where}
             ORDER BY u.name ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $students = $this->paginateRows($studentRows, $total, $perPage, $page, \App\Models\Student::class);

        $classes = \App\Models\SchoolClass::hydrate($db->fetchAll("SELECT id, name FROM school_classes ORDER BY name"));
        $sections = \App\Models\Section::hydrate($db->fetchAll("SELECT id, name FROM sections ORDER BY name"));
        $batches = \App\Models\Batch::hydrate($db->fetchAll("SELECT id, name FROM batches ORDER BY name"));

        $this->view('dashboard.progress_reports.index', [
            'students' => $students,
            'classes'  => new Collection($classes),
            'sections' => new Collection($sections),
            'batches'  => new Collection($batches),
        ]);
    }

    public function generate(int $studentId): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();

        $student = $db->fetch(
            "SELECT s.*, u.name, c.name as class_name, sec.name as section_name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             WHERE s.id = ?",
            [$studentId]
        );

        if (!$student) {
            Session::getInstance()->flash('error', 'Student not found.');
            $this->redirect('/dashboard/progress-reports');
            return;
        }

        $results = $db->fetchAll(
            "SELECT er.*, e.name as exam_name, sub.name as subject_name
             FROM exam_results er
             LEFT JOIN exams e ON er.exam_id = e.id
             LEFT JOIN subjects sub ON er.subject_id = sub.id
             WHERE er.student_id = ?
             ORDER BY e.start_date DESC, sub.name ASC",
            [$studentId]
        );

        $attendanceRate = $this->attendanceRate($studentId);
        $totalMarks = 0;
        $count = 0;
        foreach ($results as $r) {
            $totalMarks += (float) ($r['marks'] ?? 0);
            $count++;
        }
        $average = $count > 0 ? round($totalMarks / $count, 1) : 0;

        $this->view('dashboard.progress_reports.generate', [
            'student'        => $student,
            'results'        => $results,
            'attendanceRate' => $attendanceRate,
            'average'        => $average,
        ]);
    }

    private function attendanceRate(int $studentId): float
    {
        $db = Database::getInstance();
        $row = $db->fetch(
            "SELECT COUNT(*) as total,
                    SUM(CASE WHEN status IN ('present','late','half_day') THEN 1 ELSE 0 END) as present
             FROM attendances WHERE student_id = ?",
            [$studentId]
        );
        $total = (int) ($row['total'] ?? 0);
        if ($total === 0) return 0.0;
        return round(100 * (int)($row['present'] ?? 0) / $total, 1);
    }
}
