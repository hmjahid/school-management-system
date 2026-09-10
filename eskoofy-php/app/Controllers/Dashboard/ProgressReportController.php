<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class ProgressReportController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();

        $students = $db->fetchAll(
            "SELECT s.id, s.admission_number, u.name, c.name as class_name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             WHERE s.status = 'active'
             ORDER BY u.name ASC LIMIT 200"
        );

        $this->view('dashboard.progress_reports.index', ['students' => $students]);
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
