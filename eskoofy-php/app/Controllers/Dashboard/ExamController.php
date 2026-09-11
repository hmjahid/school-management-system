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
        $examRow = $this->db->fetch("SELECT * FROM exams WHERE id = ? LIMIT 1", [$id]);
        if (!$examRow) {
            Session::getInstance()->flash('error', 'Exam not found.');
            $this->redirect('/dashboard/exams');
            return;
        }

        $studentRows = $this->db->fetchAll(
            "SELECT s.id, s.user_id, s.class_id, s.section_id, s.admission_number, s.roll_number, u.name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             WHERE s.batch_id = ? AND s.status = 'active'
             ORDER BY s.roll_number ASC, u.name ASC",
            [$examRow['batch_id']]
        );

        $subjects = $this->db->fetchAll(
            "SELECT * FROM subjects ORDER BY name ASC"
        );

        $rows = $this->db->fetchAll(
            "SELECT * FROM exam_results WHERE exam_id = ?", [$id]
        );

        $byStudent = [];
        foreach ($rows as $row) {
            $byStudent[$row['student_id']] ??= $row;
        }

        $students = new \App\Core\Support\Collection(\App\Models\Student::hydrate($studentRows));
        $results = new \App\Core\Support\Collection(
            $byStudent !== []
                ? array_combine(array_keys($byStudent), \App\Models\ExamResult::hydrate(array_values($byStudent)))
                : []
        );

        $totalStudents = $students->count();
        $participated = count($byStudent);
        $passed = count(array_filter($rows, static fn ($r) => in_array($r['status'] ?? null, ['passed', 'Pass'], true) || (float) ($r['obtained_marks'] ?? 0) >= (float) ($examRow['passing_marks'] ?? 0)));
        $marks = array_filter(array_map(static fn ($r) => $r['obtained_marks'] ?? null, $rows), static fn ($v) => $v !== null);

        $stats = [
            'total_students' => $totalStudents,
            'participated'   => $participated,
            'not_participated' => max(0, $totalStudents - $participated),
            'passed'         => $passed,
            'failed'         => $participated - $passed,
            'average_score'  => $marks !== [] ? round(array_sum($marks) / count($marks), 2) : 0,
            'highest_score'  => $marks !== [] ? max($marks) : 0,
            'lowest_score'   => $marks !== [] ? min($marks) : 0,
            'pass_rate'      => $participated > 0 ? round(($passed / $participated) * 100, 2) : 0,
            'participation_rate' => $totalStudents > 0 ? round(($participated / $totalStudents) * 100, 2) : 0,
        ];

        $smsRecipients = $this->db->fetch(
            "SELECT COUNT(DISTINCT g.id) as cnt
             FROM students s
             LEFT JOIN guardians g ON s.guardian_id = g.id
             WHERE s.batch_id = ? AND s.status = 'active' AND g.id IS NOT NULL",
            [$examRow['batch_id']]
        )['cnt'] ?? 0;

        $this->view('dashboard.exams.results', [
            'exam'            => \App\Models\Exam::hydrate([$examRow])[0],
            'students'        => $students,
            'subjects'        => new \App\Core\Support\Collection(\App\Models\Subject::hydrate($subjects)),
            'results'         => $results,
            'stats'           => $stats,
            'smsRecipients'   => (int) $smsRecipients,
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

    public function exportResults(int $id): void
    {
        Auth::requireAuth();
        $exam = $this->db->fetch("SELECT * FROM exams WHERE id = ? LIMIT 1", [$id]);
        if (!$exam) {
            Session::getInstance()->flash('error', 'Exam not found.');
            $this->redirect('/dashboard/exams');
            return;
        }

        [$header, $rows] = $this->buildResultsExport($exam);
        $filename = 'exam-' . (($exam['code'] ?? '') ?: $exam['id']) . '-results.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $out = fopen('php://output', 'w');
        fputcsv($out, $header);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    public function buildResultsExport(array $exam): array
    {
        $rows = $this->db->fetchAll(
            "SELECT er.obtained_marks, er.grade, er.grade_point, er.status, er.is_published,
                    s.admission_number, s.roll_number, u.name, c.name as class_name, sec.name as section_name
             FROM exam_results er
             LEFT JOIN students s ON er.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             WHERE er.exam_id = ?
             ORDER BY s.roll_number ASC, u.name ASC",
            [$exam['id']]
        );

        $results = \App\Models\ExamResult::hydrate($rows);

        $header = ['admission_number', 'name', 'class', 'section', 'roll', 'obtained_marks', 'total_marks', 'grade', 'grade_point', 'status', 'is_published'];
        $data = [];
        foreach ($results as $r) {
            $data[] = [
                $r['admission_number'] ?? '', $r['name'] ?? '', $r['class_name'] ?? '', $r['section_name'] ?? '', $r['roll_number'] ?? '',
                $r['obtained_marks'] ?? '', $exam['total_marks'] ?? '', $r['grade'] ?? '', $r['grade_point'] ?? '', $r['status'] ?? '',
                !empty($r['is_published']) ? '1' : '0',
            ];
        }
        return [$header, $data];
    }

    public function myResults(): void
    {
        Auth::requireAuth();
        $role = Auth::role() ?? '';
        $userId = Auth::id();

        $exams = [];
        if ($role === 'admin') {
            $exams = $this->db->fetchAll(
                "SELECT e.*, sub.name as subject_name, b.name as batch_name, sec.name as section_name,
                    (SELECT COUNT(*) FROM exam_results er WHERE er.exam_id = e.id) as results_count,
                    (SELECT COUNT(*) FROM exam_results er WHERE er.exam_id = e.id AND er.is_published = 1) as published_count
                 FROM exams e
                 LEFT JOIN subjects sub ON e.subject_id = sub.id
                 LEFT JOIN batches b ON e.batch_id = b.id
                 LEFT JOIN sections sec ON e.section_id = sec.id
                 ORDER BY e.start_date DESC LIMIT 100"
            );
        } else {
            $student = $this->db->fetch("SELECT * FROM students WHERE user_id = ? LIMIT 1", [$userId]);
            if ($student) {
                $exams = $this->db->fetchAll(
                    "SELECT e.*, sub.name as subject_name, b.name as batch_name, sec.name as section_name,
                        (SELECT COUNT(*) FROM exam_results er WHERE er.exam_id = e.id) as results_count,
                        (SELECT COUNT(*) FROM exam_results er WHERE er.exam_id = e.id AND er.is_published = 1) as published_count
                     FROM exams e
                     LEFT JOIN subjects sub ON e.subject_id = sub.id
                     LEFT JOIN batches b ON e.batch_id = b.id
                     LEFT JOIN sections sec ON e.section_id = sec.id
                     WHERE e.batch_id = ? AND e.is_published = 1
                     ORDER BY e.start_date DESC LIMIT 100",
                    [$student['batch_id']]
                );
            }
        }

        $countRows = $this->db->fetchAll(
            "SELECT COALESCE(batch_id, 0) as batch_id, COALESCE(section_id, 0) as section_id, COUNT(*) as c
             FROM students
             WHERE status = 'active'
             GROUP BY COALESCE(batch_id, 0), COALESCE(section_id, 0)"
        );
        $byBatch = [];
        $bySection = [];
        $byPair = [];
        foreach ($countRows as $row) {
            $count = (int) ($row['c'] ?? 0);
            $byBatch[$row['batch_id']] = ($byBatch[$row['batch_id']] ?? 0) + $count;
            $bySection[$row['section_id']] = ($bySection[$row['section_id']] ?? 0) + $count;
            $byPair[$row['batch_id'] . ':' . $row['section_id']] = $count;
        }

        $models = [];
        foreach ($exams as $e) {
            $model = \App\Models\Exam::newFromRow($e);
            $model->setAttribute('results_count', (int) ($e['results_count'] ?? 0));
            $total = 0;
            if (!empty($e['batch_id']) && !empty($e['section_id'])) {
                $total = $byPair[$e['batch_id'] . ':' . $e['section_id']] ?? 0;
            } elseif (!empty($e['batch_id'])) {
                $total = $byBatch[$e['batch_id']] ?? 0;
            } elseif (!empty($e['section_id'])) {
                $total = $bySection[$e['section_id']] ?? 0;
            }
            $model->setAttribute('total_students', $total);
            $models[] = $model;
        }

        $all = new \App\Core\Support\Collection($models);
        $published = $all->filter(static fn ($e) => (bool) ($e->is_published ?? false));
        $ready = $all->filter(static function ($e) {
            return !(bool) ($e->is_published ?? false)
                && (int) ($e->total_students ?? 0) > 0
                && (int) ($e->results_count ?? 0) >= (int) ($e->total_students ?? 0);
        });
        $pending = $all->filter(static function ($e) {
            return !(bool) ($e->is_published ?? false)
                && !((int) ($e->total_students ?? 0) > 0 && (int) ($e->results_count ?? 0) >= (int) ($e->total_students ?? 0));
        });

        $this->view('dashboard.exams.my_results', [
            'pending'   => $pending,
            'ready'     => $ready,
            'published' => $published,
        ]);
    }

    public function studentResultsExport(int $id): void
    {
        Auth::requireAuth();
        $student = $this->db->fetch("SELECT * FROM students WHERE id = ? LIMIT 1", [$id]);
        if (!$student) {
            Session::getInstance()->flash('error', 'Student not found.');
            $this->redirect('/dashboard/students');
            return;
        }

        [$header, $rows] = $this->buildStudentResultsExport($student);
        $filename = 'student-' . (($student['admission_number'] ?? '') ?: $student['id']) . '-results.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $out = fopen('php://output', 'w');
        fputcsv($out, $header);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    public function buildStudentResultsExport(array $student): array
    {
        $rows = $this->db->fetchAll(
            "SELECT er.*, e.name as exam_name, e.start_date, sub.name as subject_name
             FROM exam_results er
             LEFT JOIN exams e ON er.exam_id = e.id
             LEFT JOIN subjects sub ON er.subject_id = sub.id
             WHERE er.student_id = ? AND er.is_published = 1
             ORDER BY e.start_date DESC, sub.name ASC",
            [$student['id']]
        );

        $results = \App\Models\ExamResult::hydrate($rows);

        $header = ['exam', 'date', 'subject', 'obtained_marks', 'total_marks', 'grade', 'grade_point', 'status'];
        $data = [];
        foreach ($results as $r) {
            $data[] = [
                $r['exam_name'] ?? '', $r['start_date'] ?? '', $r['subject_name'] ?? '', $r['obtained_marks'] ?? '',
                $r['total_marks'] ?? '', $r['grade'] ?? '', $r['grade_point'] ?? '', $r['status'] ?? '',
            ];
        }
        return [$header, $data];
    }
}
