<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

class StudentController extends Controller
{
    private Database $db;

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
}