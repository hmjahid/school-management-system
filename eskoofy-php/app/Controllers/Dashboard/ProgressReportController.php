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

        $this->view('dashboard.progress-reports.index', [
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
            "SELECT s.*, u.name, c.name as class_name, sec.name as section_name, b.name as batch_name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             LEFT JOIN batches b ON s.batch_id = b.id
             WHERE s.id = ?",
            [$studentId]
        );

        if (!$student) {
            Session::getInstance()->flash('error', 'Student not found.');
            $this->redirect('/dashboard/progress-reports');
            return;
        }

        $results = $db->fetchAll(
            "SELECT er.*, e.name as exam_name, e.total_marks, sub.name as subject_name
             FROM exam_results er
             LEFT JOIN exams e ON er.exam_id = e.id
             LEFT JOIN subjects sub ON er.subject_id = sub.id
             WHERE er.student_id = ?
             ORDER BY e.start_date DESC, sub.name ASC",
            [$studentId]
        );

        $rows = [];
        $totalObtained = 0.0;
        $totalPossible = 0.0;
        foreach ($results as $r) {
            $obtained = (float) ($r['obtained_marks'] ?? 0);
            $total = (float) ($r['total_marks'] ?? 0);
            $percentage = $total > 0 ? round(($obtained / $total) * 100, 2) : 0;
            $gradeInfo = $this->calculateGrade($percentage);

            $totalObtained += $obtained;
            $totalPossible += $total;

            $rows[] = [
                'exam_name'  => $r['exam_name'] ?? 'N/A',
                'subject'    => $r['subject_name'] ?? 'N/A',
                'obtained'   => $obtained,
                'total'      => $total,
                'percentage' => $percentage,
                'grade'      => $gradeInfo['grade'],
                'points'     => $gradeInfo['points'],
                'remark'     => $gradeInfo['remark'],
                'status'     => $r['status'] ?? '',
            ];
        }

        $overallPercentage = $totalPossible > 0 ? round(($totalObtained / $totalPossible) * 100, 2) : 0;
        $overall = $this->calculateGrade($overallPercentage);

        $submissions = $db->fetchAll(
            "SELECT asub.marks, a.title, a.total_marks as assignment_total, sub.name as subject_name
             FROM assignment_submissions asub
             LEFT JOIN assignments a ON asub.assignment_id = a.id
             LEFT JOIN subjects sub ON a.subject_id = sub.id
             WHERE asub.student_id = ? AND asub.marks IS NOT NULL",
            [$studentId]
        );

        $assignmentRows = [];
        $assignmentTotalPercentage = 0;
        $assignmentCount = 0;
        foreach ($submissions as $sub) {
            $marks = (float) ($sub['marks'] ?? 0);
            $total = (float) ($sub['assignment_total'] ?? 0);
            $pct = $total > 0 ? round(($marks / $total) * 100, 2) : 0;

            $assignmentTotalPercentage += $pct;
            $assignmentCount++;

            $assignmentRows[] = [
                'title'      => $sub['title'] ?? 'Assignment',
                'subject'    => $sub['subject_name'] ?? 'N/A',
                'marks'      => $marks,
                'total'      => $total,
                'percentage' => $pct,
            ];
        }

        $assignmentAverage = $assignmentCount > 0 ? round($assignmentTotalPercentage / $assignmentCount, 2) : null;

        $this->view('dashboard.progress-reports.show', [
            'student'            => \App\Models\Student::newFromRow($student),
            'rows'               => $rows,
            'overall'            => $overall,
            'overallPercentage'  => $overallPercentage,
            'assignmentRows'     => $assignmentRows,
            'assignmentAverage'  => $assignmentAverage,
            'settings'           => \App\Models\WebsiteSetting::getSettings(),
            'generatedAt'        => new \App\Core\Support\Carbon(),
            'attendanceRate'     => $this->attendanceRate($studentId),
        ]);
    }

    /**
     * Mirrors the app's Exam::calculateGrade() default grading scale.
     */
    private function calculateGrade(float $score): array
    {
        $scale = [
            [80, 100, 'A+', 4.0, 'Excellent'],
            [70, 79, 'A', 3.7, 'Very Good'],
            [65, 69, 'A-', 3.3, 'Good'],
            [60, 64, 'B+', 3.0, 'Above Average'],
            [55, 59, 'B', 2.7, 'Average'],
            [50, 54, 'B-', 2.3, 'Satisfactory'],
            [45, 49, 'C+', 2.0, 'Below Average'],
            [40, 44, 'C', 1.7, 'Pass'],
            [0, 39, 'F', 0.0, 'Fail'],
        ];
        foreach ($scale as [$min, $max, $grade, $points, $remark]) {
            if ($score >= $min && $score <= $max) {
                return ['grade' => $grade, 'points' => $points, 'remark' => $remark];
            }
        }
        return ['grade' => 'F', 'points' => 0.0, 'remark' => 'Fail'];
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
