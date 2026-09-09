<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class AdmissionController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $paymentStatus = $_GET['payment_status'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND (a.application_number LIKE ? OR a.first_name LIKE ? OR a.last_name LIKE ? OR a.email LIKE ? OR a.phone LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($status !== '') {
            $where .= ' AND a.status = ?';
            $params[] = $status;
        }
        if ($paymentStatus !== '') {
            $where .= ' AND a.payment_status = ?';
            $params[] = $paymentStatus;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM admissions a WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT a.*, c.name as class_name, b.name as batch_name
             FROM admissions a
             LEFT JOIN school_classes c ON a.class_id = c.id
             LEFT JOIN batches b ON a.batch_id = b.id
             WHERE {$where}
             ORDER BY a.submitted_at DESC, a.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $settings = $this->db->fetch("SELECT * FROM admission_settings ORDER BY id DESC LIMIT 1");

        $this->view('dashboard.admissions.index', [
            'rows'      => $rows,
            'admissions' => $rows,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'lastPage'  => max(1, (int) ceil($total / $perPage)),
            'search'    => $search,
            'status'    => $status,
            'paymentStatus' => $paymentStatus,
            'settings'  => $settings,
        ]);
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $admission = $this->db->fetch(
            "SELECT a.*, c.name as class_name, b.name as batch_name, s.name as session_name
             FROM admissions a
             LEFT JOIN school_classes c ON a.class_id = c.id
             LEFT JOIN batches b ON a.batch_id = b.id
             LEFT JOIN academic_sessions s ON a.academic_session_id = s.id
             WHERE a.id = ? LIMIT 1",
            [$id]
        );

        if (!$admission) {
            Session::getInstance()->flash('error', 'Admission not found.');
            $this->redirect('/dashboard/admissions');
            return;
        }

        $this->view('dashboard.admissions.show', ['admission' => $admission]);
    }

    public function approve(int $id): void
    {
        Auth::requireAuth();
        $admission = $this->db->fetch("SELECT * FROM admissions WHERE id = ? LIMIT 1", [$id]);
        if (!$admission) {
            Session::getInstance()->flash('error', 'Admission not found.');
            $this->redirect('/dashboard/admissions');
            return;
        }

        $this->db->update('admissions', [
            'status'       => 'approved',
            'approved_at'  => date('Y-m-d H:i:s'),
            'approved_by'  => Auth::id(),
            'updated_at'   => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Admission approved.');
        $this->redirect("/dashboard/admissions/{$id}");
    }

    public function reject(int $id): void
    {
        Auth::requireAuth();
        $admission = $this->db->fetch("SELECT * FROM admissions WHERE id = ? LIMIT 1", [$id]);
        if (!$admission) {
            Session::getInstance()->flash('error', 'Admission not found.');
            $this->redirect('/dashboard/admissions');
            return;
        }

        $reason = $_POST['rejection_reason'] ?? 'Not specified';

        $this->db->update('admissions', [
            'status'           => 'rejected',
            'rejection_reason' => $reason,
            'updated_at'       => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Admission rejected.');
        $this->redirect("/dashboard/admissions/{$id}");
    }

    public function enroll(int $id): void
    {
        Auth::requireAuth();
        $admission = $this->db->fetch("SELECT * FROM admissions WHERE id = ? LIMIT 1", [$id]);
        if (!$admission) {
            Session::getInstance()->flash('error', 'Admission not found.');
            $this->redirect('/dashboard/admissions');
            return;
        }

        if ($admission['status'] !== 'approved') {
            Session::getInstance()->flash('error', 'Only approved admissions can be enrolled.');
            $this->redirect("/dashboard/admissions/{$id}");
            return;
        }

        $userId = $this->db->insert('users', [
            'name'       => $admission['first_name'] . ' ' . $admission['last_name'],
            'email'      => $admission['email'],
            'phone'      => $admission['phone'] ?? null,
            'role'       => 'student',
            'password'   => Auth::hashPassword('password'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $admissionNumber = 'STU-' . date('Ymd') . '-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT);

        $studentId = $this->db->insert('students', [
            'user_id'          => $userId,
            'admission_number' => $admissionNumber,
            'class_id'         => $admission['class_id'],
            'batch_id'         => $admission['batch_id'] ?? 1,
            'gender'           => $admission['gender'] ?? null,
            'date_of_birth'    => $admission['date_of_birth'] ?? null,
            'address'          => $admission['address'] ?? null,
            'status'           => 'active',
            'admission_date'   => date('Y-m-d'),
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->db->update('admissions', [
            'status'     => 'enrolled',
            'student_id' => $studentId,
            'enrolled_at'=> date('Y-m-d H:i:s'),
            'enrolled_by'=> Auth::id(),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Student enrolled successfully. Admission number: ' . $admissionNumber);
        $this->redirect("/dashboard/admissions/{$id}");
    }
}
