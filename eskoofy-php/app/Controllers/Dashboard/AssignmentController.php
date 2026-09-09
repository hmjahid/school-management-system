<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class AssignmentController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $classId = (int) ($_GET['class_id'] ?? 0);
        $subjectId = (int) ($_GET['subject_id'] ?? 0);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($classId > 0) {
            $where .= ' AND a.class_id = ?';
            $params[] = $classId;
        }
        if ($subjectId > 0) {
            $where .= ' AND a.subject_id = ?';
            $params[] = $subjectId;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM assignments a WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT a.*, c.name as class_name, sub.name as subject_name, t.name as teacher_name
             FROM assignments a
             LEFT JOIN school_classes c ON a.class_id = c.id
             LEFT JOIN subjects sub ON a.subject_id = sub.id
             LEFT JOIN teachers te ON a.teacher_id = te.id
             LEFT JOIN users t ON te.user_id = t.id
             WHERE {$where}
             ORDER BY a.deadline DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $subjects = $this->db->fetchAll("SELECT id, name FROM subjects ORDER BY name ASC");

        $this->view('dashboard.assignments.index', [
            'rows'      => $rows,
            'assignments' => $rows,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'lastPage'  => max(1, (int) ceil($total / $perPage)),
            'classes'   => $classes,
            'subjects'  => $subjects,
            'classId'   => $classId,
            'subjectId' => $subjectId,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $subjects = $this->db->fetchAll("SELECT id, name FROM subjects ORDER BY name ASC");

        $this->view('dashboard.assignments.create', [
            'classes'  => $classes,
            'subjects' => $subjects,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'title'       => 'required|max:255',
            'description' => 'max:5000',
            'class_id'    => 'required|numeric',
            'subject_id'  => 'required|numeric',
            'deadline'    => 'required',
            'max_marks'   => 'numeric',
        ]);

        $teacherId = null;
        $userId = Auth::id();
        $teacher = $this->db->fetch("SELECT id FROM teachers WHERE user_id = ? LIMIT 1", [$userId]);
        if ($teacher) {
            $teacherId = $teacher['id'];
        }

        $this->db->insert('assignments', [
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'class_id'    => $data['class_id'],
            'subject_id'  => $data['subject_id'],
            'teacher_id'  => $teacherId,
            'deadline'    => $data['deadline'],
            'max_marks'   => $data['max_marks'] ?? null,
            'created_by'  => $userId,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Assignment created successfully.');
        $this->redirect('/dashboard/assignments');
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $assignment = $this->db->fetch(
            "SELECT a.*, c.name as class_name, sub.name as subject_name, t.name as teacher_name
             FROM assignments a
             LEFT JOIN school_classes c ON a.class_id = c.id
             LEFT JOIN subjects sub ON a.subject_id = sub.id
             LEFT JOIN teachers te ON a.teacher_id = te.id
             LEFT JOIN users t ON te.user_id = t.id
             WHERE a.id = ? LIMIT 1",
            [$id]
        );

        if (!$assignment) {
            Session::getInstance()->flash('error', 'Assignment not found.');
            $this->redirect('/dashboard/assignments');
            return;
        }

        $submissions = $this->db->fetchAll(
            "SELECT asub.*, u.name as student_name
             FROM assignment_submissions asub
             LEFT JOIN students s ON asub.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             WHERE asub.assignment_id = ?
             ORDER BY asub.submitted_at DESC",
            [$id]
        );

        $this->view('dashboard.assignments.show', [
            'assignment'  => $assignment,
            'submissions' => $submissions,
        ]);
    }
}
