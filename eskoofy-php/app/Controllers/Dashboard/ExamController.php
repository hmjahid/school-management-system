<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class ExamController extends Controller
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
        $examType = $_GET['exam_type'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND (e.name LIKE ? OR e.exam_type LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }
        if ($examType !== '') {
            $where .= ' AND e.exam_type = ?';
            $params[] = $examType;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM exams e WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT e.*, b.name as batch_name, s.name as session_name,
                (SELECT COUNT(*) FROM exam_results er WHERE er.exam_id = e.id) as result_count
             FROM exams e
             LEFT JOIN batches b ON e.batch_id = b.id
             LEFT JOIN academic_sessions s ON e.academic_session_id = s.id
             WHERE {$where}
             ORDER BY e.start_date DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $batches = $this->db->fetchAll("SELECT id, name FROM batches ORDER BY id DESC LIMIT 50");
        $sessions = $this->db->fetchAll("SELECT * FROM academic_sessions ORDER BY is_current DESC, start_date DESC");
        $subjects = $this->db->fetchAll("SELECT id, name FROM subjects ORDER BY name ASC");
        $sections = $this->db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC");

        $this->view('dashboard.exams.index', [
            'rows'      => $rows,
            'exams' => $rows,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'lastPage'  => max(1, (int) ceil($total / $perPage)),
            'search'    => $search,
            'examType'  => $examType,
            'batches'   => $batches,
            'sessions'  => $sessions,
            'subjects'  => $subjects,
            'sections'  => $sections,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $batches = $this->db->fetchAll("SELECT id, name FROM batches ORDER BY id DESC LIMIT 50");
        $sessions = $this->db->fetchAll("SELECT * FROM academic_sessions ORDER BY is_current DESC, start_date DESC");
        $subjects = $this->db->fetchAll("SELECT id, name FROM subjects ORDER BY name ASC");
        $sections = $this->db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC");

        $this->view('dashboard.exams.create', [
            'batches'  => $batches,
            'sessions' => $sessions,
            'subjects' => $subjects,
            'sections' => $sections,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'                => 'required|max:255',
            'exam_type'           => 'required|max:50',
            'exam_date'           => 'required',
            'batch_id'            => 'required|numeric',
            'academic_session_id' => 'numeric',
            'section_id'          => 'numeric',
            'total_marks'         => 'required|numeric',
            'passing_marks'       => 'required|numeric',
            'grading_type'        => 'max:50',
            'description'         => 'max:1000',
        ]);

        $id = $this->db->insert('exams', [
            'name'                => $data['name'],
            'exam_type'           => $data['exam_type'],
            'start_date'         => $data['exam_date'],
            'batch_id'            => $data['batch_id'],
            'academic_session_id' => $data['academic_session_id'] ?? null,
            'section_id'          => $data['section_id'] ?? null,
            'total_marks'         => $data['total_marks'],
            'passing_marks'       => $data['passing_marks'],
            'grading_type'        => $data['grading_type'] ?? 'standard',
            'description'         => $data['description'] ?? null,
            'status'              => 'draft',
            'is_published'        => 0,
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Exam created successfully.');
        $this->redirect('/dashboard/exams');
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $exam = $this->db->fetch(
            "SELECT e.*, b.name as batch_name, s.name as session_name, sec.name as section_name
             FROM exams e
             LEFT JOIN batches b ON e.batch_id = b.id
             LEFT JOIN academic_sessions s ON e.academic_session_id = s.id
             LEFT JOIN sections sec ON e.section_id = sec.id
             WHERE e.id = ? LIMIT 1",
            [$id]
        );

        if (!$exam) {
            Session::getInstance()->flash('error', 'Exam not found.');
            $this->redirect('/dashboard/exams');
            return;
        }

        $results = $this->db->fetchAll(
            "SELECT er.*, u.name as student_name, sub.name as subject_name, s.admission_number
             FROM exam_results er
             LEFT JOIN students s ON er.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN subjects sub ON er.subject_id = sub.id
             WHERE er.exam_id = ?
             ORDER BY u.name ASC",
            [$id]
        );

        $subjects = $this->db->fetchAll("SELECT id, name FROM subjects ORDER BY name ASC");

        $this->view('dashboard.exams.show', [
            'exam'     => $exam,
            'results'  => $results,
            'subjects' => $subjects,
        ]);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $exam = $this->db->fetch("SELECT * FROM exams WHERE id = ? LIMIT 1", [$id]);
        if (!$exam) {
            Session::getInstance()->flash('error', 'Exam not found.');
            $this->redirect('/dashboard/exams');
            return;
        }

        $batches = $this->db->fetchAll("SELECT id, name FROM batches ORDER BY id DESC LIMIT 50");
        $sessions = $this->db->fetchAll("SELECT * FROM academic_sessions ORDER BY is_current DESC, start_date DESC");
        $subjects = $this->db->fetchAll("SELECT id, name FROM subjects ORDER BY name ASC");
        $sections = $this->db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC");

        $this->view('dashboard.exams.edit', [
            'exam'     => $exam,
            'batches'  => $batches,
            'sessions' => $sessions,
            'subjects' => $subjects,
            'sections' => $sections,
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $exam = $this->db->fetch("SELECT * FROM exams WHERE id = ? LIMIT 1", [$id]);
        if (!$exam) {
            Session::getInstance()->flash('error', 'Exam not found.');
            $this->redirect('/dashboard/exams');
            return;
        }

        $data = $this->validate([
            'name'                => 'required|max:255',
            'exam_type'           => 'required|max:50',
            'exam_date'           => 'required',
            'batch_id'            => 'required|numeric',
            'academic_session_id' => 'numeric',
            'section_id'          => 'numeric',
            'total_marks'         => 'required|numeric',
            'passing_marks'       => 'required|numeric',
            'grading_type'        => 'max:50',
            'description'         => 'max:1000',
        ]);

        $this->db->update('exams', [
            'name'                => $data['name'],
            'exam_type'           => $data['exam_type'],
            'start_date'         => $data['exam_date'],
            'batch_id'            => $data['batch_id'],
            'academic_session_id' => $data['academic_session_id'] ?? null,
            'section_id'          => $data['section_id'] ?? null,
            'total_marks'         => $data['total_marks'],
            'passing_marks'       => $data['passing_marks'],
            'grading_type'        => $data['grading_type'] ?? 'standard',
            'description'         => $data['description'] ?? null,
            'updated_at'          => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Exam updated successfully.');
        $this->redirect("/dashboard/exams/{$id}");
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('exam_results', 'exam_id = ?', [$id]);
        $this->db->delete('exams', 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Exam removed.');
        $this->redirect('/dashboard/exams');
    }

    public function publish(int $id): void
    {
        Auth::requireAuth();
        $exam = $this->db->fetch("SELECT * FROM exams WHERE id = ? LIMIT 1", [$id]);
        if (!$exam) {
            Session::getInstance()->flash('error', 'Exam not found.');
            $this->redirect('/dashboard/exams');
            return;
        }

        $newStatus = $exam['is_published'] ? 0 : 1;
        $this->db->update('exams', [
            'is_published'  => $newStatus,
            'status'        => $newStatus ? 'published' : 'draft',
            'updated_at'    => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $msg = $newStatus ? 'Exam published.' : 'Exam unpublished.';
        Session::getInstance()->flash('success', $msg);
        $this->redirect("/dashboard/exams/{$id}");
    }

    public function results(int $id): void
    {
        Auth::requireAuth();
        $exam = $this->db->fetch("SELECT * FROM exams WHERE id = ? LIMIT 1", [$id]);
        if (!$exam) {
            Session::getInstance()->flash('error', 'Exam not found.');
            $this->redirect('/dashboard/exams');
            return;
        }

        $students = $this->db->fetchAll(
            "SELECT s.id, u.name, s.admission_number, s.roll_number
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             WHERE s.batch_id = ? AND s.status = 'active'
             ORDER BY s.roll_number ASC, u.name ASC",
            [$exam['batch_id']]
        );

        $subjects = $this->db->fetchAll(
            "SELECT * FROM subjects ORDER BY name ASC"
        );

        $existingResults = [];
        $rows = $this->db->fetchAll(
            "SELECT * FROM exam_results WHERE exam_id = ?", [$id]
        );
        foreach ($rows as $row) {
            $existingResults[$row['student_id']][$row['subject_id']] = $row;
        }

        $this->view('dashboard.exams.results', [
            'exam'            => $exam,
            'students'        => $students,
            'subjects'        => $subjects,
            'existingResults' => $existingResults,
        ]);
    }

    public function storeResults(int $id): void
    {
        Auth::requireAuth();
        $exam = $this->db->fetch("SELECT * FROM exams WHERE id = ? LIMIT 1", [$id]);
        if (!$exam) {
            Session::getInstance()->flash('error', 'Exam not found.');
            $this->redirect('/dashboard/exams');
            return;
        }

        $studentIds = $_POST['student_ids'] ?? [];
        $marks = $_POST['marks'] ?? [];
        $remarks = $_POST['result_remarks'] ?? [];
        $userId = Auth::id();
        $count = 0;

        $this->db->beginTransaction();
        try {
            foreach ($studentIds as $studentId) {
                foreach ($marks[$studentId] ?? [] as $subjectId => $obtainedMarks) {
                    if ($obtainedMarks === '' || $obtainedMarks === null) {
                        continue;
                    }

                    $existing = $this->db->fetch(
                        "SELECT id FROM exam_results WHERE exam_id = ? AND student_id = ? AND subject_id = ? LIMIT 1",
                        [$id, $studentId, $subjectId]
                    );

                    $resultData = [
                        'exam_id'        => $id,
                        'student_id'     => $studentId,
                        'subject_id'     => $subjectId,
                        'obtained_marks' => (float) $obtainedMarks,
                        'total_marks'    => (float) $exam['total_marks'],
                        'passing_marks'  => (float) $exam['passing_marks'],
                        'grade'          => (float) $obtainedMarks >= (float) $exam['passing_marks'] ? 'Pass' : 'Fail',
                        'remarks'        => $remarks[$studentId][$subjectId] ?? null,
                        'created_by'     => $userId,
                        'created_at'     => date('Y-m-d H:i:s'),
                        'updated_at'     => date('Y-m-d H:i:s'),
                    ];

                    if ($existing) {
                        unset($resultData['exam_id'], $resultData['student_id'], $resultData['subject_id'], $resultData['created_at']);
                        $this->db->update('exam_results', $resultData, 'id = ?', [$existing['id']]);
                    } else {
                        $this->db->insert('exam_results', $resultData);
                    }
                    $count++;
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            Session::getInstance()->flash('error', 'Failed to save results: ' . $e->getMessage());
            $this->redirect("/dashboard/exams/{$id}/results");
            return;
        }

        Session::getInstance()->flash('success', "Saved {$count} results.");
        $this->redirect("/dashboard/exams/{$id}");
    }
}
