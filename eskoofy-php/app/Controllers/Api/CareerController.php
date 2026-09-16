<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

/**
 * Public careers endpoints — list, single, apply.
 * Parity with eskoofy-app Api\CareerController.
 */
class CareerController extends Controller
{
    public function index(): void
    {
        $db  = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT id, title, description, type, location, salary_min, salary_max, deadline
             FROM careers
             WHERE is_published = 1 AND deadline >= CURDATE()
             ORDER BY deadline ASC"
        );
        $this->success($rows, 'Careers list retrieved');
    }

    public function show(int $id): void
    {
        $db  = Database::getInstance();
        $row = $db->fetch(
            "SELECT id, title, description, requirements, type, location, salary_min, salary_max, deadline
             FROM careers
             WHERE id = ? AND is_published = 1",
            [$id]
        );
        if (! $row) {
            $this->error('Career not found.', 404);
        }
        $this->success($row, 'Career retrieved');
    }

    public function apply(): void
    {
        $name  = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $careerId = (int) ($_POST['career_id'] ?? 0);

        if ($name === '' || $email === '' || $careerId < 1) {
            $this->error('Name, email and career are required.', 422);
        }

        $db = Database::getInstance();
        $career = $db->fetch("SELECT id FROM careers WHERE id = ? AND is_published = 1", [$careerId]);
        if (! $career) {
            $this->error('Career not found.', 404);
        }

        $db->insert('job_applications', [
            'career_id'    => $careerId,
            'name'         => $name,
            'email'        => $email,
            'phone'        => trim((string) ($_POST['phone'] ?? '')),
            'resume_path'  => trim((string) ($_POST['resume_path'] ?? '')),
            'cover_letter' => trim((string) ($_POST['cover_letter'] ?? '')),
            'status'       => 'pending',
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->json(['success' => true, 'message' => 'Application submitted successfully.', 'data' => null], 201);
    }
}