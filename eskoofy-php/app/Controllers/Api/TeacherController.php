<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;

class TeacherController extends Controller
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

        $total = $this->db->count('teachers');
        $rows = $this->db->fetchAll(
            "SELECT t.id, u.name, u.email, u.phone, t.designation, t.qualification, t.hire_date
             FROM teachers t
             LEFT JOIN users u ON t.user_id = u.id
             ORDER BY u.name ASC
             LIMIT {$perPage} OFFSET {$offset}"
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
        $teacher = $this->db->fetch(
            "SELECT t.*, u.name, u.email, u.phone
             FROM teachers t
             LEFT JOIN users u ON t.user_id = u.id
             WHERE t.id = ? LIMIT 1",
            [$id]
        );

        if (!$teacher) {
            $this->error('Teacher not found', 404);
        }

        $this->success($teacher, 'Teacher retrieved');
    }

    public function store(): void
    {
        $data = $this->validate([
            'name'      => 'required|max:255',
            'email'     => 'required|email',
            'designation' => 'max:255',
            'qualification' => 'max:255',
            'phone'     => 'max:20',
        ]);

        $userId = $this->db->insert('users', [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'role'       => 'teacher',
            'role_id'     => \App\Core\Auth::roleId('teacher'),
            'password'   => \App\Core\Auth::hashPassword('password'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $teacherId = $this->db->insert('teachers', [
            'user_id'       => $userId,
            'designation'   => $data['designation'] ?? null,
            'qualification' => $data['qualification'] ?? null,
            'hire_date'     => date('Y-m-d'),
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->success(['id' => $teacherId], 'Teacher created', 201);
    }

    public function update(int $id): void
    {
        $teacher = $this->db->fetch("SELECT * FROM teachers WHERE id = ? LIMIT 1", [$id]);
        if (!$teacher) {
            $this->error('Teacher not found', 404);
        }

        $data = $this->validate([
            'designation' => 'max:255',
            'qualification' => 'max:255',
            'status'     => 'max:20',
        ]);

        $this->db->update('teachers', [
            'designation'   => $data['designation'] ?? $teacher['designation'],
            'qualification' => $data['qualification'] ?? $teacher['qualification'],
            'status'        => $data['status'] ?? $teacher['status'],
            'updated_at'    => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->success(['id' => $id], 'Teacher updated');
    }

    public function destroy(int $id): void
    {
        $teacher = $this->db->fetch("SELECT * FROM teachers WHERE id = ? LIMIT 1", [$id]);
        if (!$teacher) {
            $this->error('Teacher not found', 404);
        }

        $this->db->delete('teachers', 'id = ?', [$id]);
        $this->db->delete('users', 'id = ?', [$teacher['user_id']]);

        $this->success(['id' => $id], 'Teacher deleted');
    }
}