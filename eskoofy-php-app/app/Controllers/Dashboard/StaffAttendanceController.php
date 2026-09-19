<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;
use App\Core\Support\Carbon;
use App\Core\Support\Collection;

class StaffAttendanceController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $date = new Carbon($_GET['date'] ?? date('Y-m-d'));

        $records = $this->db->fetchAll(
            "SELECT sa.*, t.employee_id, u.name, u.email
             FROM staff_attendances sa
             LEFT JOIN teachers t ON sa.teacher_id = t.id
             LEFT JOIN users u ON t.user_id = u.id
             WHERE sa.date = ?
             ORDER BY u.name ASC",
            [$date->toDateString()]
        );

        $existing = [];
        foreach ($records as $r) {
            $existing[$r['teacher_id']] = \App\Models\StaffAttendance::newFromRow($r);
        }

        $teachers = new Collection(\App\Models\Teacher::hydrate($this->db->fetchAll(
            "SELECT t.id, t.user_id, u.name, t.employee_id
             FROM teachers t
             LEFT JOIN users u ON t.user_id = u.id
             WHERE t.status = 'active'
             ORDER BY u.name ASC"
        )));

        $this->view('dashboard.staff_attendance.index', [
            'records'  => $records,
            'teachers' => $teachers,
            'existing' => $existing,
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
        $month = new Carbon(($_GET['month'] ?? date('Y-m')) . '-01');
        $from = $month->copy()->startOfMonth()->toDateString();
        $to = $month->copy()->endOfMonth()->toDateString();

        $period = new Collection();
        $day = $month->copy()->startOfMonth();
        $last = $month->copy()->endOfMonth();
        while ($day->lte($last)) {
            $period->push($day->copy());
            $day->addDay();
        }

        $teachers = new Collection(\App\Models\Teacher::hydrate($this->db->fetchAll(
            "SELECT t.id, t.user_id, u.name, t.employee_id
             FROM teachers t
             LEFT JOIN users u ON t.user_id = u.id
             WHERE t.status = 'active'
             ORDER BY u.name ASC"
        )));

        $attRows = $this->db->fetchAll(
            "SELECT sa.*, t.employee_id, u.name
             FROM staff_attendances sa
             LEFT JOIN teachers t ON sa.teacher_id = t.id
             LEFT JOIN users u ON t.user_id = u.id
             WHERE sa.date BETWEEN ? AND ?
             ORDER BY sa.date ASC",
            [$from, $to]
        );

        $records = [];
        foreach ($attRows as $r) {
            $records[$r['teacher_id']][] = \App\Models\StaffAttendance::newFromRow($r);
        }
        foreach ($records as $tid => $list) {
            $records[$tid] = new Collection($list);
        }

        $this->view('dashboard.staff_attendance.report', [
            'teachers' => $teachers,
            'records'  => $records,
            'month'    => $month,
            'period'   => $period,
        ]);
    }
}
