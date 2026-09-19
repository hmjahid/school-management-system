<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;

class ReportController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.reports.index');
    }

    public function students(): void
    {
        Auth::requireAuth();
        $byClass = $this->db->fetchAll(
            "SELECT c.name as class_name, COUNT(*) as total
             FROM students s
             LEFT JOIN school_classes c ON s.class_id = c.id
             WHERE s.status = 'active'
             GROUP BY c.name
             ORDER BY c.name ASC"
        );

        $byGender = $this->db->fetchAll(
            "SELECT gender, COUNT(*) as total FROM students WHERE gender IS NOT NULL AND status = 'active' GROUP BY gender"
        );

        $total = $this->db->count('students', "status = 'active'");

        $this->view('dashboard.reports.students', [
            'byClass'  => $byClass,
            'byGender' => $byGender,
            'total'    => $total,
        ]);
    }

    public function fees(): void
    {
        Auth::requireAuth();
        $from = $_GET['from'] ?? date('Y-m-01', strtotime('-6 months'));
        $to = $_GET['to'] ?? date('Y-m-d');

        $byMonth = $this->db->fetchAll(
            "SELECT DATE_FORMAT(payment_date, '%Y-%m') as bucket, SUM(paid_amount) as total, COUNT(*) as count
             FROM payments WHERE payment_status = 'completed' AND payment_date BETWEEN ? AND ?
             GROUP BY bucket ORDER BY bucket ASC",
            [$from, $to]
        );

        $byStatus = $this->db->fetchAll(
            "SELECT payment_status, SUM(paid_amount) as total, COUNT(*) as count
             FROM payments WHERE payment_date BETWEEN ? AND ?
             GROUP BY payment_status ORDER BY payment_status ASC",
            [$from, $to]
        );

        $byMethod = $this->db->fetchAll(
            "SELECT payment_method, SUM(paid_amount) as total, COUNT(*) as count
             FROM payments WHERE payment_status = 'completed' AND payment_date BETWEEN ? AND ?
             GROUP BY payment_method ORDER BY payment_method ASC",
            [$from, $to]
        );

        $summary = $this->db->fetch(
            "SELECT COALESCE(SUM(paid_amount), 0) as total, COUNT(*) as count
             FROM payments WHERE payment_status = 'completed' AND payment_date BETWEEN ? AND ?",
            [$from, $to]
        );

        $this->view('dashboard.reports.fees', [
            'byMonth'  => new \App\Core\Support\Collection(\App\Models\Payment::hydrate($byMonth)),
            'byStatus' => new \App\Core\Support\Collection(\App\Models\Payment::hydrate($byStatus)),
            'byMethod' => new \App\Core\Support\Collection(\App\Models\Payment::hydrate($byMethod)),
            'total'    => (float) ($summary['total'] ?? 0),
            'count'    => (int) ($summary['count'] ?? 0),
            'from'     => new \App\Core\Support\Carbon($from),
            'to'       => new \App\Core\Support\Carbon($to),
        ]);
    }

    public function attendance(): void
    {
        Auth::requireAuth();
        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
        $to = $_GET['to'] ?? date('Y-m-d');

        $byDate = $this->db->fetchAll(
            "SELECT date as day,
                SUM(CASE WHEN status IN ('present','late','half_day') THEN 1 ELSE 0 END) as present_count,
                COUNT(*) as total_count
             FROM attendances WHERE date BETWEEN ? AND ?
             GROUP BY date ORDER BY date ASC",
            [$from, $to]
        );

        $byClass = $this->db->fetchAll(
            "SELECT c.name as class_name,
                SUM(CASE WHEN a.status IN ('present','late','half_day') THEN 1 ELSE 0 END) as present_count,
                COUNT(*) as total_count
             FROM attendances a
             JOIN students s ON a.student_id = s.id
             JOIN school_classes c ON s.class_id = c.id
             WHERE a.date BETWEEN ? AND ?
             GROUP BY c.name ORDER BY c.name ASC",
            [$from, $to]
        );

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as t FROM attendances WHERE date BETWEEN ? AND ?", [$from, $to]
        )['t'] ?? 0);
        $present = (int) ($this->db->fetch(
            "SELECT COUNT(*) as t FROM attendances WHERE date BETWEEN ? AND ? AND status IN ('present','late','half_day')",
            [$from, $to]
        )['t'] ?? 0);
        $rate = $total > 0 ? round(100 * $present / $total, 1) : 0;

        $this->view('dashboard.reports.attendance', [
            'byDate'  => new \App\Core\Support\Collection(\App\Models\Attendance::hydrate($byDate)),
            'byClass' => new \App\Core\Support\Collection(\App\Models\Attendance::hydrate($byClass)),
            'total'   => $total,
            'present' => $present,
            'rate'    => $rate,
            'from'    => new \App\Core\Support\Carbon($from),
            'to'      => new \App\Core\Support\Carbon($to),
        ]);
    }

    public function exams(): void
    {
        Auth::requireAuth();
        $exams = $this->db->fetchAll(
            "SELECT e.*,
                (SELECT COUNT(*) FROM exam_results WHERE exam_id = e.id) as result_count,
                (SELECT COUNT(*) FROM exam_results WHERE exam_id = e.id AND grade = 'Pass') as pass_count,
                (SELECT COUNT(*) FROM exam_results WHERE exam_id = e.id AND grade = 'Fail') as fail_count
             FROM exams e
             WHERE e.is_published = 1
             ORDER BY e.start_date DESC LIMIT 20"
        );

        $this->view('dashboard.reports.exams', ['exams' => $exams]);
    }

    public function analytics(): void
    {
        Auth::requireAuth();

        $months = [];
        $labels = [];
        for ($i = 11; $i >= 0; $i--) {
            $ts = strtotime("first day of -{$i} months");
            $months[] = date('Y-m', $ts);
            $labels[] = date('M Y', $ts);
        }
        $fromMonth = date('Y-m-01', strtotime('-11 months'));

        $studentGrowth = array_fill(0, 12, 0);
        $rows = $this->db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as bucket, COUNT(*) as total
             FROM students WHERE created_at >= ? GROUP BY bucket",
            [$fromMonth . ' 00:00:00']
        );
        foreach ($rows as $row) {
            $idx = array_search($row['bucket'] ?? null, $months, true);
            if ($idx !== false) {
                $studentGrowth[$idx] = (int) ($row['total'] ?? 0);
            }
        }

        $revenue = array_fill(0, 12, 0.0);
        $rows = $this->db->fetchAll(
            "SELECT DATE_FORMAT(payment_date, '%Y-%m') as bucket, SUM(paid_amount) as total
             FROM payments WHERE payment_status = 'completed' AND payment_date >= ? GROUP BY bucket",
            [$fromMonth]
        );
        foreach ($rows as $row) {
            $idx = array_search($row['bucket'] ?? null, $months, true);
            if ($idx !== false) {
                $revenue[$idx] = (float) ($row['total'] ?? 0);
            }
        }

        $expenses = array_fill(0, 12, 0.0);
        $rows = $this->db->fetchAll(
            "SELECT DATE_FORMAT(date, '%Y-%m') as bucket, SUM(amount) as total
             FROM expenses WHERE date >= ? GROUP BY bucket",
            [$fromMonth]
        );
        foreach ($rows as $row) {
            $idx = array_search($row['bucket'] ?? null, $months, true);
            if ($idx !== false) {
                $expenses[$idx] = (float) ($row['total'] ?? 0);
            }
        }

        $feeCollected = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(paid_amount), 0) as t FROM payments WHERE payment_status = 'completed' AND payment_date >= ?",
            [date('Y-m-01')]
        )['t'] ?? 0);

        $totalStudents = (int) ($this->db->fetch(
            "SELECT COUNT(*) as t FROM students WHERE status = 'active'"
        )['t'] ?? 0);
        $monthlyFees = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as t FROM fees WHERE status = 'active' AND frequency IN ('monthly', 'recurring')"
        )['t'] ?? 0);
        $feeTarget = $monthlyFees * max(1, $totalStudents);
        $feeTargetPercent = $feeTarget > 0 ? round(100 * $feeCollected / $feeTarget, 1) : 0;

        $attendanceByClass = $this->db->fetchAll(
            "SELECT c.name as class_name,
                ROUND(100.0 * SUM(CASE WHEN a.status IN ('present','late','half_day') THEN 1 ELSE 0 END) / COUNT(*), 1) as rate
             FROM attendances a
             JOIN students s ON a.student_id = s.id
             JOIN school_classes c ON s.class_id = c.id
             WHERE a.date >= ?
             GROUP BY c.name ORDER BY c.name ASC",
            [date('Y-m-d', strtotime('-30 days'))]
        );

        $teacherWorkload = $this->db->fetchAll(
            "SELECT u.name as teacher_name, COUNT(DISTINCT ct.class_id) as classes_count
             FROM teachers t
             JOIN users u ON t.user_id = u.id
             JOIN class_teacher ct ON t.id = ct.teacher_id
             GROUP BY u.name ORDER BY classes_count DESC LIMIT 10"
        );

        $this->view('dashboard.reports.analytics', [
            'months'            => $labels,
            'studentGrowth'     => $studentGrowth,
            'revenue'           => $revenue,
            'expenses'          => $expenses,
            'feeTarget'         => $feeTarget,
            'feeCollected'      => $feeCollected,
            'feeTargetPercent'  => $feeTargetPercent,
            'attendanceByClass' => new \App\Core\Support\Collection(\App\Models\Attendance::hydrate($attendanceByClass)),
            'teacherWorkload'   => new \App\Core\Support\Collection(\App\Models\User::hydrate($teacherWorkload)),
        ]);
    }

    public function exportCsv(string $type): void
    {
        Auth::requireAuth();
        if (!in_array($type, ['fees', 'attendance', 'students'], true)) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        [$header, $rows] = $this->buildExportRows($type);
        $filename = 'report-' . $type . '-' . date('Ymd-His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $out = fopen('php://output', 'w');
        fputcsv($out, $header);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    public function buildExportRows(string $type): array
    {
        if ($type === 'fees') {
            $rows = $this->db->fetchAll(
                "SELECT DATE_FORMAT(payment_date, '%Y-%m') as bucket, SUM(paid_amount) as total, COUNT(*) as count
                 FROM payments WHERE payment_status = 'completed' GROUP BY bucket ORDER BY bucket ASC"
            );
            $data = [];
            foreach ($rows as $r) {
                $data[] = [$r['bucket'], $r['total'], $r['count']];
            }
            return [['month', 'total', 'count'], $data];
        }
        if ($type === 'attendance') {
            $rows = $this->db->fetchAll(
                "SELECT a.date, c.name as class_name,
                    SUM(CASE WHEN a.status IN ('present','late','half_day') THEN 1 ELSE 0 END) as present,
                    COUNT(*) as total
                 FROM attendances a
                 JOIN students s ON a.student_id = s.id
                 JOIN school_classes c ON s.class_id = c.id
                 GROUP BY a.date, c.name ORDER BY a.date ASC"
            );
            $data = [];
            foreach ($rows as $r) {
                $data[] = [$r['date'], $r['class_name'], $r['present'], $r['total']];
            }
            return [['date', 'class', 'present', 'total'], $data];
        }

        $rows = $this->db->fetchAll(
            "SELECT c.name as class_name, COUNT(*) as total
             FROM students s LEFT JOIN school_classes c ON s.class_id = c.id
             WHERE s.status = 'active'
             GROUP BY c.name ORDER BY c.name ASC"
        );
        $data = [];
        foreach ($rows as $r) {
            $data[] = [$r['class_name'], $r['total']];
        }
        return [['class', 'count'], $data];
    }
}
