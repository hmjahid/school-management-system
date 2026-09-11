<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class StudentController extends Controller
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
        $classId = (int) ($_GET['class_id'] ?? 0);
        $sectionId = (int) ($_GET['section_id'] ?? 0);
        $status = $_GET['status'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];

        if ($search !== '') {
            $where .= " AND (u.name LIKE ? OR s.admission_number LIKE ? OR u.email LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($classId > 0) {
            $where .= ' AND s.class_id = ?';
            $params[] = $classId;
        }
        if ($sectionId > 0) {
            $where .= ' AND s.section_id = ?';
            $params[] = $sectionId;
        }
        if ($status !== '') {
            $where .= ' AND s.status = ?';
            $params[] = $status;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE {$where}",
            $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT s.*, u.name, u.email, u.photo, c.name as class_name, sec.name as section_name, b.name as batch_name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             LEFT JOIN batches b ON s.batch_id = b.id
             WHERE {$where}
             ORDER BY s.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $sections = $this->db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC");

        $this->view('dashboard.students.index', [
            'rows'       => $rows,
            'students'   => $rows,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'lastPage'   => max(1, (int) ceil($total / $perPage)),
            'classes'    => $classes,
            'sections'   => $sections,
            'search'     => $search,
            'classId'    => $classId,
            'sectionId'  => $sectionId,
            'status'     => $status,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $sections = $this->db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC");
        $batches = $this->db->fetchAll("SELECT id, name FROM batches ORDER BY id DESC LIMIT 50");
        $guardians = $this->db->fetchAll(
            "SELECT g.*, u.name as guardian_name FROM guardians g LEFT JOIN users u ON g.user_id = u.id ORDER BY g.id DESC LIMIT 200"
        );

        $this->view('dashboard.students.create', [
            'classes'   => $classes,
            'sections'  => $sections,
            'batches'   => $batches,
            'guardians' => $guardians,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'       => 'required|max:255',
            'email'      => 'required|email',
            'phone'      => 'max:20',
            'class_id'   => 'required|numeric',
            'section_id' => 'numeric',
            'batch_id'   => 'required|numeric',
            'roll_number'=> 'max:20',
            'gender'     => 'in:male,female,other',
            'date_of_birth' => 'date',
            'address'    => 'max:500',
            'guardian_id'=> 'numeric',
        ]);

        $userId = $this->db->insert('users', [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'role'       => 'student',
            'role_id'     => \App\Core\Auth::roleId('student'),
            'password'   => App\Core\Auth::hashPassword('password'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $admissionNumber = 'STU-' . date('Ymd') . '-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT);

        $studentId = $this->db->insert('students', [
            'user_id'           => $userId,
            'admission_number'  => $admissionNumber,
            'class_id'          => $data['class_id'],
            'section_id'        => $data['section_id'] ?? null,
            'batch_id'          => $data['batch_id'],
            'roll_number'       => $data['roll_number'] ?? null,
            'gender'            => $data['gender'] ?? null,
            'date_of_birth'     => $data['date_of_birth'] ?? null,
            'address'           => $data['address'] ?? null,
            'guardian_id'       => $data['guardian_id'] ?? null,
            'status'            => 'active',
            'admission_date'    => date('Y-m-d'),
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Student created successfully.');
        $this->redirect('/dashboard/students');
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $student = $this->db->fetch(
            "SELECT s.*, u.name, u.email, u.phone as user_phone, u.photo, u.gender as user_gender,
                    c.name as class_name, sec.name as section_name, b.name as batch_name,
                    g.id as guardian_db_id, gu.name as guardian_name, gu.email as guardian_email
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             LEFT JOIN batches b ON s.batch_id = b.id
             LEFT JOIN guardians g ON s.guardian_id = g.id
             LEFT JOIN users gu ON g.user_id = gu.id
             WHERE s.id = ? LIMIT 1",
            [$id]
        );

        if (!$student) {
            Session::getInstance()->flash('error', 'Student not found.');
            $this->redirect('/dashboard/students');
            return;
        }

        $recentAttendance = $this->db->fetchAll(
            "SELECT * FROM attendances WHERE student_id = ? ORDER BY date DESC LIMIT 10",
            [$id]
        );

        $recentResults = $this->db->fetchAll(
            "SELECT er.*, e.name as exam_name, sub.name as subject_name
             FROM exam_results er
             LEFT JOIN exams e ON er.exam_id = e.id
             LEFT JOIN subjects sub ON er.subject_id = sub.id
             WHERE er.student_id = ?
             ORDER BY er.id DESC LIMIT 10",
            [$id]
        );

        $feePayments = $this->db->fetchAll(
            "SELECT fp.*, f.name as fee_name
             FROM fee_payments fp
             LEFT JOIN fees f ON fp.fee_id = f.id
             WHERE fp.student_id = ?
             ORDER BY fp.id DESC LIMIT 10",
            [$id]
        );

        $this->view('dashboard.students.show', [
            'student'          => $student,
            'recentAttendance' => $recentAttendance,
            'recentResults'    => $recentResults,
            'feePayments'      => $feePayments,
        ]);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $student = $this->db->fetch("SELECT * FROM students WHERE id = ? LIMIT 1", [$id]);
        if (!$student) {
            Session::getInstance()->flash('error', 'Student not found.');
            $this->redirect('/dashboard/students');
            return;
        }

        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $sections = $this->db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC");
        $batches = $this->db->fetchAll("SELECT id, name FROM batches ORDER BY id DESC LIMIT 50");
        $guardians = $this->db->fetchAll(
            "SELECT g.*, u.name as guardian_name FROM guardians g LEFT JOIN users u ON g.user_id = u.id ORDER BY g.id DESC LIMIT 200"
        );
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ? LIMIT 1", [$student['user_id']]);

        $this->view('dashboard.students.edit', [
            'student'   => $student,
            'user'      => $user,
            'classes'   => $classes,
            'sections'  => $sections,
            'batches'   => $batches,
            'guardians' => $guardians,
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $student = $this->db->fetch("SELECT * FROM students WHERE id = ? LIMIT 1", [$id]);
        if (!$student) {
            Session::getInstance()->flash('error', 'Student not found.');
            $this->redirect('/dashboard/students');
            return;
        }

        $data = $this->validate([
            'name'        => 'required|max:255',
            'email'       => 'required|email',
            'class_id'    => 'required|numeric',
            'section_id'  => 'numeric',
            'batch_id'    => 'required|numeric',
            'roll_number' => 'max:20',
            'status'      => 'in:active,inactive,graduated,transferred',
            'gender'      => 'in:male,female,other',
            'date_of_birth' => 'date',
            'address'     => 'max:500',
            'guardian_id' => 'numeric',
        ]);

        $this->db->update('users', [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$student['user_id']]);

        $this->db->update('students', [
            'class_id'     => $data['class_id'],
            'section_id'   => $data['section_id'] ?? null,
            'batch_id'     => $data['batch_id'],
            'roll_number'  => $data['roll_number'] ?? null,
            'status'       => $data['status'] ?? 'active',
            'gender'       => $data['gender'] ?? null,
            'date_of_birth'=> $data['date_of_birth'] ?? null,
            'address'      => $data['address'] ?? null,
            'guardian_id'  => $data['guardian_id'] ?? null,
            'updated_at'   => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Student updated successfully.');
        $this->redirect("/dashboard/students/{$id}");
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $student = $this->db->fetch("SELECT * FROM students WHERE id = ? LIMIT 1", [$id]);
        if (!$student) {
            Session::getInstance()->flash('error', 'Student not found.');
            $this->redirect('/dashboard/students');
            return;
        }

        $this->db->delete('students', 'id = ?', [$id]);
        $this->db->delete('users', 'id = ?', [$student['user_id']]);

        Session::getInstance()->flash('success', 'Student removed.');
        $this->redirect('/dashboard/students');
    }

    public function attendance(int $id): void
    {
        Auth::requireAuth();
        $student = $this->db->fetch("SELECT s.*, u.name FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = ? LIMIT 1", [$id]);
        if (!$student) {
            Session::getInstance()->flash('error', 'Student not found.');
            $this->redirect('/dashboard/students');
            return;
        }

        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');

        $records = $this->db->fetchAll(
            "SELECT * FROM attendances WHERE student_id = ? AND date BETWEEN ? AND ? ORDER BY date DESC",
            [$id, $from, $to]
        );

        $totalDays = count($records);
        $presentDays = 0;
        foreach ($records as $r) {
            if (in_array($r['status'], ['present', 'late', 'half_day'])) {
                $presentDays++;
            }
        }
        $rate = $totalDays > 0 ? round(100 * $presentDays / $totalDays, 1) : 0;

        $this->view('dashboard.students.attendance', [
            'student'     => $student,
            'records'     => $records,
            'totalDays'   => $totalDays,
            'presentDays' => $presentDays,
            'rate'        => $rate,
            'from'        => $from,
            'to'          => $to,
        ]);
    }

    public function results(int $id): void
    {
        Auth::requireAuth();
        $studentRow = $this->db->fetch("SELECT s.*, u.name FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = ? LIMIT 1", [$id]);
        if (!$studentRow) {
            Session::getInstance()->flash('error', 'Student not found.');
            $this->redirect('/dashboard/students');
            return;
        }

        $rows = $this->db->fetchAll(
            "SELECT er.* FROM exam_results er
             LEFT JOIN exams e ON er.exam_id = e.id
             WHERE er.student_id = ? AND er.is_published = 1
             ORDER BY e.start_date DESC, er.id DESC",
            [$id]
        );

        $gradePoints = array_values(array_filter(array_map(static fn ($r) => $r['grade_point'] ?? null, $rows), static fn ($v) => $v !== null));
        $summary = [
            'count'           => count($rows),
            'avg_grade_point' => $gradePoints !== [] ? round(array_sum($gradePoints) / count($gradePoints), 2) : null,
            'latest_grade'    => $rows[0]['grade'] ?? null,
        ];

        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('dashboard.students.results', [
            'student' => \App\Models\Student::hydrate([$studentRow])[0],
            'summary' => $summary,
            'results' => $this->paginateRows($rows, count($rows), 20, $page, \App\Models\ExamResult::class),
        ]);
    }

    public function fees(int $id): void
    {
        Auth::requireAuth();
        $student = $this->db->fetch("SELECT s.*, u.name FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = ? LIMIT 1", [$id]);
        if (!$student) {
            Session::getInstance()->flash('error', 'Student not found.');
            $this->redirect('/dashboard/students');
            return;
        }

        $fees = $this->db->fetchAll(
            "SELECT f.*, c.name as class_name
             FROM fees f
             LEFT JOIN school_classes c ON f.class_id = c.id
             WHERE f.class_id = ? OR f.student_id = ?
             ORDER BY f.id DESC",
            [$student['class_id'], $id]
        );

        $payments = $this->db->fetchAll(
            "SELECT fp.*, f.name as fee_name
             FROM fee_payments fp
             LEFT JOIN fees f ON fp.fee_id = f.id
             WHERE fp.student_id = ?
             ORDER BY fp.id DESC",
            [$id]
        );

        $totalDue = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(balance), 0) as total FROM fee_payments WHERE student_id = ? AND status IN ('pending', 'partial')",
            [$id]
        )['total'] ?? 0);

        $totalPaid = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(paid_amount), 0) as total FROM fee_payments WHERE student_id = ? AND status = 'paid'",
            [$id]
        )['total'] ?? 0);

        $this->view('dashboard.students.fees', [
            'student'   => $student,
            'fees'      => $fees,
            'payments'  => $payments,
            'totalDue'  => $totalDue,
            'totalPaid' => $totalPaid,
        ]);
    }

    public function promoteForm(): void
    {
        Auth::requireAuth();
        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $sections = $this->db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC");
        $batches = $this->db->fetchAll("SELECT id, name FROM batches ORDER BY id DESC LIMIT 50");

        $fromClassId = (int) ($_GET['from_class_id'] ?? 0);
        $fromSectionId = (int) ($_GET['from_section_id'] ?? 0);

        $students = [];
        if ($fromClassId > 0) {
            $where = 's.class_id = ? AND s.status = ?';
            $params = [$fromClassId, 'active'];
            if ($fromSectionId > 0) {
                $where .= ' AND s.section_id = ?';
                $params[] = $fromSectionId;
            }
            $students = $this->db->fetchAll(
                "SELECT s.*, u.name, c.name as class_name, sec.name as section_name
                 FROM students s
                 LEFT JOIN users u ON s.user_id = u.id
                 LEFT JOIN school_classes c ON s.class_id = c.id
                 LEFT JOIN sections sec ON s.section_id = sec.id
                 WHERE {$where}
                 ORDER BY s.roll_number ASC, u.name ASC",
                $params
            );
        }

        $this->view('dashboard.students.promote', [
            'classes'       => \App\Core\Support\Collection::make(\App\Models\SchoolClass::hydrate($classes)),
            'sections'      => \App\Core\Support\Collection::make(\App\Models\Section::hydrate($sections)),
            'batches'       => \App\Core\Support\Collection::make(\App\Models\Batch::hydrate($batches)),
            'fromClassId'   => $fromClassId,
            'fromSectionId' => $fromSectionId,
            'students'      => \App\Core\Support\Collection::make(\App\Models\Student::hydrate($students)),
        ]);
    }

    public function promote(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'from_class_id'    => 'required|numeric',
            'to_class_id'      => 'required|numeric',
            'to_batch_id'      => 'required|numeric',
            'from_section_id'  => 'numeric',
            'to_section_id'    => 'numeric',
            'student_ids'      => 'array',
            'promote_all'      => 'boolean',
            'keep_roll_number' => 'boolean',
        ]);

        $result = $this->runPromote($data);
        if ($result < 0) {
            Session::getInstance()->flash('error', 'No matching students found to promote.');
            $this->redirect('/dashboard/students/promote');
            return;
        }
        if ($result === 0) {
            Session::getInstance()->flash('error', 'No students selected for promotion.');
            $this->redirect('/dashboard/students/promote');
            return;
        }

        Session::getInstance()->flash('success', "Promoted {$result} student(s) to the next class.");
        $this->redirect('/dashboard/students/promote');
    }

    public function runPromote(array $data): int
    {
        $fromClassId = (int) $data['from_class_id'];
        $toClassId = (int) $data['to_class_id'];
        $toBatchId = (int) $data['to_batch_id'];
        $fromSectionId = isset($data['from_section_id']) ? (int) $data['from_section_id'] : 0;
        $toSectionId = isset($data['to_section_id']) ? (int) $data['to_section_id'] : null;
        $promoteAll = !empty($data['promote_all']);
        $keepRoll = !empty($data['keep_roll_number']);
        $studentIds = $_POST['student_ids'] ?? $data['student_ids'] ?? [];

        $where = 's.class_id = ? AND s.status = ?';
        $params = [$fromClassId, 'active'];
        if ($fromSectionId > 0) {
            $where .= ' AND s.section_id = ?';
            $params[] = $fromSectionId;
        }
        if (!$promoteAll && !empty($studentIds)) {
            $in = implode(',', array_map('intval', $studentIds));
            $where .= " AND s.id IN ({$in})";
        }

        $students = $this->db->fetchAll(
            "SELECT s.* FROM students s WHERE {$where} ORDER BY s.roll_number ASC",
            $params
        );

        if (!$promoteAll && empty($studentIds)) {
            return 0;
        }
        if (empty($students)) {
            return -1;
        }

        $count = 0;
        foreach ($students as $student) {
            $this->db->update('students', [
                'class_id'   => $toClassId,
                'section_id' => $toSectionId,
                'batch_id'   => $toBatchId,
                'roll_number'=> $keepRoll ? ($student['roll_number'] ?? null) : null,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$student['id']]);
            $count++;
        }

        return $count;
    }
}
