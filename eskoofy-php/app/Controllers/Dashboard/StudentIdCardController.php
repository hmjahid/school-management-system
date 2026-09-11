<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class StudentIdCardController extends Controller
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
        $where = "s.status = 'active'";
        $params = [];
        if ($search !== '') {
            $where .= " AND (u.name LIKE ? OR s.admission_number LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }

        $students = $this->db->fetchAll(
            "SELECT s.id, s.admission_number, u.name, u.photo, c.name as class_name, s.gender
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             WHERE {$where}
             ORDER BY u.name ASC LIMIT 500",
            $params
        );

        $settings = $this->db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");

        $this->view('dashboard.id-cards.index', [
            'students'  => $students,
            'settings'  => $settings,
            'search'    => $search,
        ]);
    }

    public function generate(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'student_id' => 'required|numeric',
        ]);

        $student = $this->db->fetch(
            "SELECT s.*, u.name, u.photo, c.name as class_name, sec.name as section_name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             WHERE s.id = ? LIMIT 1",
            [$data['student_id']]
        );

        if (!$student) {
            Session::getInstance()->flash('error', 'Student not found.');
            $this->redirect('/dashboard/id-cards');
            return;
        }

        $settings = $this->db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");

        $this->view('dashboard.id-cards.generate', [
            'student'   => $student,
            'settings'  => $settings,
            'generated' => true,
        ]);
    }
}
