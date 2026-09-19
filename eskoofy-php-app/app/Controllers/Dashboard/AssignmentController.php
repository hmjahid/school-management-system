<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class AssignmentController extends Controller
{
    private DatabaseInterface $db;

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
            $where .= ' AND a.batch_id = ?';
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
            "SELECT a.*, b.name as class_name, sub.name as subject_name, t.name as teacher_name
             FROM assignments a
             LEFT JOIN batches b ON a.batch_id = b.id
             LEFT JOIN subjects sub ON a.subject_id = sub.id
             LEFT JOIN users t ON a.created_by = t.id
             WHERE {$where}
             ORDER BY a.due_date DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $subjects = $this->db->fetchAll("SELECT id, name FROM subjects ORDER BY name ASC");

        $this->view('dashboard.assignments.index', [
            'rows'      => $this->paginateRows($rows, $total, $perPage, $page, \App\Models\Assignment::class),
            'assignments' => $this->paginateRows($rows, $total, $perPage, $page, \App\Models\Assignment::class),
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'lastPage'  => max(1, (int) ceil($total / $perPage)),
            'classes'   => \App\Models\SchoolClass::hydrate($classes),
            'subjects'  => \App\Models\Subject::hydrate($subjects),
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

        $filePath = null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/storage/assignments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $filename = 'assignment-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $filename);
            $filePath = 'storage/assignments/' . $filename;
        }

        $this->db->insert('assignments', [
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'batch_id'    => $data['class_id'],
            'subject_id'  => $data['subject_id'],
            'due_date'    => $data['deadline'],
            'total_marks' => $data['max_marks'] ?? null,
            'file_path'   => $filePath,
            'allow_guardian_notes' => !empty($_POST['allow_guardian_notes']) ? 1 : 0,
            'created_by'  => Auth::id(),
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Assignment created.');
        $this->redirect('/dashboard/assignments');
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $assignmentRow = $this->db->fetch(
            "SELECT a.*, b.name as class_name, sub.name as subject_name, t.name as teacher_name
             FROM assignments a
             LEFT JOIN batches b ON a.batch_id = b.id
             LEFT JOIN subjects sub ON a.subject_id = sub.id
             LEFT JOIN users t ON a.created_by = t.id
             WHERE a.id = ? LIMIT 1",
            [$id]
        );

        if (!$assignmentRow) {
            Session::getInstance()->flash('error', 'Assignment not found.');
            $this->redirect('/dashboard/assignments');
            return;
        }

        $submissionRows = $this->db->fetchAll(
            "SELECT asub.*, u.name as student_name
             FROM assignment_submissions asub
             LEFT JOIN students s ON asub.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             WHERE asub.assignment_id = ?
             ORDER BY asub.submitted_at DESC",
            [$id]
        );

        $assignment = \App\Models\Assignment::hydrate([$assignmentRow])[0];
        $assignment->setRelation('submissions', new \App\Core\Support\Collection(\App\Models\AssignmentSubmission::hydrate($submissionRows)));

        $this->view('dashboard.assignments.show', [
            'assignment'  => $assignment,
            'submissions' => new \App\Core\Support\Collection(\App\Models\AssignmentSubmission::hydrate($submissionRows)),
        ]);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $assignment = $this->db->fetch("SELECT * FROM assignments WHERE id = ? LIMIT 1", [$id]);
        if (!$assignment) {
            Session::getInstance()->flash('error', 'Assignment not found.');
            $this->redirect('/dashboard/assignments');
            return;
        }

        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $subjects = $this->db->fetchAll("SELECT id, name FROM subjects ORDER BY name ASC");
        $batches = $this->db->fetchAll("SELECT id, name FROM batches ORDER BY id ASC LIMIT 100");

        $this->view('dashboard.assignments.edit', [
            'assignment' => $assignment,
            'classes'    => $classes,
            'subjects'   => $subjects,
            'batches'    => $batches,
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $assignment = $this->db->fetch("SELECT * FROM assignments WHERE id = ? LIMIT 1", [$id]);
        if (!$assignment) {
            Session::getInstance()->flash('error', 'Assignment not found.');
            $this->redirect('/dashboard/assignments');
            return;
        }

        $data = $this->validate([
            'title'       => 'required|max:255',
            'description' => 'max:5000',
            'batch_id'    => 'required|numeric',
            'subject_id'  => 'required|numeric',
            'due_date'    => 'required',
            'total_marks' => 'numeric',
        ]);

        $filePath = $assignment['file_path'] ?? null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/storage/assignments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $filename = 'assignment-' . $id . '-' . time() . '.' . $ext;
            move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $filename);
            if ($filePath) {
                @unlink(__DIR__ . '/../../public/' . $filePath);
            }
            $filePath = 'storage/assignments/' . $filename;
        }

        $this->db->update('assignments', [
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'batch_id'    => $data['batch_id'],
            'subject_id'  => $data['subject_id'],
            'due_date'    => $data['due_date'],
            'total_marks' => $data['total_marks'] ?? null,
            'file_path'   => $filePath,
            'allow_guardian_notes' => !empty($_POST['allow_guardian_notes']) ? 1 : 0,
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Assignment updated.');
        $this->redirect('/dashboard/assignments');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $assignment = $this->db->fetch("SELECT * FROM assignments WHERE id = ? LIMIT 1", [$id]);
        if ($assignment && !empty($assignment['file_path'])) {
            @unlink(__DIR__ . '/../../public/' . $assignment['file_path']);
        }
        $this->db->update('assignments', ['deleted_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Assignment removed.');
        $this->redirect('/dashboard/assignments');
    }

    public function submissions(int $id): void
    {
        Auth::requireAuth();
        $assignment = $this->db->fetch(
            "SELECT a.*, b.name as class_name, sub.name as subject_name
             FROM assignments a
             LEFT JOIN batches b ON a.batch_id = b.id
             LEFT JOIN subjects sub ON a.subject_id = sub.id
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
             ORDER BY asub.id ASC",
            [$id]
        );

        $this->view('dashboard.assignments.submissions', [
            'assignment'  => $assignment,
            'submissions' => $submissions,
        ]);
    }

    public function grade(int $submissionId): void
    {
        Auth::requireAuth();
        $submission = $this->db->fetch(
            "SELECT asub.*, a.total_marks FROM assignment_submissions asub
             LEFT JOIN assignments a ON asub.assignment_id = a.id
             WHERE asub.id = ? LIMIT 1",
            [$submissionId]
        );
        if (!$submission) {
            Session::getInstance()->flash('error', 'Submission not found.');
            $this->redirect('/dashboard/assignments');
            return;
        }

        $data = $this->validate([
            'marks'    => 'required|numeric',
            'feedback' => 'max:1000',
        ]);

        $this->applyGrade($submission, $data['marks'], $data['feedback'] ?? null);

        Session::getInstance()->flash('success', 'Submission graded.');
        $this->redirect('/dashboard/assignments/' . $submission['assignment_id'] . '/submissions');
    }

    public function applyGrade(array $submission, float $marks, ?string $feedback): void
    {
        $max = (float) ($submission['total_marks'] ?? 0);
        if ($max > 0 && $marks > $max) {
            $marks = $max;
        }

        $this->db->update('assignment_submissions', [
            'marks'     => $marks,
            'feedback'  => $feedback,
            'graded_by' => Auth::id(),
            'graded_at' => date('Y-m-d H:i:s'),
            'status'    => 'graded',
            'updated_at'=> date('Y-m-d H:i:s'),
        ], 'id = ?', [$submission['id']]);
    }
}
