<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class CertificateController extends Controller
{
    private DatabaseInterface $db;

    private const TYPES = ['transfer', 'character', 'achievement', 'participation', 'completion'];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $search = $_GET['search'] ?? '';
        $type = $_GET['type'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND (c.certificate_number LIKE ? OR EXISTS (SELECT 1 FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = c.student_id AND u.name LIKE ?))";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }
        if ($type !== '') {
            $where .= ' AND c.certificate_type = ?';
            $params[] = $type;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM certificates c WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT c.*, u.name as student_name
             FROM certificates c
             LEFT JOIN students s ON c.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             WHERE {$where}
             ORDER BY c.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('dashboard.certificates.index', [
            'rows'     => $rows,
            'types'    => self::TYPES,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'search'   => $search,
            'type'     => $type,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $students = $this->db->fetchAll(
            "SELECT s.id, s.admission_number, u.name
             FROM students s JOIN users u ON s.user_id = u.id ORDER BY s.id DESC LIMIT 500"
        );

        $this->view('dashboard.certificates.create', [
            'students' => $students,
            'types'    => self::TYPES,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'student_id'       => 'required|numeric',
            'certificate_type' => 'required',
            'issue_date'       => 'required',
            'status'           => 'max:20',
            'body'             => 'max:5000',
        ]);

        $this->storeOne(
            (int) $data['student_id'],
            $data['certificate_type'],
            $data['issue_date'],
            $data['body'] ?? null,
            $data['status'] ?? 'draft'
        );

        Session::getInstance()->flash('success', 'Certificate generated.');
        $this->redirect('/dashboard/certificates');
    }

    public function generate(): void
    {
        $this->store();
    }

    public function storeOne(int $studentId, string $certificateType, string $issueDate, ?string $body, string $status): bool
    {
        $student = $this->db->fetch(
            "SELECT s.*, u.name FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = ? LIMIT 1",
            [$studentId]
        );
        if (!$student) {
            return false;
        }

        $year = date('Y');
        $count = (int) ($this->db->fetch(
            "SELECT COUNT(*) as c FROM certificates WHERE YEAR(created_at) = ?", [$year]
        )['c'] ?? 0);
        $number = sprintf('CERT-%s-%04d', $year, $count + 1);

        $userId = Auth::id();
        $this->db->insert('certificates', [
            'student_id'        => $studentId,
            'certificate_type'  => in_array($certificateType, self::TYPES, true) ? $certificateType : 'character',
            'issue_date'        => $issueDate,
            'certificate_number'=> $number,
            'name'              => ucfirst($certificateType) . ' certificate for ' . ($student['name'] ?? 'Student'),
            'template'          => '[]',
            'body'              => ($body !== null && $body !== '') ? json_encode([$body]) : null,
            'status'            => in_array($status, ['draft', 'issued', 'revoked'], true) ? $status : 'draft',
            'created_by'        => $userId,
            'generated_by'      => $userId,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $cert = $this->loadCertificate($id);
        if (!$cert) {
            Session::getInstance()->flash('error', 'Certificate not found.');
            $this->redirect('/dashboard/certificates');
            return;
        }
        $this->view('dashboard.certificates.show', ['cert' => $cert]);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $cert = $this->loadCertificate($id);
        if (!$cert) {
            Session::getInstance()->flash('error', 'Certificate not found.');
            $this->redirect('/dashboard/certificates');
            return;
        }
        $students = $this->db->fetchAll(
            "SELECT s.id, s.admission_number, u.name
             FROM students s JOIN users u ON s.user_id = u.id ORDER BY s.id DESC LIMIT 500"
        );
        $this->view('dashboard.certificates.edit', [
            'cert'     => $cert,
            'students' => $students,
            'types'    => self::TYPES,
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $cert = $this->loadCertificate($id);
        if (!$cert) {
            Session::getInstance()->flash('error', 'Certificate not found.');
            $this->redirect('/dashboard/certificates');
            return;
        }

        $data = $this->validate([
            'student_id'       => 'required|numeric',
            'certificate_type' => 'required',
            'issue_date'       => 'required',
            'status'           => 'required',
            'body'             => 'max:5000',
        ]);

        $this->db->update('certificates', [
            'student_id'        => (int) $data['student_id'],
            'certificate_type'  => in_array($data['certificate_type'], self::TYPES, true) ? $data['certificate_type'] : 'character',
            'issue_date'        => $data['issue_date'],
            'status'            => in_array($data['status'], ['draft', 'issued', 'revoked'], true) ? $data['status'] : 'draft',
            'body'              => (($data['body'] ?? '') !== '') ? json_encode([$data['body']]) : null,
            'updated_at'        => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Certificate updated.');
        $this->redirect("/dashboard/certificates/{$id}");
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('certificates', 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Certificate removed.');
        $this->redirect('/dashboard/certificates');
    }

    public function print(int $id): void
    {
        Auth::requireAuth();
        $cert = $this->loadCertificate($id);
        if (!$cert) {
            Session::getInstance()->flash('error', 'Certificate not found.');
            $this->redirect('/dashboard/certificates');
            return;
        }
        $settings = $this->db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");
        $this->view('dashboard.certificates.print', [
            'cert'     => $cert,
            'settings' => $settings,
        ]);
    }

    private function loadCertificate(int $id): ?array
    {
        $cert = $this->db->fetch(
            "SELECT c.*, u.name as student_name, c2.name as class_name, sec.name as section_name,
                    s.admission_number, s.roll_number
             FROM certificates c
             LEFT JOIN students s ON c.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c2 ON s.class_id = c2.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             WHERE c.id = ? LIMIT 1",
            [$id]
        );
        if (!$cert) {
            return null;
        }
        $cert['body'] = isset($cert['body']) && $cert['body'] !== '' ? json_decode((string) $cert['body'], true) : null;
        return $cert;
    }
}