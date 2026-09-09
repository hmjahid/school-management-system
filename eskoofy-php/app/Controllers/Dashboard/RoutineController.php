<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class RoutineController extends Controller
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
        $sectionId = (int) ($_GET['section_id'] ?? 0);

        $where = '1=1';
        $params = [];
        if ($classId > 0) {
            $where .= ' AND r.class_id = ?';
            $params[] = $classId;
        }
        if ($sectionId > 0) {
            $where .= ' AND r.section_id = ?';
            $params[] = $sectionId;
        }

        $rows = $this->db->fetchAll(
            "SELECT r.*, c.name as class_name, sec.name as section_name, sub.name as subject_name,
                    t.name as teacher_name
             FROM routines r
             LEFT JOIN school_classes c ON r.class_id = c.id
             LEFT JOIN sections sec ON r.section_id = sec.id
             LEFT JOIN subjects sub ON r.subject_id = sub.id
             LEFT JOIN teachers te ON r.teacher_id = te.id
             LEFT JOIN users t ON te.user_id = t.id
             WHERE {$where}
             ORDER BY r.day_of_week ASC, r.start_time ASC",
            $params
        );

        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $sections = $this->db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC");
        $subjects = $this->db->fetchAll("SELECT id, name FROM subjects ORDER BY name ASC");
        $teachers = $this->db->fetchAll(
            "SELECT t.id, u.name FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.status = 'active' ORDER BY u.name ASC"
        );

        $this->view('dashboard.routines.index', [
            'rows'      => $rows,
            'periods' => $rows,
            'classes'   => $classes,
            'sections'  => $sections,
            'subjects'  => $subjects,
            'teachers'  => $teachers,
            'classId'   => $classId,
            'sectionId' => $sectionId,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'class_id'    => 'required|numeric',
            'section_id'  => 'numeric',
            'subject_id'  => 'required|numeric',
            'teacher_id'  => 'numeric',
            'day_of_week' => 'required|max:20',
            'start_time'  => 'required|max:10',
            'end_time'    => 'required|max:10',
            'room'        => 'max:50',
        ]);

        $this->db->insert('routines', [
            'class_id'    => $data['class_id'],
            'section_id'  => $data['section_id'] ?? null,
            'subject_id'  => $data['subject_id'],
            'teacher_id'  => $data['teacher_id'] ?? null,
            'day_of_week' => $data['day_of_week'],
            'start_time'  => $data['start_time'],
            'end_time'    => $data['end_time'],
            'room'        => $data['room'] ?? null,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Routine entry added.');
        $this->redirect("/dashboard/routines?class_id={$data['class_id']}");
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $routine = $this->db->fetch("SELECT * FROM routines WHERE id = ? LIMIT 1", [$id]);
        if (!$routine) {
            Session::getInstance()->flash('error', 'Routine entry not found.');
            $this->redirect('/dashboard/routines');
            return;
        }

        $data = $this->validate([
            'class_id'    => 'required|numeric',
            'section_id'  => 'numeric',
            'subject_id'  => 'required|numeric',
            'teacher_id'  => 'numeric',
            'day_of_week' => 'required|max:20',
            'start_time'  => 'required|max:10',
            'end_time'    => 'required|max:10',
            'room'        => 'max:50',
        ]);

        $this->db->update('routines', [
            'class_id'    => $data['class_id'],
            'section_id'  => $data['section_id'] ?? null,
            'subject_id'  => $data['subject_id'],
            'teacher_id'  => $data['teacher_id'] ?? null,
            'day_of_week' => $data['day_of_week'],
            'start_time'  => $data['start_time'],
            'end_time'    => $data['end_time'],
            'room'        => $data['room'] ?? null,
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Routine entry updated.');
        $this->redirect("/dashboard/routines?class_id={$data['class_id']}");
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $routine = $this->db->fetch("SELECT * FROM routines WHERE id = ? LIMIT 1", [$id]);
        $classId = $routine['class_id'] ?? 0;

        $this->db->delete('routines', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Routine entry removed.');
        $this->redirect("/dashboard/routines?class_id={$classId}");
    }
}
