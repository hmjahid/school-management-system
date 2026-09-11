<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class PayrollController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function salaryStructures(): void
    {
        Auth::requireAuth();
        $rows = $this->db->fetchAll(
            "SELECT ss.*, u.name as employee_name, u.role
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
        $this->redirect('/dashboard/salary-structures');
    }

    public function payslips(): void
    {
        Auth::requireAuth();
        $month = (int) ($_GET['month'] ?? date('n'));
        $year = (int) ($_GET['year'] ?? date('Y'));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        if ($year < 2020 || $year > 2099) {
            $year = (int) date('Y');
        }
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM payslips p WHERE p.month = ? AND p.year = ?",
            [$month, $year]
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT p.*, u.name as employee_name, u.role
             FROM payslips p
             LEFT JOIN teachers t ON p.teacher_id = t.id
             LEFT JOIN users u ON t.user_id = u.id
             WHERE p.month = ? AND p.year = ?
             ORDER BY u.name ASC
             LIMIT {$perPage} OFFSET {$offset}",
            [$month, $year]
        );

        $this->view('dashboard.payroll.payslips', [
            'rows'  => $this->paginateRows($rows, $total, $perPage, $page, \App\Models\Payslip::class),
            'month' => $month,
            'year'  => $year,
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
        $this->redirect('/dashboard/payslips');
    }

    public function generate(): void
    {
        Auth::requireAuth();
        $month = (int) ($_GET['month'] ?? date('n'));
        $year = (int) ($_GET['year'] ?? date('Y'));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        if ($year < 2020 || $year > 2099) {
            $year = (int) date('Y');
        }

        $structures = $this->db->fetchAll(
            "SELECT ss.*, t.user_id, u.name as employee_name, u.role
             FROM salary_structures ss
             LEFT JOIN teachers t ON ss.teacher_id = t.id
             LEFT JOIN users u ON t.user_id = u.id
             WHERE ss.is_active = 1
             ORDER BY u.name ASC"
        );

        $preview = [];
        foreach ($structures as $s) {
            $allowances = json_decode((string) ($s['allowances'] ?? '[]'), true) ?: [];
            $deductions = json_decode((string) ($s['deductions'] ?? '[]'), true) ?: [];
            $totalAllowances = array_sum(array_map('floatval', $allowances));
            $totalDeductions = array_sum(array_map('floatval', $deductions));

            $leaveDays = 0;
            $rows = $this->db->fetchAll(
                "SELECT from_date, to_date FROM leave_requests
                 WHERE teacher_id = ? AND status = 'approved'
                   AND ((YEAR(from_date) = ? AND MONTH(from_date) = ?)
                     OR (YEAR(to_date) = ? AND MONTH(to_date) = ?))",
                [$s['teacher_id'], $year, $month, $year, $month]
            );
            foreach ($rows as $lr) {
                $fromDate = $lr['from_date'] ?? null;
                $toDate = $lr['to_date'] ?? null;
                if ($fromDate === null || $toDate === null) {
                    continue;
                }
                $f = new \DateTime($fromDate);
                $t = new \DateTime($toDate);
                $leaveDays += (int) $f->diff($t)->format('%a') + 1;
            }

            $basic = (float) ($s['basic'] ?? 0);
            $dailyRate = $basic / 30;
            $leaveDeduction = $leaveDays * $dailyRate;
            $gross = $basic + $totalAllowances;
            $net = $gross - ($totalDeductions + $leaveDeduction);

            $preview[] = [
                'teacher'     => new \App\Models\Teacher([
                    'id'      => (int) $s['teacher_id'],
                    'user_id' => (int) ($s['user_id'] ?? 0),
                ]),
                'basic'       => $basic,
                'allowances'  => $totalAllowances,
                'leave_days'  => $leaveDays,
                'deductions'  => $totalDeductions,
                'net'         => round($net, 2),
            ];
        }

        $this->view('dashboard.payroll.generate', [
            'preview' => new \App\Core\Support\Collection($preview),
            'month'   => $month,
            'year'    => $year,
        ]);
    }

    public function generateStore(): void
    {
        Auth::requireAuth();
        $month = (int) ($_POST['month'] ?? 0);
        $year = (int) ($_POST['year'] ?? 0);
        $teacherIds = $_POST['teacher_ids'] ?? [];
        if ($month < 1 || $month > 12 || $year < 2020 || $year > 2099) {
            Session::getInstance()->flash('error', 'Invalid month/year.');
            $this->redirect('/dashboard/payroll/generate');
            return;
        }

        $count = $this->buildPayslips(array_map('intval', (array) $teacherIds), $month, $year);

        Session::getInstance()->flash('success', "Generated {$count} payslips.");
        $this->redirect('/dashboard/payslips?month=' . $month . '&year=' . $year);
    }

    public function buildPayslips(array $teacherIds, int $month, int $year): int
    {
        $count = 0;
        foreach ($teacherIds as $teacherId) {
            $s = $this->db->fetch(
                "SELECT * FROM salary_structures WHERE teacher_id = ? AND is_active = 1 LIMIT 1",
                [$teacherId]
            );
            if (!$s) {
                continue;
            }
            $exists = $this->db->fetch(
                "SELECT id FROM payslips WHERE teacher_id = ? AND month = ? AND year = ? LIMIT 1",
                [$teacherId, $month, $year]
            );
            if ($exists) {
                continue;
            }

            $allowances = json_decode((string) $s['allowances'], true) ?: [];
            $deductions = json_decode((string) $s['deductions'], true) ?: [];
            $totalAllowances = array_sum(array_map('floatval', $allowances));
            $totalDeductions = array_sum(array_map('floatval', $deductions));

            $leaveDays = 0;
            $rows = $this->db->fetchAll(
                "SELECT from_date, to_date FROM leave_requests
                 WHERE teacher_id = ? AND status = 'approved'
                   AND ((YEAR(from_date) = ? AND MONTH(from_date) = ?)
                     OR (YEAR(to_date) = ? AND MONTH(to_date) = ?))",
                [$teacherId, $year, $month, $year, $month]
            );
            foreach ($rows as $lr) {
                $f = new \DateTime($lr['from_date']);
                $t = new \DateTime($lr['to_date']);
                $leaveDays += (int) $f->diff($t)->format('%a') + 1;
            }

            $dailyRate = (float) $s['basic'] / 30;
            $leaveDeduction = $leaveDays * $dailyRate;
            $gross = (float) $s['basic'] + $totalAllowances;
            $totalDeductionsWithLeave = $totalDeductions + $leaveDeduction;
            $net = round($gross - $totalDeductionsWithLeave, 2);

            $this->db->insert('payslips', [
                'teacher_id'        => $teacherId,
                'month'             => $month,
                'year'              => $year,
                'basic'             => (float) $s['basic'],
                'total_allowances'  => round($totalAllowances, 2),
                'total_deductions'  => round($totalDeductionsWithLeave, 2),
                'net_salary'        => $net,
                'details'           => json_encode([
                    'allowances'     => $allowances,
                    'deductions'     => $deductions,
                    'leave_days'     => $leaveDays,
                    'leave_deduction'=> round($leaveDeduction, 2),
                ]),
                'status'            => 'draft',
                'generated_at'      => date('Y-m-d H:i:s'),
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
            $count++;
        }

        return $count;
    }

    public function showPayslip(int $id): void
    {
        Auth::requireAuth();
        $payslip = $this->db->fetch(
            "SELECT p.*, u.name as employee_name, u.role
             FROM payslips p
             LEFT JOIN teachers t ON p.teacher_id = t.id
             LEFT JOIN users u ON t.user_id = u.id
             WHERE p.id = ? LIMIT 1",
            [$id]
        );
        if (!$payslip) {
            Session::getInstance()->flash('error', 'Payslip not found.');
            $this->redirect('/dashboard/payslips');
            return;
        }
        $payslip['details'] = isset($payslip['details']) && $payslip['details'] !== '' ? json_decode((string) $payslip['details'], true) : [];

        $this->view('dashboard.payroll.payslip_show', ['payslip' => \App\Models\Payslip::newFromRow($payslip)]);
    }

    public function markPaid(int $id): void
    {
        Auth::requireAuth();
        $payslip = $this->db->fetch("SELECT * FROM payslips WHERE id = ? LIMIT 1", [$id]);
        if (!$payslip) {
            Session::getInstance()->flash('error', 'Payslip not found.');
            $this->redirect('/dashboard/payslips');
            return;
        }

        $this->db->update('payslips', [
            'status'    => 'paid',
            'paid_at'   => date('Y-m-d H:i:s'),
            'updated_at'=> date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Payslip marked as paid.');
        $this->redirect('/dashboard/payslips/' . $id);
    }

    public function leaveRequests(): void
    {
        Auth::requireAuth();
        $rows = $this->db->fetchAll(
            "SELECT lr.*, u.name as employee_name, u.role
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
            'status'       => $data['status'],
            'approver_note'=> $data['admin_note'] ?? null,
            'approver_id'  => Auth::id(),
            'decided_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Leave request updated.');
        $this->redirect('/dashboard/payroll/leave-requests');
    }

    public function staffAttendance(): void
    {
        Auth::requireAuth();
        $date = $_GET['date'] ?? date('Y-m-d');
        $rows = $this->db->fetchAll(
            "SELECT sa.*, u.name as employee_name, u.role
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
            "SELECT * FROM leave_types ORDER BY name_en ASC"
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
            'name_en'      => $data['name'],
            'days_per_year'=> (int) $data['days_allowed'],
            'is_paid'      => isset($data['is_paid']) ? (int) $data['is_paid'] : 1,
            'is_active'    => 1,
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
            'name_en'      => $data['name'],
            'days_per_year'=> (int) $data['days_allowed'],
            'is_paid'      => isset($data['is_paid']) ? (int) $data['is_paid'] : 1,
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
