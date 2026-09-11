<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;

class AdmissionSubmitter
{
    public static function submitPublicApplication(array $data, array $files = []): int
    {
        $db = Database::getInstance();

        $academicSessionId = (int) ($data['academic_session_id'] ?? 0);
        if ($academicSessionId <= 0) {
            $session = $db->fetch("SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1");
            $academicSessionId = (int) ($session['id'] ?? 1);
        }

        $batchId = (int) ($data['batch_id'] ?? 0);
        if ($batchId <= 0) {
            $batch = $db->fetch("SELECT id FROM batches ORDER BY id DESC LIMIT 1");
            $batchId = (int) ($batch['id'] ?? 1);
        }

        $appNumber = $data['application_number'] ?? null;
        if (!$appNumber) {
            $appNumber = 'ADM-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        }

        $userId = null;
        if (!empty($data['email'])) {
            $existingUser = $db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$data['email']]);
            if ($existingUser) {
                $userId = (int) $existingUser['id'];
            }
        }

        if (!$userId) {
            $userId = $db->insert('users', [
                'name'       => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
                'email'      => $data['email'],
                'phone'      => $data['phone'] ?? null,
                'role'       => 'student',
            'role_id'     => \App\Core\Auth::roleId('student'),
                'password'   => Auth::hashPassword(bin2hex(random_bytes(8))),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $photoPath = self::handleFile($files['photo'] ?? null, 'admissions/photos');

        $admissionId = $db->insert('admissions', [
            'application_number' => $appNumber,
            'academic_session_id'=> $academicSessionId,
            'batch_id'           => $batchId,
            'user_id'            => $userId,
            'first_name'         => $data['first_name'] ?? '',
            'last_name'          => $data['last_name'] ?? '',
            'gender'             => $data['gender'] ?? null,
            'date_of_birth'      => $data['date_of_birth'] ?? null,
            'blood_group'        => $data['blood_group'] ?? null,
            'religion'           => $data['religion'] ?? null,
            'nationality'        => $data['nationality'] ?? 'Bangladeshi',
            'photo'              => $photoPath,
            'email'              => $data['email'] ?? '',
            'phone'              => $data['phone'] ?? '',
            'address'            => $data['address'] ?? '',
            'city'               => $data['city'] ?? null,
            'state'              => $data['state'] ?? null,
            'country'            => $data['country'] ?? 'Bangladesh',
            'postal_code'        => $data['postal_code'] ?? null,
            'father_name'        => $data['father_name'] ?? '',
            'father_phone'       => $data['father_phone'] ?? '',
            'father_occupation'  => $data['father_occupation'] ?? null,
            'mother_name'        => $data['mother_name'] ?? '',
            'mother_phone'       => $data['mother_phone'] ?? null,
            'mother_occupation'  => $data['mother_occupation'] ?? null,
            'guardian_name'      => $data['guardian_name'] ?? null,
            'guardian_relation'  => $data['guardian_relation'] ?? null,
            'guardian_phone'     => $data['guardian_phone'] ?? null,
            'previous_school'    => $data['previous_school'] ?? null,
            'previous_class'     => $data['previous_class'] ?? null,
            'previous_grade'     => $data['previous_grade'] ?? null,
            'status'             => 'submitted',
            'submitted_at'       => date('Y-m-d H:i:s'),
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        $documents = [
            'transfer_certificate' => $files['transfer_certificate'] ?? null,
            'birth_certificate'    => $files['birth_certificate'] ?? null,
            'report_card'          => $files['report_card'] ?? null,
        ];
        foreach ($documents as $type => $file) {
            if (!$file) continue;
            $path = self::handleFile($file, 'admissions/documents');
            if ($path) {
                $db->insert('admission_documents', [
                    'admission_id' => $admissionId,
                    'document_type'=> $type,
                    'file_path'    => $path,
                    'uploaded_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }

        try {
            $db->insert('contact_submissions', [
                'type'       => 'admission',
                'name'       => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
                'email'      => $data['email'] ?? '',
                'phone'      => $data['phone'] ?? null,
                'subject'    => 'New admission application: ' . $appNumber,
                'message'    => 'A new admission application was submitted.',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
        }

        return $admissionId;
    }

    private static function handleFile(?array $file, string $directory): ?string
    {
        if (!$file || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        $uploadDir = __DIR__ . '/../../public/uploads/' . $directory;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = pathinfo($file['name'] ?? '', PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name'] ?? 'file')) . ($ext ? '.' . $ext : '');
        $dest = $uploadDir . '/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return 'uploads/' . $directory . '/' . $filename;
        }
        return null;
    }
}
