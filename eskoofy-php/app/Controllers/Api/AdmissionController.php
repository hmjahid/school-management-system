<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Schema;
use App\Core\Validator;

class AdmissionController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];

        if (($_GET['status'] ?? '') !== '') {
            $where .= ' AND a.status = ?';
            $params[] = $_GET['status'];
        }
        if (($_GET['payment_status'] ?? '') !== '') {
            $where .= ' AND a.payment_status = ?';
            $params[] = $_GET['payment_status'];
        }
        if (($_GET['academic_session_id'] ?? '') !== '') {
            $where .= ' AND a.academic_session_id = ?';
            $params[] = (int) $_GET['academic_session_id'];
        }
        if (($_GET['batch_id'] ?? '') !== '') {
            $where .= ' AND a.batch_id = ?';
            $params[] = (int) $_GET['batch_id'];
        }
        if (($_GET['search'] ?? '') !== '') {
            $search = (string) $_GET['search'];
            $where .= " AND (a.application_number LIKE ? OR a.first_name LIKE ? OR a.last_name LIKE ? OR a.email LIKE ? OR a.phone LIKE ?)";
            $like = "%{$search}%";
            foreach (range(1, 5) as $_) {
                $params[] = $like;
            }
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM admissions a WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT a.id, a.application_number, a.academic_session_id, a.batch_id,
                    a.first_name, a.last_name, a.email, a.phone, a.gender,
                    a.status, a.payment_status, a.submitted_at, a.admission_fee,
                    b.name as batch_name, s.name as session_name
             FROM admissions a
             LEFT JOIN batches b ON a.batch_id = b.id
             LEFT JOIN academic_sessions s ON a.academic_session_id = s.id
             WHERE {$where}
             ORDER BY a.submitted_at DESC, a.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->paginated([
            'data'         => $rows,
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'last_page'    => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function show(int $id): void
    {
        $admission = $this->db->fetch(
            "SELECT a.*, b.name as batch_name, s.name as session_name
             FROM admissions a
             LEFT JOIN batches b ON a.batch_id = b.id
             LEFT JOIN academic_sessions s ON a.academic_session_id = s.id
             WHERE a.id = ? LIMIT 1",
            [$id]
        );
        if (!$admission) {
            $this->error('Admission not found', 404);
        }

        $documents = [];
        if (Schema::hasTable('admission_documents')) {
            $documents = $this->db->fetchAll(
                "SELECT id, admission_id, type, name, file_path, file_type, file_size,
                        description, is_approved, created_at
                 FROM admission_documents
                 WHERE admission_id = ?
                 ORDER BY id DESC",
                [$id]
            );
            foreach ($documents as $i => $doc) {
                $documents[$i]['file_url'] = $this->fileUrl($doc['file_path']);
            }
        }
        $admission['documents'] = $documents;

        $this->success($admission, 'Admission retrieved');
    }

    public function store(): void
    {
        $data = $this->validateBody($this->body(), [
            'academic_session_id' => 'required|numeric',
            'batch_id'            => 'required|numeric',
            'first_name'          => 'required|max:191',
            'last_name'           => 'required|max:191',
            'gender'              => 'required|in:male,female,other',
            'date_of_birth'       => 'required|date',
            'email'               => 'required|email|max:191',
            'phone'               => 'required|max:20',
            'address'             => 'required|max:255',
            'city'                => 'required|max:191',
            'postal_code'         => 'required|max:20',
            'father_name'         => 'required|max:191',
            'father_phone'        => 'required|max:20',
            'mother_name'         => 'required|max:191',
            'mother_phone'        => 'required|max:20',
            'blood_group'         => 'max:10',
            'religion'            => 'max:191',
            'nationality'         => 'max:191',
            'state'               => 'max:191',
            'country'             => 'max:191',
            'father_occupation'   => 'max:191',
            'mother_occupation'   => 'max:191',
            'guardian_name'       => 'max:191',
            'guardian_relation'   => 'max:191',
            'guardian_phone'      => 'max:20',
            'previous_school'     => 'max:191',
            'previous_class'      => 'max:191',
            'previous_grade'      => 'max:191',
            'admission_notes'     => 'max:1000',
        ]);

        $applicationNumber = 'APP-' . date('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        $id = $this->db->insert('admissions', [
            'application_number'    => $applicationNumber,
            'academic_session_id'   => (int) $data['academic_session_id'],
            'batch_id'              => (int) $data['batch_id'],
            'first_name'            => $data['first_name'],
            'last_name'             => $data['last_name'],
            'gender'                => $data['gender'],
            'date_of_birth'         => $data['date_of_birth'],
            'blood_group'           => $data['blood_group'] ?? null,
            'religion'              => $data['religion'] ?? null,
            'nationality'           => $data['nationality'] ?? 'Bangladeshi',
            'photo'                 => null,
            'email'                 => $data['email'],
            'phone'                 => $data['phone'],
            'address'               => $data['address'],
            'city'                  => $data['city'],
            'state'                 => $data['state'] ?? null,
            'country'               => $data['country'] ?? 'Bangladesh',
            'postal_code'           => $data['postal_code'],
            'father_name'           => $data['father_name'],
            'father_phone'          => $data['father_phone'],
            'father_occupation'     => $data['father_occupation'] ?? null,
            'mother_name'           => $data['mother_name'],
            'mother_phone'          => $data['mother_phone'],
            'mother_occupation'     => $data['mother_occupation'] ?? null,
            'guardian_name'         => $data['guardian_name'] ?? null,
            'guardian_relation'     => $data['guardian_relation'] ?? null,
            'guardian_phone'        => $data['guardian_phone'] ?? null,
            'previous_school'       => $data['previous_school'] ?? null,
            'previous_class'        => $data['previous_class'] ?? null,
            'previous_grade'        => $data['previous_grade'] ?? null,
            'status'                => 'draft',
            'payment_status'        => 'unpaid',
            'admission_notes'       => $data['admission_notes'] ?? null,
            'created_at'            => date('Y-m-d H:i:s'),
            'updated_at'            => date('Y-m-d H:i:s'),
        ]);

        $this->success(['id' => $id, 'application_number' => $applicationNumber], 'Admission application submitted successfully', 201);
    }

    public function update(int $id): void
    {
        $admission = $this->db->fetch("SELECT * FROM admissions WHERE id = ? LIMIT 1", [$id]);
        if (!$admission) {
            $this->error('Admission not found', 404);
        }

        if (!in_array($admission['status'], ['draft', 'under_review'], true)) {
            $this->error('Cannot update admission with current status: ' . $admission['status'], 403);
        }

        $data = $this->validateBody($this->body(), [
            'first_name'          => 'max:191',
            'last_name'           => 'max:191',
            'gender'              => 'in:male,female,other',
            'date_of_birth'       => 'date',
            'email'               => 'email|max:191',
            'phone'               => 'max:20',
            'address'             => 'max:255',
            'city'                => 'max:191',
            'postal_code'         => 'max:20',
            'father_name'         => 'max:191',
            'father_phone'        => 'max:20',
            'mother_name'         => 'max:191',
            'mother_phone'        => 'max:20',
            'blood_group'         => 'max:10',
            'religion'            => 'max:191',
            'nationality'         => 'max:191',
            'state'               => 'max:191',
            'country'             => 'max:191',
            'father_occupation'   => 'max:191',
            'mother_occupation'   => 'max:191',
            'guardian_name'       => 'max:191',
            'guardian_relation'   => 'max:191',
            'guardian_phone'      => 'max:20',
            'previous_school'     => 'max:191',
            'previous_class'      => 'max:191',
            'previous_grade'      => 'max:191',
            'academic_session_id' => 'numeric',
            'batch_id'            => 'numeric',
            'admission_notes'     => 'max:1000',
            'payment_status'      => 'in:unpaid,submitted,verified,rejected',
        ]);

        $fields = [
            'academic_session_id', 'batch_id', 'first_name', 'last_name', 'gender',
            'date_of_birth', 'blood_group', 'religion', 'nationality', 'email',
            'phone', 'address', 'city', 'state', 'country', 'postal_code',
            'father_name', 'father_phone', 'father_occupation', 'mother_name',
            'mother_phone', 'mother_occupation', 'guardian_name', 'guardian_relation',
            'guardian_phone', 'previous_school', 'previous_class', 'previous_grade',
            'admission_notes', 'payment_status',
        ];
        $updates = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }
        if ($updates === []) {
            $this->success(['id' => $id], 'Admission updated');
        }
        $updates['updated_at'] = date('Y-m-d H:i:s');
        $this->db->update('admissions', $updates, 'id = ?', [$id]);

        $this->success(['id' => $id], 'Admission application updated successfully');
    }

    public function destroy(int $id): void
    {
        $admission = $this->db->fetch("SELECT * FROM admissions WHERE id = ? LIMIT 1", [$id]);
        if (!$admission) {
            $this->error('Admission not found', 404);
        }
        $this->db->delete('admissions', 'id = ?', [$id]);
        $this->success(['id' => $id], 'Admission deleted');
    }

    public function submit(int $id): void
    {
        $admission = $this->db->fetch("SELECT * FROM admissions WHERE id = ? LIMIT 1", [$id]);
        if (!$admission) {
            $this->error('Admission not found', 404);
        }

        if ($admission['status'] !== 'draft') {
            $this->error('Unable to submit admission with current status: ' . $admission['status'], 422);
        }

        $this->db->update('admissions', [
            'status'       => 'submitted',
            'submitted_at' => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->success($this->freshAdmission($id), 'Admission submitted successfully');
    }

    public function approve(int $id): void
    {
        $admission = $this->db->fetch("SELECT * FROM admissions WHERE id = ? LIMIT 1", [$id]);
        if (!$admission) {
            $this->error('Admission not found', 404);
        }

        if (!in_array($admission['status'], ['submitted', 'under_review', 'waitlisted'], true)) {
            $this->error('Unable to approve admission with current status: ' . $admission['status'], 422);
        }

        $data = $this->body();
        $this->db->update('admissions', [
            'status'          => 'approved',
            'approved_at'     => date('Y-m-d H:i:s'),
            'approved_by'     => \App\Core\Auth::id(),
            'admission_notes' => $data['notes'] ?? $admission['admission_notes'] ?? null,
            'admission_date'  => $admission['admission_date'] ?? date('Y-m-d'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->success($this->freshAdmission($id), 'Admission approved successfully');
    }

    public function reject(int $id): void
    {
        $admission = $this->db->fetch("SELECT * FROM admissions WHERE id = ? LIMIT 1", [$id]);
        if (!$admission) {
            $this->error('Admission not found', 404);
        }

        if (!in_array($admission['status'], ['submitted', 'under_review', 'waitlisted'], true)) {
            $this->error('Unable to reject admission with current status: ' . $admission['status'], 422);
        }

        $data = $this->validateBody($this->body(), [
            'reason' => 'required|max:1000',
        ]);

        $this->db->update('admissions', [
            'status'           => 'rejected',
            'rejection_reason' => $data['reason'],
            'rejected_at'      => date('Y-m-d H:i:s'),
            'rejected_by'      => \App\Core\Auth::id(),
            'updated_at'       => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->success($this->freshAdmission($id), 'Admission rejected');
    }

    public function enroll(int $id): void
    {
        $admission = $this->db->fetch("SELECT * FROM admissions WHERE id = ? LIMIT 1", [$id]);
        if (!$admission) {
            $this->error('Admission not found', 404);
        }

        if ($admission['status'] !== 'approved') {
            $this->error('Unable to enroll student. Admission status: ' . $admission['status'], 422);
        }

        $data = $this->validateBody($this->body(), [
            'admission_date' => 'date',
            'section_id'     => 'numeric',
            'roll_number'    => 'max:50',
            'class_id'       => 'numeric',
        ]);

        $admissionDate = $data['admission_date'] ?? date('Y-m-d');

        $userId = $this->db->insert('users', [
            'name'       => trim(($admission['first_name'] ?? '') . ' ' . ($admission['last_name'] ?? '')),
            'email'      => $admission['email'],
            'phone'      => $admission['phone'] ?? null,
            'role'       => 'student',
            'role_id'    => \App\Core\Auth::roleId('student'),
            'password'   => \App\Core\Auth::hashPassword('password'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $admissionNumber = 'STU-' . date('Ymd') . '-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT);

        $classId = (int) ($data['class_id'] ?? 0);
        if ($classId <= 0) {
            $first = $this->db->fetch("SELECT id FROM school_classes ORDER BY id ASC LIMIT 1");
            $classId = (int) ($first['id'] ?? 1);
        }

        $studentId = $this->db->insert('students', [
            'admission_id'      => $id,
            'user_id'           => $userId,
            'class_id'          => $classId,
            'section_id'        => isset($data['section_id']) ? (int) $data['section_id'] : null,
            'batch_id'          => (int) ($admission['batch_id'] ?? 1),
            'admission_number'  => $admissionNumber,
            'admission_date'    => $admissionDate,
            'roll_number'       => $data['roll_number'] ?? null,
            'first_name'        => $admission['first_name'] ?? null,
            'last_name'         => $admission['last_name'] ?? null,
            'gender'            => $admission['gender'] ?? null,
            'date_of_birth'     => $admission['date_of_birth'] ?? null,
            'email'             => $admission['email'] ?? null,
            'phone'             => $admission['phone'] ?? null,
            'address'           => $admission['address'] ?? null,
            'city'              => $admission['city'] ?? null,
            'state'             => $admission['state'] ?? null,
            'postal_code'       => $admission['postal_code'] ?? null,
            'country'           => $admission['country'] ?? 'Bangladesh',
            'father_name'       => $admission['father_name'] ?? null,
            'father_phone'      => $admission['father_phone'] ?? null,
            'mother_name'       => $admission['mother_name'] ?? null,
            'mother_phone'      => $admission['mother_phone'] ?? null,
            'status'            => 'active',
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        $this->db->update('admissions', [
            'status'         => 'enrolled',
            'admission_date' => $admissionDate,
            'enrolled_at'    => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->success([
            'admission' => $this->freshAdmission($id),
            'student'   => ['id' => $studentId, 'admission_number' => $admissionNumber],
        ], 'Student enrolled successfully');
    }

    public function filterOptions(): void
    {
        $academicSessions = [];
        $batches = [];
        $classes = [];
        $sections = [];

        if (Schema::hasTable('academic_sessions')) {
            $academicSessions = $this->db->fetchAll(
                "SELECT id, name FROM academic_sessions
                 WHERE deleted_at IS NULL AND is_active = 1
                 ORDER BY name DESC"
            );
        }
        if (Schema::hasTable('batches')) {
            $batches = $this->db->fetchAll(
                "SELECT id, name, course_id FROM batches
                 WHERE deleted_at IS NULL AND is_active = 1
                 ORDER BY name ASC"
            );
        }
        if (Schema::hasTable('school_classes')) {
            $classes = $this->db->fetchAll(
                "SELECT id, name, code FROM school_classes WHERE is_active = 1 ORDER BY name ASC"
            );
        }
        if (Schema::hasTable('sections')) {
            $sections = $this->db->fetchAll(
                "SELECT id, name, class_id FROM sections WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name ASC"
            );
        }

        $this->success([
            'academic_sessions' => $academicSessions,
            'batches'           => $batches,
            'classes'           => $classes,
            'sections'          => $sections,
            'statuses'          => [
                ['value' => 'draft',         'label' => 'Draft'],
                ['value' => 'submitted',     'label' => 'Submitted'],
                ['value' => 'under_review',  'label' => 'Under Review'],
                ['value' => 'approved',      'label' => 'Approved'],
                ['value' => 'rejected',      'label' => 'Rejected'],
                ['value' => 'waitlisted',    'label' => 'Waitlisted'],
                ['value' => 'enrolled',      'label' => 'Enrolled'],
                ['value' => 'cancelled',     'label' => 'Cancelled'],
            ],
        ], 'Admission filters retrieved');
    }

    public function uploadDocument(int $admissionId): void
    {
        $admission = $this->db->fetch("SELECT id FROM admissions WHERE id = ? LIMIT 1", [$admissionId]);
        if (!$admission) {
            $this->error('Admission not found', 404);
        }

        $type = $_POST['type'] ?? '';
        $allowed = ['transfer_certificate', 'birth_certificate', 'photo', 'mark_sheet', 'character_certificate', 'migration_certificate', 'other'];
        if (!in_array($type, $allowed, true)) {
            $this->error('Invalid document type', 422);
        }

        if (empty($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->error('file is required', 422);
        }

        $file = $_FILES['file'];
        $maxBytes = 5 * 1024 * 1024;
        if ((int) $file['size'] > $maxBytes) {
            $this->error('The file must not exceed 5MB', 422);
        }

        $dir = public_path('storage/admissions/' . $admissionId . '/documents');
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            $this->error('Unable to create upload directory', 500);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $relative = 'admissions/' . $admissionId . '/documents/' . $filename;

        if (!@move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            $this->error('Unable to store the uploaded file', 500);
        }

        $documentId = $this->db->insert('admission_documents', [
            'admission_id' => $admissionId,
            'type'         => $type,
            'name'         => $file['name'],
            'file_path'    => $relative,
            'file_type'    => $file['type'] ?? 'application/octet-stream',
            'file_size'    => (int) $file['size'],
            'description'  => $_POST['description'] ?? null,
            'is_approved'  => 0,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->success([
            'id'           => $documentId,
            'admission_id' => $admissionId,
            'type'         => $type,
            'name'         => $file['name'],
            'file_path'    => $relative,
            'file_type'    => $file['type'] ?? 'application/octet-stream',
            'file_size'    => (int) $file['size'],
            'file_url'     => $this->fileUrl($relative),
        ], 'Document uploaded successfully', 201);
    }

    public function viewDocument(int $admissionId, int $documentId): void
    {
        $document = $this->db->fetch(
            "SELECT id, admission_id, type, name, file_path, file_type, file_size, description, is_approved, created_at
             FROM admission_documents WHERE id = ? LIMIT 1",
            [$documentId]
        );

        if (!$document || (int) $document['admission_id'] !== $admissionId) {
            $this->error('Document does not belong to this admission', 404);
        }

        $document['file_url'] = $this->fileUrl($document['file_path']);

        $this->success($document, 'Document retrieved');
    }

    public function deleteDocument(int $admissionId, int $documentId): void
    {
        $document = $this->db->fetch(
            "SELECT id, admission_id, file_path FROM admission_documents WHERE id = ? LIMIT 1",
            [$documentId]
        );

        if (!$document || (int) $document['admission_id'] !== $admissionId) {
            $this->error('Document does not belong to this admission', 404);
        }

        $full = public_path('storage/' . ltrim((string) $document['file_path'], '/'));
        if (is_file($full)) {
            @unlink($full);
        }

        $this->db->delete('admission_documents', 'id = ?', [$documentId]);

        $this->success(['id' => $documentId], 'Document deleted successfully');
    }

    public function status(int|string $reference): void
    {
        if ($reference === '') {
            $this->error('application_number is required', 422);
        }

        if (is_numeric($reference)) {
            $admission = $this->db->fetch(
                "SELECT application_number, first_name, last_name, status, payment_status, submitted_at, admission_date
                 FROM admissions WHERE id = ? LIMIT 1",
                [(int) $reference]
            );
        } else {
            $admission = $this->db->fetch(
                "SELECT application_number, first_name, last_name, status, payment_status, submitted_at, admission_date
                 FROM admissions WHERE application_number = ? LIMIT 1",
                [$reference]
            );
        }

        if (!$admission) {
            $this->error('Admission not found', 404);
        }

        $this->success($admission, 'Admission status retrieved');
    }

    public function export(): void
    {
        $where = '1=1';
        $params = [];
        if (($_GET['status'] ?? '') !== '') {
            $where .= ' AND a.status = ?';
            $params[] = $_GET['status'];
        }

        $rows = $this->db->fetchAll(
            "SELECT a.application_number, a.first_name, a.last_name, a.email, a.phone,
                    a.status, a.payment_status, a.submitted_at, b.name as batch_name
             FROM admissions a
             LEFT JOIN batches b ON a.batch_id = b.id
             WHERE {$where}
             ORDER BY a.submitted_at DESC",
            $params
        );

        $this->streamCsv('admissions-export-' . date('Y-m-d-H-i-s') . '.csv', $rows);
    }

    public function import(): void
    {
        if (empty($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->error('file is required', 422);
        }

        $handle = fopen($_FILES['file']['tmp_name'], 'r');
        if ($handle === false) {
            $this->error('Unable to read the uploaded file', 422);
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            $this->error('The uploaded CSV file is empty', 422);
        }

        $header = array_map('trim', $header);
        $imported = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === []) {
                continue;
            }
            $record = array_combine($header, $row);
            $record = array_map(static fn ($v) => $v === '' ? null : $v, $record);

            $firstName = (string) ($record['first_name'] ?? '');
            $email = (string) ($record['email'] ?? '');
            if ($firstName === '' || $email === '') {
                continue;
            }

            $session = (int) ($record['academic_session_id'] ?? 0);
            if ($session <= 0) {
                $current = $this->db->fetch("SELECT id FROM academic_sessions WHERE is_current = 1 AND deleted_at IS NULL LIMIT 1");
                $session = (int) ($current['id'] ?? 0);
            }
            $batch = (int) ($record['batch_id'] ?? 0);

            $applicationNumber = (string) ($record['application_number'] ?? '');
            if ($applicationNumber === '') {
                $applicationNumber = 'APP-' . date('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            }

            $this->db->insert('admissions', [
                'application_number'    => $applicationNumber,
                'academic_session_id'   => $session ?: 1,
                'batch_id'              => $batch ?: 1,
                'first_name'            => $firstName,
                'last_name'             => (string) ($record['last_name'] ?? ''),
                'gender'                => in_array($record['gender'] ?? '', ['male', 'female', 'other'], true) ? $record['gender'] : 'other',
                'date_of_birth'         => (string) ($record['date_of_birth'] ?? date('Y-m-d')),
                'email'                 => $email,
                'phone'                 => (string) ($record['phone'] ?? ''),
                'address'               => (string) ($record['address'] ?? ''),
                'city'                  => (string) ($record['city'] ?? ''),
                'postal_code'           => (string) ($record['postal_code'] ?? ''),
                'father_name'           => (string) ($record['father_name'] ?? ''),
                'father_phone'          => (string) ($record['father_phone'] ?? ''),
                'mother_name'           => (string) ($record['mother_name'] ?? ''),
                'mother_phone'          => (string) ($record['mother_phone'] ?? ''),
                'status'                => (string) ($record['status'] ?? 'draft'),
                'payment_status'        => (string) ($record['payment_status'] ?? 'unpaid'),
                'created_at'            => date('Y-m-d H:i:s'),
                'updated_at'            => date('Y-m-d H:i:s'),
            ]);
            $imported++;
        }
        fclose($handle);

        $this->success(['imported' => $imported], $imported . ' admission(s) imported successfully');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function body(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw !== false && $raw !== '') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                return $json;
            }
        }
        return $_POST;
    }

    private function validateBody(array $data, array $rules): array
    {
        $validator = new Validator($data, $rules);
        if ($validator->fails()) {
            $this->error('Validation failed', 422, $validator->errors());
        }
        return $validator->validated();
    }

    private function freshAdmission(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM admissions WHERE id = ? LIMIT 1", [$id]);
    }

    private function fileUrl(string $path): string
    {
        return url('storage/' . ltrim($path, '/'));
    }

    private function streamCsv(string $filename, array $rows): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        if (count($rows) > 0) {
            fputcsv($out, array_keys($rows[0]));
        }
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}