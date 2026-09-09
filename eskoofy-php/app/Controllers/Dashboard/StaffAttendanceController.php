<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class StaffAttendanceController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $date = $_GET['date'] ?? date('Y-m-d');

        $records = $this->db->fetchAll(
            "SELECT sa.*, t.employee_id, u.name, u.email
             FROM staff_attendances sa
             LEFT JOIN teachers t ON sa.teacher_id = t.id
             LEFT JOIN users u ON t.user_id = u.id
             WHERE sa.date = ?
             ORDER BY u.name ASC",
            [$date]
        );

        $teachers = $this->db->fetchAll(
            "SELECT t.id, u.name, t.employee_id
             FROM teachers t
             LEFT JOIN users u ON t.user_id = u.id
             WHERE t.status = 'active'
             ORDER BY u.name ASC"
        );

        $this->view('dashboard.staff_attendance.index', [
            'records'  => $records,
            'teachers' => $teachers,
            'date'     => $date,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'date' => 'required',
        ]);

        $statuses = $_POST['status'] ?? [];
        $checkIn = $_POST['check_in'] ?? [];
        $checkOut = $_POST['check_out'] ?? [];
        $notes = $_POST['notes'] ?? [];

        $count = 0;
        foreach ($statuses as $teacherId => $status) {
            if (!in_array($status, ['present', 'absent', 'leave', 'half_day', 'late'], true)) continue;

            $existing = $this->db->fetch(
                "SELECT id FROM staff_attendances WHERE teacher_id = ? AND date = ? LIMIT 1",
                [$teacherId, $data['date']]
            );

            $payload = [
                'teacher_id' => $teacherId,
                'date'       => $data['date'],
                'status'     => $status,
                'check_in'   => $checkIn[$teacherId] ?? null,
                'check_out'  => $checkOut[$teacherId] ?? null,
                'notes'      => $notes[$teacherId] ?? null,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $this->db->update('staff_attendances', $payload, 'id = ?', [$existing['id']]);
            } else {
                $payload['created_at'] = date('Y-m-d H:i:s');
                $this->db->insert('staff_attendances', $payload);
            }
            $count++;
        }

        Session::getInstance()->flash('success', "Saved {$count} staff attendance records.");
        $this->redirect('/dashboard/staff-attendance?date=' . urlencode($data['date']));
    }

    public function report(): void
    {
        Auth::requireAuth();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');

        $rows = $this->db->fetchAll(
            "SELECT t.id, u.name, t.employee_id,
                    COUNT(*) as total_days,
                    SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                    SUM(CASE WHEN sa.status = 'leave' THEN 1 ELSE 0 END) as leave_days
             FROM teachers t
             LEFT JOIN users u ON t.user_id = u.id
             LEFT JOIN staff_attendances sa ON sa.teacher_id = t.id AND sa.date BETWEEN ? AND ?
             WHERE t.status = 'active'
             GROUP BY t.id
             ORDER BY u.name ASC",
            [$from, $to]
        );

        $this->view('dashboard.staff_attendance.report', [
            'rows' => $rows,
            'from' => $from,
            'to'   => $to,
        ]);
    }
}
