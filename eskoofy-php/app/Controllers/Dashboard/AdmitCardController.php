<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class AdmitCardController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $exams = $this->db->fetchAll(
            "SELECT * FROM exams WHERE is_published = 1 ORDER BY exam_date DESC LIMIT 50"
        );

        $students = $this->db->fetchAll(
            "SELECT s.id, s.admission_number, u.name, c.name as class_name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             WHERE s.status = 'active'
             ORDER BY u.name ASC LIMIT 500"
        );

        $settings = $this->db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");

        $this->view('dashboard.admit-cards.index', [
            'exams'    => $exams,
            'students' => $students,
            'settings' => $settings,
        ]);
    }

    public function generate(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'student_id' => 'required|numeric',
            'exam_id'    => 'required|numeric',
        ]);

        $student = $this->db->fetch(
            "SELECT s.*, u.name, c.name as class_name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             WHERE s.id = ? LIMIT 1",
            [$data['student_id']]
        );

        $exam = $this->db->fetch("SELECT * FROM exams WHERE id = ? LIMIT 1", [$data['exam_id']]);
        $settings = $this->db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");

        if (!$student || !$exam) {
            Session::getInstance()->flash('error', 'Student or exam not found.');
            $this->redirect('/dashboard/admit-cards');
            return;
        }

        $this->view('dashboard.admit-cards.generate', [
            'student'   => $student,
            'exam'      => $exam,
            'settings'  => $settings,
            'generated' => true,
        ]);
    }
}
