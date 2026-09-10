<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class TeacherController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];

        if ($search !== '') {
            $where .= " AND (u.name LIKE ? OR u.email LIKE ? OR t.employee_id LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($status !== '') {
            $where .= ' AND t.status = ?';
            $params[] = $status;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM teachers t LEFT JOIN users u ON t.user_id = u.id WHERE {$where}",
            $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT t.*, u.name, u.email, u.photo, u.phone as user_phone
             FROM teachers t
             LEFT JOIN users u ON t.user_id = u.id
             WHERE {$where}
             ORDER BY t.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('dashboard.teachers.index', [
            'rows'     => $rows,
            'teachers' => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'search'   => $search,
            'status'   => $status,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $subjects = $this->db->fetchAll("SELECT id, name FROM subjects ORDER BY name ASC");
        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");

        $this->view('dashboard.teachers.create', [
            'subjects' => $subjects,
            'classes'  => $classes,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'          => 'required|max:255',
            'email'         => 'required|email',
            'phone'         => 'max:20',
            'employee_id'   => 'max:50',
            'qualification' => 'max:255',
            'experience'    => 'max:255',
            'joining_date'  => 'date',
            'salary'        => 'numeric',
            'subject_ids'   => 'max:500',
        ]);

        $exists = $this->db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$data['email']]);
        if ($exists) {
            Session::getInstance()->flash('error', 'A user with this email already exists.');
            $this->back();
            return;
        }

        $userId = $this->db->insert('users', [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'role'       => 'teacher',
            'password'   => Auth::hashPassword('password'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $teacherId = $this->db->insert('teachers', [
            'user_id'        => $userId,
            'employee_id'    => $data['employee_id'] ?? null,
            'qualification'  => $data['qualification'] ?? null,
            'experience'     => $data['experience'] ?? null,
            'joining_date'   => $data['joining_date'] ?? date('Y-m-d'),
            'salary'         => $data['salary'] ?? null,
            'status'         => 'active',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        if (!empty($data['subject_ids'])) {
            $subjectIds = array_map('intval', explode(',', $data['subject_ids']));
            foreach ($subjectIds as $sid) {
                if ($sid > 0) {
                    $this->db->insert('class_subject_teacher', [
                        'teacher_id' => $teacherId,
                        'subject_id' => $sid,
                    ]);
                }
            }
        }

        Session::getInstance()->flash('success', 'Teacher created successfully.');
        $this->redirect('/dashboard/teachers');
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $teacher = $this->db->fetch(
            "SELECT t.*, u.name, u.email, u.photo, u.phone as user_phone, u.address
             FROM teachers t
             LEFT JOIN users u ON t.user_id = u.id
             WHERE t.id = ? LIMIT 1",
            [$id]
        );

        if (!$teacher) {
            Session::getInstance()->flash('error', 'Teacher not found.');
            $this->redirect('/dashboard/teachers');
            return;
        }

        $subjects = $this->db->fetchAll(
            "SELECT sub.* FROM subjects sub
             INNER JOIN class_subject_teacher ts ON sub.id = ts.subject_id
             WHERE ts.teacher_id = ?",
            [$id]
        );

        $classes = $this->db->fetchAll(
            "SELECT c.* FROM school_classes c
             INNER JOIN class_teacher ct ON c.id = ct.class_id
             WHERE ct.teacher_id = ?",
            [$id]
        );

        $assignedClasses = $this->db->fetchAll(
            "SELECT c.name as class_name, sub.name as subject_name
             FROM class_subject_teacher cst
             LEFT JOIN school_classes c ON cst.class_id = c.id
             LEFT JOIN subjects sub ON cst.subject_id = sub.id
             WHERE cst.teacher_id = ?",
            [$id]
        );

        $primarySubject = !empty($subjects) ? $subjects[0] : null;
        $teacher['subject_name'] = $primarySubject['name'] ?? null;
        $teacher['assignedClasses'] = $assignedClasses;

        $this->view('dashboard.teachers.show', [
            'teacher'  => $teacher,
            'subjects' => $subjects,
            'classes'  => $classes,
        ]);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $teacher = $this->db->fetch("SELECT * FROM teachers WHERE id = ? LIMIT 1", [$id]);
        if (!$teacher) {
            Session::getInstance()->flash('error', 'Teacher not found.');
            $this->redirect('/dashboard/teachers');
            return;
        }

        $user = $this->db->fetch("SELECT * FROM users WHERE id = ? LIMIT 1", [$teacher['user_id']]);
        $subjects = $this->db->fetchAll("SELECT id, name FROM subjects ORDER BY name ASC");
        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");

        $assignedSubjectIds = array_column(
            $this->db->fetchAll("SELECT subject_id FROM class_subject_teacher WHERE teacher_id = ?", [$id]),
            'subject_id'
        );

        $this->view('dashboard.teachers.edit', [
            'teacher'           => $teacher,
            'user'              => $user,
            'subjects'          => $subjects,
            'classes'           => $classes,
            'assignedSubjectIds' => $assignedSubjectIds,
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $teacher = $this->db->fetch("SELECT * FROM teachers WHERE id = ? LIMIT 1", [$id]);
        if (!$teacher) {
            Session::getInstance()->flash('error', 'Teacher not found.');
            $this->redirect('/dashboard/teachers');
            return;
        }

        $data = $this->validate([
            'name'          => 'required|max:255',
            'email'         => 'required|email',
            'phone'         => 'max:20',
            'employee_id'   => 'max:50',
            'qualification' => 'max:255',
            'experience'    => 'max:255',
            'joining_date'  => 'date',
            'salary'        => 'numeric',
            'status'        => 'in:active,inactive',
            'subject_ids'   => 'max:500',
        ]);

        $this->db->update('users', [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$teacher['user_id']]);

        $this->db->update('teachers', [
            'employee_id'   => $data['employee_id'] ?? null,
            'qualification' => $data['qualification'] ?? null,
            'experience'    => $data['experience'] ?? null,
            'joining_date'  => $data['joining_date'] ?? null,
            'salary'        => $data['salary'] ?? null,
            'status'        => $data['status'] ?? 'active',
            'updated_at'    => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->db->delete('class_subject_teacher', 'teacher_id = ?', [$id]);
        if (!empty($data['subject_ids'])) {
            $subjectIds = array_map('intval', explode(',', $data['subject_ids']));
            foreach ($subjectIds as $sid) {
                if ($sid > 0) {
                    $this->db->insert('class_subject_teacher', [
                        'teacher_id' => $id,
                        'subject_id' => $sid,
                    ]);
                }
            }
        }

        Session::getInstance()->flash('success', 'Teacher updated successfully.');
        $this->redirect("/dashboard/teachers/{$id}");
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $teacher = $this->db->fetch("SELECT * FROM teachers WHERE id = ? LIMIT 1", [$id]);
        if (!$teacher) {
            Session::getInstance()->flash('error', 'Teacher not found.');
            $this->redirect('/dashboard/teachers');
            return;
        }

        $this->db->delete('class_subject_teacher', 'teacher_id = ?', [$id]);
        $this->db->delete('teachers', 'id = ?', [$id]);
        $this->db->update('users', ['deleted_at' => date('Y-m-d H:i:s')], 'id = ?', [$teacher['user_id']]);

        Session::getInstance()->flash('success', 'Teacher removed.');
        $this->redirect('/dashboard/teachers');
    }
}
