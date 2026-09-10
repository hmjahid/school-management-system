<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class ReportController extends Controller
{
    private Database $db;

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

        $total = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(paid_amount), 0) as t FROM payments WHERE payment_status = 'completed' AND payment_date BETWEEN ? AND ?",
            [$from, $to]
        )['t'] ?? 0);

        $this->view('dashboard.reports.fees', [
            'byMonth' => $byMonth,
            'total'   => $total,
            'from'    => $from,
            'to'      => $to,
        ]);
    }

    public function attendance(): void
    {
        Auth::requireAuth();
        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
        $to = $_GET['to'] ?? date('Y-m-d');

        $byDate = $this->db->fetchAll(
            "SELECT date,
                SUM(CASE WHEN status IN ('present','late','half_day') THEN 1 ELSE 0 END) as present_count,
                COUNT(*) as total_count
             FROM attendances WHERE date BETWEEN ? AND ?
             GROUP BY date ORDER BY date ASC",
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
            'byDate'  => $byDate,
            'total'   => $total,
            'present' => $present,
            'rate'    => $rate,
            'from'    => $from,
            'to'      => $to,
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
}
