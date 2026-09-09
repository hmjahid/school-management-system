<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class CertificateController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $students = $this->db->fetchAll(
            "SELECT s.id, s.admission_number, u.name, c.name as class_name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             WHERE s.status = 'active'
             ORDER BY u.name ASC LIMIT 500"
        );

        $settings = $this->db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");

        $this->view('dashboard.certificates.index', [
            'students'  => $students,
            'settings'  => $settings,
        ]);
    }

    public function generate(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'student_id'     => 'required|numeric',
            'certificate_type' => 'required|max:50',
        ]);

        $student = $this->db->fetch(
            "SELECT s.*, u.name, c.name as class_name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             WHERE s.id = ? LIMIT 1",
            [$data['student_id']]
        );

        if (!$student) {
            Session::getInstance()->flash('error', 'Student not found.');
            $this->redirect('/dashboard/certificates');
            return;
        }

        $settings = $this->db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");

        $this->view('dashboard.certificates.generate', [
            'student'          => $student,
            'certificateType'  => $data['certificate_type'],
            'settings'         => $settings,
            'generated'        => true,
        ]);
    }
}
