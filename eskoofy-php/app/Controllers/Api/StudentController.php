<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;

class StudentController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;
        $search = $_GET['search'] ?? '';

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND (u.name LIKE ? OR s.admission_number LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE {$where}",
            $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT s.id, s.admission_number, u.name, u.email, s.roll_number, s.gender, c.name as class_name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             WHERE {$where}
             ORDER BY s.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->paginated([
            'data'         => $rows,
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'last_page'    => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function show(int $id): void
    {
        $student = $this->db->fetch(
            "SELECT s.*, u.name, u.email, c.name as class_name, sec.name as section_name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             WHERE s.id = ? LIMIT 1",
            [$id]
        );

        if (!$student) {
            $this->error('Student not found', 404);
        }

        $this->success($student, 'Student retrieved');
    }

    public function store(): void
    {
        $data = $this->validate([
            'name'       => 'required|max:255',
            'email'      => 'required|email',
            'class_id'   => 'required|numeric',
            'batch_id'   => 'numeric',
            'roll_number'=> 'max:20',
            'gender'     => 'in:male,female,other',
        ]);

        $userId = $this->db->insert('users', [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'role'       => 'student',
            'role_id'     => \App\Core\Auth::roleId('student'),
            'password'   => \App\Core\Auth::hashPassword('password'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $admissionNumber = 'STU-' . date('Ymd') . '-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT);

        $studentId = $this->db->insert('students', [
            'user_id'           => $userId,
            'admission_number'  => $admissionNumber,
            'class_id'          => $data['class_id'],
            'batch_id'          => $data['batch_id'] ?? 1,
            'roll_number'       => $data['roll_number'] ?? null,
            'gender'            => $data['gender'] ?? null,
            'status'            => 'active',
            'admission_date'    => date('Y-m-d'),
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        $this->success(['id' => $studentId, 'admission_number' => $admissionNumber], 'Student created', 201);
    }

    public function update(int $id): void
    {
        $student = $this->db->fetch("SELECT * FROM students WHERE id = ? LIMIT 1", [$id]);
        if (!$student) {
            $this->error('Student not found', 404);
        }

        $data = $this->validate([
            'class_id'   => 'numeric',
            'batch_id'   => 'numeric',
            'roll_number'=> 'max:20',
            'gender'     => 'in:male,female,other',
            'status'     => 'in:active,inactive,graduated,transferred',
        ]);

        $this->db->update('students', [
            'class_id'     => $data['class_id'] ?? $student['class_id'],
            'batch_id'     => $data['batch_id'] ?? $student['batch_id'],
            'roll_number'  => $data['roll_number'] ?? $student['roll_number'],
            'gender'       => $data['gender'] ?? $student['gender'],
            'status'       => $data['status'] ?? $student['status'],
            'updated_at'   => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->success(['id' => $id], 'Student updated');
    }

    public function destroy(int $id): void
    {
        $student = $this->db->fetch("SELECT * FROM students WHERE id = ? LIMIT 1", [$id]);
        if (!$student) {
            $this->error('Student not found', 404);
        }

        $this->db->delete('students', 'id = ?', [$id]);
        $this->db->delete('users', 'id = ?', [$student['user_id']]);

        $this->success(['id' => $id], 'Student deleted');
    }

    public function edit(int $id): void
    {
        $student = $this->db->fetch(
            "SELECT s.*, u.name, u.email, c.name as class_name, sec.name as section_name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             WHERE s.id = ? LIMIT 1",
            [$id]
        );

        if (!$student) {
            $this->error('Student not found', 404);
        }

        $this->success($student, 'Student retrieved successfully');
    }

    public function attendance(int $id): void
    {
        $student = $this->db->fetch("SELECT id FROM students WHERE id = ? LIMIT 1", [$id]);
        if (!$student) {
            $this->error('Student not found', 404);
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 31)));
        $offset = ($page - 1) * $perPage;

        $where = 'a.student_id = ?';
        $params = [$id];

        if (($_GET['month'] ?? '') !== '' && ($_GET['year'] ?? '') !== '') {
            $month = (int) $_GET['month'];
            $year = (int) $_GET['year'];
            $where .= ' AND a.date >= ? AND a.date <= ?';
            $params[] = sprintf('%04d-%02d-01', $year, $month);
            $params[] = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $month)));
        }
        if (($_GET['from'] ?? '') !== '') {
            $where .= ' AND a.date >= ?';
            $params[] = $_GET['from'];
        }
        if (($_GET['to'] ?? '') !== '') {
            $where .= ' AND a.date <= ?';
            $params[] = $_GET['to'];
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM attendances a WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT a.id, a.date, a.status, a.type, a.period, a.remarks,
                    sub.name as subject_name, sec.name as section_name
             FROM attendances a
             LEFT JOIN subjects sub ON a.subject_id = sub.id
             LEFT JOIN sections sec ON a.section_id = sec.id
             WHERE {$where}
             ORDER BY a.date DESC, a.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->paginated([
            'data'         => $rows,
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'last_page'    => max(1, (int) ceil($total / $perPage)),
        ], 'Student attendance retrieved successfully');
    }

    public function fees(int $id): void
    {
        $student = $this->db->fetch("SELECT id FROM students WHERE id = ? LIMIT 1", [$id]);
        if (!$student) {
            $this->error('Student not found', 404);
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $where = 'fp.student_id = ? AND fp.deleted_at IS NULL';
        $params = [$id];

        if (($_GET['status'] ?? '') !== '') {
            $where .= ' AND fp.status = ?';
            $params[] = $_GET['status'];
        }
        if (($_GET['month'] ?? '') !== '' && ($_GET['year'] ?? '') !== '') {
            $where .= ' AND fp.month = ? AND fp.year = ?';
            $params[] = (string) $_GET['month'];
            $params[] = (int) $_GET['year'];
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM fee_payments fp WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT fp.*, f.name as fee_name
             FROM fee_payments fp
             LEFT JOIN fees f ON fp.fee_id = f.id
             WHERE {$where}
             ORDER BY fp.payment_date DESC, fp.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $summary = $this->db->fetch(
            "SELECT COALESCE(SUM(fp.paid_amount), 0) as total_paid, COALESCE(SUM(fp.balance), 0) as total_balance
             FROM fee_payments fp
             WHERE fp.student_id = ? AND fp.deleted_at IS NULL AND fp.status NOT IN ('cancelled','refunded')",
            [$id]
        );

        $this->json([
            'success' => true,
            'message' => 'Student fees retrieved successfully',
            'data'    => $rows,
            'meta'    => [
                'pagination' => [
                    'current_page' => $page,
                    'per_page'     => $perPage,
                    'total'        => $total,
                    'last_page'    => max(1, (int) ceil($total / $perPage)),
                ],
                'summary' => [
                    'total_paid'    => (float) ($summary['total_paid'] ?? 0),
                    'total_balance' => (float) ($summary['total_balance'] ?? 0),
                ],
            ],
        ]);
    }

    public function results(int $id): void
    {
        $student = $this->db->fetch("SELECT id FROM students WHERE id = ? LIMIT 1", [$id]);
        if (!$student) {
            $this->error('Student not found', 404);
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $where = 'er.student_id = ? AND er.is_published = 1 AND e.is_published = 1 AND e.status = ? AND e.deleted_at IS NULL';
        $params = [$id, 'published'];

        if (($_GET['exam_id'] ?? '') !== '') {
            $where .= ' AND er.exam_id = ?';
            $params[] = (int) $_GET['exam_id'];
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt
             FROM exam_results er
             LEFT JOIN exams e ON er.exam_id = e.id
             WHERE {$where}",
            $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT er.id, er.exam_id, er.subject_id, er.obtained_marks, er.total_marks, er.grade,
                    er.grade_point, er.remarks, er.published_at,
                    e.name as exam_name, e.start_date, e.status,
                    sub.name as subject_name
             FROM exam_results er
             LEFT JOIN exams e ON er.exam_id = e.id
             LEFT JOIN subjects sub ON er.subject_id = sub.id
             WHERE {$where}
             ORDER BY er.published_at DESC, sub.name ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->paginated([
            'data'         => $rows,
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'last_page'    => max(1, (int) ceil($total / $perPage)),
        ], 'Student exam results retrieved successfully');
    }
}