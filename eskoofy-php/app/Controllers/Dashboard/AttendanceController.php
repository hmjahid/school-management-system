<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class AttendanceController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $date = $_GET['date'] ?? date('Y-m-d');
        $classId = (int) ($_GET['class_id'] ?? 0);
        $sectionId = (int) ($_GET['section_id'] ?? 0);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $where = "a.date = ?";
        $params = [$date];

        if ($classId > 0) {
            $where .= ' AND a.school_class_id = ?';
            $params[] = $classId;
        }
        if ($sectionId > 0) {
            $where .= ' AND a.section_id = ?';
            $params[] = $sectionId;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM attendances a WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT a.*, s.admission_number, u.name as student_name, c.name as class_name, sec.name as section_name
             FROM attendances a
             LEFT JOIN students s ON a.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             WHERE {$where}
             ORDER BY c.name ASC, u.name ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $sections = $this->db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC");

        $presentCount = $this->db->fetch(
            "SELECT COUNT(*) as cnt FROM attendances a WHERE {$where} AND a.status IN ('present', 'late', 'half_day')",
            $params
        )['cnt'] ?? 0;

        $this->view('dashboard.attendance.index', [
            'rows'         => $rows,
            'records' => $rows,
            'total'        => $total,
            'page'         => $page,
            'perPage'      => $perPage,
            'lastPage'     => max(1, (int) ceil($total / $perPage)),
            'date'         => $date,
            'classId'      => $classId,
            'sectionId'    => $sectionId,
            'classes'      => $classes,
            'sections'     => $sections,
            'presentCount' => (int) $presentCount,
        ]);
    }

    public function mark(): void
    {
        Auth::requireAuth();
        $classId = (int) ($_GET['class_id'] ?? 0);
        $sectionId = (int) ($_GET['section_id'] ?? 0);
        $date = $_GET['date'] ?? date('Y-m-d');

        $students = [];
        if ($classId > 0) {
            $where = 's.class_id = ? AND s.status = \'active\'';
            $params = [$classId];
            if ($sectionId > 0) {
                $where .= ' AND s.section_id = ?';
                $params[] = $sectionId;
            }
            $students = $this->db->fetchAll(
                "SELECT s.id, u.name, s.roll_number
                 FROM students s
                 LEFT JOIN users u ON s.user_id = u.id
                 WHERE {$where}
                 ORDER BY s.roll_number ASC, u.name ASC",
                $params
            );
        }

        $existing = [];
        if ($classId > 0) {
            $existingRows = $this->db->fetchAll(
                "SELECT * FROM attendances WHERE date = ? AND school_class_id = ?" .
                ($sectionId > 0 ? ' AND section_id = ?' : ''),
                $sectionId > 0 ? [$date, $classId, $sectionId] : [$date, $classId]
            );
            foreach ($existingRows as $row) {
                $existing[$row['student_id']] = $row;
            }
        }

        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $sections = $this->db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC");

        $this->view('dashboard.attendance.mark', [
            'students'   => $students,
            'existing'   => $existing,
            'classes'    => $classes,
            'sections'   => $sections,
            'classId'    => $classId,
            'sectionId'  => $sectionId,
            'date'       => $date,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'date'        => 'required',
            'class_id'    => 'required|numeric',
            'section_id'  => 'numeric',
            'status'      => 'required',
        ]);

        $date = $data['date'];
        $classId = $data['class_id'];
        $sectionId = $data['section_id'] ?? null;
        $userId = Auth::id();
        $statuses = $_POST['student_status'] ?? [];
        $remarks = $_POST['student_remarks'] ?? [];

        $count = 0;
        foreach ($statuses as $studentId => $status) {
            if (!in_array($status, ['present', 'absent', 'late', 'half_day', 'excused'])) {
                continue;
            }

            $existing = $this->db->fetch(
                "SELECT id FROM attendances WHERE student_id = ? AND date = ? AND type = 'daily' LIMIT 1",
                [$studentId, $date]
            );

            if ($existing) {
                $this->db->update('attendances', [
                    'status'     => $status,
                    'remarks'    => $remarks[$studentId] ?? null,
                    'recorded_by'=> $userId,
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$existing['id']]);
            } else {
                $this->db->insert('attendances', [
                    'student_id' => $studentId,
                    'date'       => $date,
                    'type'       => 'daily',
                    'status'     => $status,
                    'school_class_id' => $classId,
                    'section_id' => $sectionId,
                    'remarks'    => $remarks[$studentId] ?? null,
                    'recorded_by'=> $userId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
            $count++;
        }

        Session::getInstance()->flash('success', "Saved {$count} attendance records.");
        $this->redirect("/dashboard/attendance?date={$date}&class_id={$classId}" .
            ($sectionId ? "&section_id={$sectionId}" : ''));
    }
}
