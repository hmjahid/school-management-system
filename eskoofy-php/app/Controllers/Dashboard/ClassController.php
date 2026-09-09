<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class ClassController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $search = $_GET['search'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND c.name LIKE ?";
            $params[] = "%{$search}%";
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM school_classes c WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT c.*, t.name as teacher_name,
                (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id AND s.status = 'active') as student_count,
                (SELECT COUNT(*) FROM sections sec WHERE sec.class_id = c.id) as section_count
             FROM school_classes c
             LEFT JOIN teachers ct ON c.class_teacher_id = ct.id
             LEFT JOIN users t ON ct.user_id = t.id
             WHERE {$where}
             ORDER BY c.name ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $teachers = $this->db->fetchAll(
            "SELECT t.id, u.name FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.status = 'active' ORDER BY u.name ASC"
        );

        $this->view('dashboard.classes.index', [
            'rows'     => $rows,
            'classes'  => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'search'   => $search,
            'teachers' => $teachers,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $teachers = $this->db->fetchAll(
            "SELECT t.id, u.name FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.status = 'active' ORDER BY u.name ASC"
        );
        $sessions = $this->db->fetchAll(
            "SELECT * FROM academic_sessions ORDER BY is_current DESC, start_date DESC"
        );

        $this->view('dashboard.classes.create', [
            'teachers' => $teachers,
            'sessions' => $sessions,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'                => 'required|max:100',
            'class_teacher_id'    => 'numeric',
            'academic_session_id' => 'numeric',
            'shift'               => 'max:50',
            'capacity'            => 'numeric',
            'description'         => 'max:500',
        ]);

        $id = $this->db->insert('school_classes', [
            'name'                => $data['name'],
            'class_teacher_id'    => $data['class_teacher_id'] ?? null,
            'academic_session_id' => $data['academic_session_id'] ?? null,
            'shift'               => $data['shift'] ?? null,
            'capacity'            => $data['capacity'] ?? null,
            'description'         => $data['description'] ?? null,
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Class created successfully.');
        $this->redirect('/dashboard/classes');
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $schoolClass = $this->db->fetch(
            "SELECT c.*, t.name as teacher_name, a.name as session_name
             FROM school_classes c
             LEFT JOIN teachers ct ON c.class_teacher_id = ct.id
             LEFT JOIN users t ON ct.user_id = t.id
             LEFT JOIN academic_sessions a ON c.academic_session_id = a.id
             WHERE c.id = ? LIMIT 1",
            [$id]
        );

        if (!$schoolClass) {
            Session::getInstance()->flash('error', 'Class not found.');
            $this->redirect('/dashboard/classes');
            return;
        }

        $sections = $this->db->fetchAll(
            "SELECT s.*, sec.name as section_name, (SELECT COUNT(*) FROM students st WHERE st.section_id = s.id AND st.status = 'active') as students_count
             FROM sections s LEFT JOIN sections sec ON s.id = sec.id
             WHERE s.class_id = ? ORDER BY s.name ASC", [$id]
        );

        $students = $this->db->fetchAll(
            "SELECT s.*, u.name, sec.name as section_name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             WHERE s.class_id = ? AND s.status = 'active'
             ORDER BY s.roll_number ASC LIMIT 100",
            [$id]
        );

        $schoolClass['sections'] = $sections;
        $schoolClass['students'] = $students;
        $schoolClass['students_count'] = count($students);
        $schoolClass['sections_count'] = count($sections);

        $this->view('dashboard.classes.show', [
            'schoolClass' => $schoolClass,
            'class'       => $schoolClass,
            'sections'    => $sections,
            'students'    => $students,
        ]);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $schoolClass = $this->db->fetch("SELECT * FROM school_classes WHERE id = ? LIMIT 1", [$id]);
        if (!$schoolClass) {
            Session::getInstance()->flash('error', 'Class not found.');
            $this->redirect('/dashboard/classes');
            return;
        }

        $teachers = $this->db->fetchAll(
            "SELECT t.id, u.name FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.status = 'active' ORDER BY u.name ASC"
        );
        $sessions = $this->db->fetchAll(
            "SELECT * FROM academic_sessions ORDER BY is_current DESC, start_date DESC"
        );

        $this->view('dashboard.classes.edit', [
            'schoolClass' => $schoolClass,
            'teachers'    => $teachers,
            'sessions'    => $sessions,
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $schoolClass = $this->db->fetch("SELECT * FROM school_classes WHERE id = ? LIMIT 1", [$id]);
        if (!$schoolClass) {
            Session::getInstance()->flash('error', 'Class not found.');
            $this->redirect('/dashboard/classes');
            return;
        }

        $data = $this->validate([
            'name'                => 'required|max:100',
            'class_teacher_id'    => 'numeric',
            'academic_session_id' => 'numeric',
            'shift'               => 'max:50',
            'capacity'            => 'numeric',
            'description'         => 'max:500',
        ]);

        $this->db->update('school_classes', [
            'name'                => $data['name'],
            'class_teacher_id'    => $data['class_teacher_id'] ?? null,
            'academic_session_id' => $data['academic_session_id'] ?? null,
            'shift'               => $data['shift'] ?? null,
            'capacity'            => $data['capacity'] ?? null,
            'description'         => $data['description'] ?? null,
            'updated_at'          => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Class updated successfully.');
        $this->redirect("/dashboard/classes/{$id}");
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $studentCount = $this->db->count('students', "class_id = ? AND status = 'active'", [$id]);
        if ($studentCount > 0) {
            Session::getInstance()->flash('error', 'Cannot delete class with active students. Reassign them first.');
            $this->redirect('/dashboard/classes');
            return;
        }

        $this->db->delete('sections', 'class_id = ?', [$id]);
        $this->db->delete('school_classes', 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Class removed.');
        $this->redirect('/dashboard/classes');
    }
}
