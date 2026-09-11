<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class AdmissionController extends Controller
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
            "SELECT a.*, b.name as batch_name
             FROM admissions a
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
            "SELECT a.*, b.name as batch_name, s.name as session_name
             FROM admissions a
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
            'role_id'     => \App\Core\Auth::roleId('student'),
            'password'   => Auth::hashPassword('password'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $admissionNumber = 'STU-' . date('Ymd') . '-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT);

        $classId = (int) ($admission['class_id'] ?? 0) ?: (int) ($this->db->fetch("SELECT id FROM school_classes ORDER BY id ASC LIMIT 1")['id'] ?? 1);

        $studentId = $this->db->insert('students', [
            'user_id'          => $userId,
            'admission_number' => $admissionNumber,
            'class_id'         => $classId,
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

    public function toggleOpen(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'is_open'     => 'required|boolean',
            'admission_fee' => 'numeric',
            'display_year'  => 'max:9',
        ]);

        $isOpen = !empty($data['is_open']);
        $this->saveAdmissionSettings($data);

        Session::getInstance()->flash('success', $isOpen ? 'Admissions opened for new applications.' : 'Admissions closed.');
        $this->redirect('/dashboard/admissions');
    }

    public function saveAdmissionSettings(array $data): void
    {
        $settings = $this->db->fetch("SELECT * FROM admission_settings ORDER BY id DESC LIMIT 1");

        if ($settings) {
            $this->db->update('admission_settings', [
                'is_open'       => !empty($data['is_open']) ? 1 : 0,
                'display_year'  => $data['display_year'] ?? $settings['display_year'] ?? null,
                'admission_fee' => isset($data['admission_fee']) ? $data['admission_fee'] : ($settings['admission_fee'] ?? 0),
                'payment_number'=> $_POST['payment_number'] ?? $settings['payment_number'] ?? null,
                'closed_message_en' => $_POST['closed_message_en'] ?? $settings['closed_message_en'] ?? null,
                'closed_message_bn' => $_POST['closed_message_bn'] ?? $settings['closed_message_bn'] ?? null,
                'payment_instructions_en' => $_POST['payment_instructions_en'] ?? $settings['payment_instructions_en'] ?? null,
                'payment_instructions_bn' => $_POST['payment_instructions_bn'] ?? $settings['payment_instructions_bn'] ?? null,
                'notice_en'     => $_POST['notice_en'] ?? $settings['notice_en'] ?? null,
                'notice_bn'     => $_POST['notice_bn'] ?? $settings['notice_bn'] ?? null,
                'bar_title_en'  => $_POST['bar_title_en'] ?? $settings['bar_title_en'] ?? null,
                'bar_title_bn'  => $_POST['bar_title_bn'] ?? $settings['bar_title_bn'] ?? null,
                'updated_at'    => date('Y-m-d H:i:s'),
            ], 'id = ?', [$settings['id']]);
        } else {
            $this->db->insert('admission_settings', [
                'is_open'       => !empty($data['is_open']) ? 1 : 0,
                'display_year'  => $data['display_year'] ?? null,
                'admission_fee' => $data['admission_fee'] ?? 0,
                'payment_number'=> $_POST['payment_number'] ?? null,
                'closed_message_en' => $_POST['closed_message_en'] ?? null,
                'closed_message_bn' => $_POST['closed_message_bn'] ?? null,
                'payment_instructions_en' => $_POST['payment_instructions_en'] ?? null,
                'payment_instructions_bn' => $_POST['payment_instructions_bn'] ?? null,
                'notice_en'     => $_POST['notice_en'] ?? null,
                'notice_bn'     => $_POST['notice_bn'] ?? null,
                'bar_title_en'  => $_POST['bar_title_en'] ?? null,
                'bar_title_bn'  => $_POST['bar_title_bn'] ?? null,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function updateStatus(int $id): void
    {
        Auth::requireAuth();
        $admission = $this->db->fetch("SELECT * FROM admissions WHERE id = ? LIMIT 1", [$id]);
        if (!$admission) {
            Session::getInstance()->flash('error', 'Admission not found.');
            $this->redirect('/dashboard/admissions');
            return;
        }

        $status = $_POST['status'] ?? '';
        $allowed = ['under_review', 'approved', 'rejected', 'waitlisted', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            Session::getInstance()->flash('error', 'Invalid status.');
            $this->redirect("/dashboard/admissions/{$id}");
            return;
        }

        $notes = $_POST['admission_notes'] ?? null;
        $rejectionReason = $_POST['rejection_reason'] ?? null;

        if ($status === 'approved') {
            if (!in_array($admission['status'], ['submitted', 'under_review'], true)) {
                Session::getInstance()->flash('error', 'Unable to update status from current state.');
                $this->redirect("/dashboard/admissions/{$id}");
                return;
            }
            $this->db->update('admissions', [
                'status'         => 'approved',
                'admission_date' => date('Y-m-d'),
                'admission_notes'=> $notes,
                'approved_by'    => Auth::id(),
                'approved_at'    => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);
        } elseif ($status === 'rejected') {
            if (!in_array($admission['status'], ['submitted', 'under_review'], true)) {
                Session::getInstance()->flash('error', 'Unable to update status from current state.');
                $this->redirect("/dashboard/admissions/{$id}");
                return;
            }
            $this->db->update('admissions', [
                'status'           => 'rejected',
                'rejection_reason' => $rejectionReason ?? 'Not specified',
                'rejected_by'      => Auth::id(),
                'rejected_at'      => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);
        } else {
            $update = [
                'status'     => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($status === 'cancelled') {
                $update['cancelled_at'] = date('Y-m-d H:i:s');
            }
            $this->db->update('admissions', $update, 'id = ?', [$id]);
        }

        Session::getInstance()->flash('success', 'Status updated.');
        $this->redirect("/dashboard/admissions/{$id}");
    }

    public function verifyPayment(int $id): void
    {
        Auth::requireAuth();
        $admission = $this->db->fetch("SELECT * FROM admissions WHERE id = ? LIMIT 1", [$id]);
        if (!$admission) {
            Session::getInstance()->flash('error', 'Admission not found.');
            $this->redirect('/dashboard/admissions');
            return;
        }

        if ($admission['payment_status'] === 'verified') {
            Session::getInstance()->flash('success', 'Payment is already verified.');
            $this->redirect("/dashboard/admissions/{$id}");
            return;
        }
        if (empty($admission['transaction_id'])) {
            Session::getInstance()->flash('error', 'No transaction ID was submitted by the applicant.');
            $this->redirect("/dashboard/admissions/{$id}");
            return;
        }

        $this->db->update('admissions', [
            'payment_status' => 'verified',
            'verified_at'    => date('Y-m-d H:i:s'),
            'verified_by'    => Auth::id(),
            'updated_at'     => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Payment verified.');
        $this->redirect("/dashboard/admissions/{$id}");
    }
}
