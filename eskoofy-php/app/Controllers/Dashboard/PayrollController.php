<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class PayrollController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function salaryStructures(): void
    {
        Auth::requireAuth();
        $rows = $this->db->fetchAll(
            "SELECT ss.*, u.name as employee_name, u.designation
             FROM salary_structures ss
             LEFT JOIN teachers t ON ss.teacher_id = t.id
             LEFT JOIN users u ON t.user_id = u.id
             ORDER BY u.name ASC"
        );

        $this->view('dashboard.payroll.salary-structures', ['rows' => $rows]);
    }

    public function storeSalaryStructure(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'teacher_id'  => 'required|numeric',
            'basic'      => 'required|numeric',
            'house_rent' => 'numeric',
            'medical'    => 'numeric',
            'transport'  => 'numeric',
            'other'      => 'numeric',
            'deductions' => 'numeric',
            'effective_from' => 'required',
        ]);

        // Form posts allowance/deduction sub-fields; the schema stores them
        // as JSON in `allowances` / `deductions` longtext columns.
        $allowances = json_encode(array_filter([
            'house_rent' => (float) ($data['house_rent'] ?? 0),
            'medical'    => (float) ($data['medical'] ?? 0),
            'transport'  => (float) ($data['transport'] ?? 0),
            'other'      => (float) ($data['other'] ?? 0),
        ]));
        $deductions = json_encode(array_filter([
            'total'      => (float) ($data['deductions'] ?? 0),
        ]));

        $existing = $this->db->fetch(
            "SELECT id FROM salary_structures WHERE teacher_id = ? AND effective_from = ? LIMIT 1",
            [$data['teacher_id'], $data['effective_from']]
        );
        if ($existing) {
            $this->db->update('salary_structures', [
                'basic'      => $data['basic'],
                'allowances' => $allowances,
                'deductions' => $deductions,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('salary_structures', [
                'teacher_id'     => $data['teacher_id'],
                'basic'          => $data['basic'],
                'allowances'     => $allowances,
                'deductions'     => $deductions,
                'effective_from' => $data['effective_from'],
                'is_active'      => 1,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
        }

        Session::getInstance()->flash('success', 'Salary structure saved.');
        $this->redirect('/dashboard/payroll/salary-structures');
    }

    public function payslips(): void
    {
        Auth::requireAuth();
        $month = $_GET['month'] ?? date('Y-m');
        $rows = $this->db->fetchAll(
            "SELECT p.*, u.name as employee_name, u.designation
             FROM payslips p
             LEFT JOIN teachers t ON p.teacher_id = t.id
             LEFT JOIN users u ON t.user_id = u.id
             WHERE p.month = ?
             ORDER BY u.name ASC",
            [$month]
        );

        $this->view('dashboard.payroll.payslips', [
            'rows'  => $rows,
            'month' => $month,
        ]);
    }

    public function storePayslip(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'teacher_id' => 'required|numeric',
            'month'      => 'required|max:7',
            'basic'      => 'required|numeric',
            'total_allowances' => 'numeric',
            'total_deductions' => 'numeric',
            'status'     => 'required|max:20',
        ]);

        // Form posts month as "YYYY-MM"; the schema splits it into month+year.
        [$year, $month] = explode('-', $data['month']) + [null, null];

        $existing = $this->db->fetch(
            "SELECT id FROM payslips WHERE teacher_id = ? AND month = ? AND year = ? LIMIT 1",
            [$data['teacher_id'], (int) $month, (int) $year]
        );

        $total = (float) $data['basic']
            + (float) ($data['total_allowances'] ?? 0)
            - (float) ($data['total_deductions'] ?? 0);

        if ($existing) {
            $this->db->update('payslips', [
                'basic'             => $data['basic'],
                'total_allowances'  => $data['total_allowances'] ?? 0,
                'total_deductions'  => $data['total_deductions'] ?? 0,
                'net_salary'        => $total,
                'status'            => $data['status'],
                'updated_at'        => date('Y-m-d H:i:s'),
            ], 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('payslips', [
                'teacher_id'        => $data['teacher_id'],
                'month'             => (int) $month,
                'year'              => (int) $year,
                'basic'             => $data['basic'],
                'total_allowances'  => $data['total_allowances'] ?? 0,
                'total_deductions'  => $data['total_deductions'] ?? 0,
                'net_salary'        => $total,
                'status'            => $data['status'],
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
        }

        Session::getInstance()->flash('success', 'Payslip saved.');
        $this->redirect('/dashboard/payroll/payslips');
    }

    public function leaveRequests(): void
    {
        Auth::requireAuth();
        $rows = $this->db->fetchAll(
            "SELECT lr.*, u.name as employee_name, u.designation
             FROM leave_requests lr
             LEFT JOIN teachers t ON lr.teacher_id = t.id
             LEFT JOIN users u ON t.user_id = u.id
             ORDER BY lr.created_at DESC"
        );

        $this->view('dashboard.payroll.leave-requests', ['rows' => $rows]);
    }

    public function updateLeaveRequest(int $id): void
    {
        Auth::requireAuth();
        $leave = $this->db->fetch("SELECT * FROM leave_requests WHERE id = ? LIMIT 1", [$id]);
        if (!$leave) {
            Session::getInstance()->flash('error', 'Leave request not found.');
            $this->redirect('/dashboard/payroll/leave-requests');
            return;
        }

        $data = $this->validate([
            'status'     => 'required|max:20',
            'admin_note' => 'max:500',
        ]);

        $this->db->update('leave_requests', [
            'status'     => $data['status'],
            'admin_note' => $data['admin_note'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Leave request updated.');
        $this->redirect('/dashboard/payroll/leave-requests');
    }

    public function staffAttendance(): void
    {
        Auth::requireAuth();
        $date = $_GET['date'] ?? date('Y-m-d');
        $rows = $this->db->fetchAll(
            "SELECT sa.*, u.name as employee_name, u.designation
             FROM staff_attendances sa
             LEFT JOIN teachers t ON sa.teacher_id = t.id
             LEFT JOIN users u ON t.user_id = u.id
             WHERE sa.date = ?
             ORDER BY u.name ASC",
            [$date]
        );

        $this->view('dashboard.payroll.staff-attendance', [
            'rows' => $rows,
            'date' => $date,
        ]);
    }

    public function storeStaffAttendance(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'date'   => 'required',
            'users'  => 'required',
        ]);

        $users = json_decode($data['users'], true);
        if (!is_array($users)) {
            Session::getInstance()->flash('error', 'Invalid attendance data.');
            $this->back();
            return;
        }

        foreach ($users as $entry) {
            if (empty($entry['teacher_id'])) continue;
            $teacherId = (int) $entry['teacher_id'];
            $status = $entry['status'] ?? 'absent';
            $checkIn = $entry['check_in'] ?? null;
            $checkOut = $entry['check_out'] ?? null;
            $note = $entry['note'] ?? null;

            $existing = $this->db->fetch(
                "SELECT id FROM staff_attendances WHERE teacher_id = ? AND date = ? LIMIT 1",
                [$teacherId, $data['date']]
            );

            if ($existing) {
                $this->db->update('staff_attendances', [
                    'status'    => $status,
                    'check_in_at'  => $checkIn,
                    'check_out_at' => $checkOut,
                    'note'      => $note,
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$existing['id']]);
            } else {
                $this->db->insert('staff_attendances', [
                    'teacher_id'    => $teacherId,
                    'date'       => $data['date'],
                    'status'     => $status,
                    'check_in_at'   => $checkIn,
                    'check_out_at'  => $checkOut,
                    'note'       => $note,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        Session::getInstance()->flash('success', 'Staff attendance saved.');
        $this->redirect('/dashboard/payroll/staff-attendance');
    }

    public function leaveTypes(): void
    {
        Auth::requireAuth();
        $rows = $this->db->fetchAll(
            "SELECT * FROM leave_types ORDER BY name ASC"
        );

        $this->view('dashboard.payroll.leave-types', ['leaveTypes' => $rows]);
    }

    public function storeLeaveType(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'         => 'required|max:100',
            'days_allowed' => 'required|numeric',
            'is_paid'      => 'numeric',
        ]);

        $this->db->insert('leave_types', [
            'name'         => $data['name'],
            'days_allowed' => $data['days_allowed'],
            'is_paid'      => isset($data['is_paid']) ? (int) $data['is_paid'] : 0,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Leave type created.');
        $this->redirect('/dashboard/leave-types');
    }

    public function updateLeaveType(int $id): void
    {
        Auth::requireAuth();
        $lt = $this->db->fetch("SELECT * FROM leave_types WHERE id = ? LIMIT 1", [$id]);
        if (!$lt) {
            Session::getInstance()->flash('error', 'Leave type not found.');
            $this->redirect('/dashboard/leave-types');
            return;
        }

        $data = $this->validate([
            'name'         => 'required|max:100',
            'days_allowed' => 'required|numeric',
            'is_paid'      => 'numeric',
        ]);

        $this->db->update('leave_types', [
            'name'         => $data['name'],
            'days_allowed' => $data['days_allowed'],
            'is_paid'      => isset($data['is_paid']) ? (int) $data['is_paid'] : 0,
            'updated_at'   => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Leave type updated.');
        $this->redirect('/dashboard/leave-types');
    }

    public function destroyLeaveType(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('leave_types', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Leave type deleted.');
        $this->redirect('/dashboard/leave-types');
    }
}
