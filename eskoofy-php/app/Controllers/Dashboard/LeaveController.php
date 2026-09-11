<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class LeaveController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $status = $_GET['status'] ?? '';
        $role = Auth::role() ?? '';

        $where = '1=1';
        $params = [];
        if (in_array($role, ['admin', 'staff'], true) === false) {
            $teacher = $this->db->fetch("SELECT id FROM teachers WHERE user_id = ? LIMIT 1", [Auth::id()]);
            if (!$teacher) {
                $this->view('dashboard.leaves.index', ['rows' => [], 'status' => $status]);
                return;
            }
            $where .= ' AND lr.teacher_id = ?';
            $params[] = $teacher['id'];
        }
        if ($status !== '') {
            $where .= ' AND lr.status = ?';
            $params[] = $status;
        }

        $rows = $this->db->fetchAll(
            "SELECT lr.*, u.name as teacher_name, lt.name_en as leave_type, lt.name_bn as leave_type_bn,
                    u2.name as approver_name
             FROM leave_requests lr
             LEFT JOIN teachers t ON lr.teacher_id = t.id
             LEFT JOIN users u ON t.user_id = u.id
             LEFT JOIN leave_types lt ON lr.leave_type_id = lt.id
             LEFT JOIN users u2 ON lr.approver_id = u2.id
             WHERE {$where}
             ORDER BY lr.created_at DESC",
            $params
        );

        foreach ($rows as &$row) {
            $row['days'] = $this->inclusiveDays($row['from_date'] ?? '', $row['to_date'] ?? '');
        }
        unset($row);

        $this->view('dashboard.leaves.index', [
            'rows'   => $rows,
            'status' => $status,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $role = Auth::role() ?? '';
        $teachers = [];
        if (in_array($role, ['admin', 'staff'], true)) {
            $teachers = $this->db->fetchAll(
                "SELECT t.id, u.name FROM teachers t LEFT JOIN users u ON t.user_id = u.id ORDER BY u.name ASC LIMIT 200"
            );
        }
        $teacher = $this->db->fetch(
            "SELECT t.id FROM teachers t WHERE t.user_id = ? LIMIT 1", [Auth::id()]
        );
        $types = $this->db->fetchAll(
            "SELECT * FROM leave_types WHERE is_active = 1 ORDER BY name_en ASC"
        );

        $this->view('dashboard.leaves.create', [
            'teachers' => $teachers,
            'teacher'  => $teacher,
            'types'    => $types,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $role = Auth::role() ?? '';
        $data = $this->validate([
            'teacher_id'    => 'required|numeric',
            'leave_type_id' => 'required|numeric',
            'from_date'     => 'required',
            'to_date'       => 'required',
            'reason'        => 'required|max:1000',
        ]);

        if (!in_array($role, ['admin', 'staff'], true)) {
            $teacher = $this->db->fetch("SELECT id FROM teachers WHERE user_id = ? LIMIT 1", [Auth::id()]);
            if (!$teacher || (int) $teacher['id'] !== (int) $data['teacher_id']) {
                http_response_code(403);
                echo 'Forbidden';
                return;
            }
        }

        $this->db->insert('leave_requests', [
            'teacher_id'    => (int) $data['teacher_id'],
            'leave_type_id' => (int) $data['leave_type_id'],
            'from_date'     => $data['from_date'],
            'to_date'       => $data['to_date'],
            'reason'        => $data['reason'],
            'status'        => 'pending',
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Leave request submitted.');
        $this->redirect('/dashboard/leaves');
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $leave = $this->loadLeave($id);
        if (!$leave) {
            Session::getInstance()->flash('error', 'Leave request not found.');
            $this->redirect('/dashboard/leaves');
            return;
        }
        $this->view('dashboard.leaves.show', ['leave' => $leave]);
    }

    public function approve(int $id): void
    {
        Auth::requireAuth();
        $note = trim((string) ($_POST['approver_note'] ?? ''));
        $this->applyDecision($id, 'approved', $note);
        Session::getInstance()->flash('success', 'Leave approved.');
        $this->redirect('/dashboard/leaves/' . $id);
    }

    public function reject(int $id): void
    {
        Auth::requireAuth();
        $note = trim((string) ($_POST['approver_note'] ?? ''));
        $this->applyDecision($id, 'rejected', $note);
        Session::getInstance()->flash('success', 'Leave rejected.');
        $this->redirect('/dashboard/leaves/' . $id);
    }

    public function applyDecision(int $id, string $status, string $note): void
    {
        $this->db->update('leave_requests', [
            'status'       => $status,
            'approver_id'  => Auth::id(),
            'approver_note'=> $note !== '' ? $note : null,
            'decided_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    public function cancel(int $id): void
    {
        Auth::requireAuth();
        $leave = $this->db->fetch("SELECT * FROM leave_requests WHERE id = ? LIMIT 1", [$id]);
        if (!$leave) {
            Session::getInstance()->flash('error', 'Leave request not found.');
            $this->redirect('/dashboard/leaves');
            return;
        }

        $this->db->update('leave_requests', [
            'status'     => 'cancelled',
            'decided_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Leave cancelled.');
        $this->redirect('/dashboard/leaves/' . $id);
    }

    private function loadLeave(int $id): ?array
    {
        $leave = $this->db->fetch(
            "SELECT lr.*, u.name as teacher_name, lt.name_en as leave_type, lt.name_bn as leave_type_bn,
                    u2.name as approver_name
             FROM leave_requests lr
             LEFT JOIN teachers t ON lr.teacher_id = t.id
             LEFT JOIN users u ON t.user_id = u.id
             LEFT JOIN leave_types lt ON lr.leave_type_id = lt.id
             LEFT JOIN users u2 ON lr.approver_id = u2.id
             WHERE lr.id = ? LIMIT 1",
            [$id]
        );
        if (!$leave) {
            return null;
        }
        $leave['days'] = $this->inclusiveDays($leave['from_date'] ?? '', $leave['to_date'] ?? '');
        return $leave;
    }

    private function inclusiveDays(string $from, string $to): int
    {
        if ($from === '' || $to === '') {
            return 0;
        }
        $f = new \DateTime($from);
        $t = new \DateTime($to);
        return (int) $f->diff($t)->format('%a') + 1;
    }
}