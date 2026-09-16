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

    // ── Teacher portal (parity with app Api\TeacherController) ──────────

    public function getTeacherClasses(): void
    {
        $user = \App\Core\Auth::user();
        if (! $user) {
            $this->error('Unauthenticated.', 401);
        }
        $teacher = $this->db->fetch(
            "SELECT id FROM teachers WHERE user_id = ? LIMIT 1",
            [(int) $user->getKey()]
        );
        if (! $teacher) {
            $this->error('No teacher record linked to this account.', 404);
        }
        $teacherId = (int) $teacher['id'];

        // Classes where the teacher is the class teacher, plus classes from
        // the class_teacher join table.
        $rows = $this->db->fetchAll(
            "SELECT DISTINCT c.id, c.name, c.code, c.shift
             FROM school_classes c
             LEFT JOIN class_teacher ct ON ct.class_id = c.id AND ct.teacher_id = ?
             WHERE c.is_active = 1 AND c.deleted_at IS NULL
               AND (c.class_teacher_id = ? OR ct.id IS NOT NULL)
             ORDER BY c.name",
            [$teacherId, $teacherId]
        );
        $this->success($rows, 'Teacher classes retrieved');
    }

    public function getClassStudents(int $classId): void
    {
        $this->assertTeacherHasClass($classId);
        $rows = $this->db->fetchAll(
            "SELECT s.id, u.name, s.roll_number
             FROM students s
             JOIN users u ON u.id = s.user_id
             WHERE s.class_id = ? AND s.deleted_at IS NULL
             ORDER BY s.roll_number ASC",
            [$classId]
        );
        $this->success($rows, 'Class students retrieved');
    }

    public function getClassGrades(int $classId): void
    {
        $this->assertTeacherHasClass($classId);
        $rows = $this->db->fetchAll(
            "SELECT g.id, g.student_id, u.name AS student_name, g.subject_id, sub.name AS subject_name,
                    g.exam_id, e.name AS exam_name, g.marks_obtained, g.total_marks, g.grade, g.remarks
             FROM grades g
             LEFT JOIN students s ON s.id = g.student_id
             LEFT JOIN users u ON u.id = s.user_id
             LEFT JOIN subjects sub ON sub.id = g.subject_id
             LEFT JOIN exams e ON e.id = g.exam_id
             WHERE g.class_id = ?
             ORDER BY g.exam_id, u.name",
            [$classId]
        );
        $this->success($rows, 'Class grades retrieved');
    }

    private function assertTeacherHasClass(int $classId): void
    {
        $user = \App\Core\Auth::user();
        if (! $user) {
            $this->error('Unauthenticated.', 401);
        }
        $teacher = $this->db->fetch(
            "SELECT id FROM teachers WHERE user_id = ? LIMIT 1",
            [(int) $user->getKey()]
        );
        if (! $teacher) {
            $this->error('No teacher record linked to this account.', 404);
        }
        $teacherId = (int) $teacher['id'];
        $row = $this->db->fetch(
            "SELECT c.id FROM school_classes c
             LEFT JOIN class_teacher ct ON ct.class_id = c.id AND ct.teacher_id = ?
             WHERE c.id = ? AND c.is_active = 1 AND c.deleted_at IS NULL
               AND (c.class_teacher_id = ? OR ct.id IS NOT NULL) LIMIT 1",
            [$teacherId, $classId, $teacherId]
        );
        if (! $row) {
            $this->error('This class is not assigned to you.', 403);
        }
    }
}