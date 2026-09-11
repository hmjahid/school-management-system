<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;

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
        $status = $_GET['status'] ?? '';

        $where = '1=1';
        $params = [];
        if ($status !== '') {
            $where .= ' AND a.status = ?';
            $params[] = $status;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM admissions a WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT a.id, a.application_number, a.first_name, a.last_name, a.email, a.phone,
                    a.status, a.payment_status, a.submitted_at, c.name as class_name
             FROM admissions a
             LEFT JOIN school_classes c ON a.class_id = c.id
             WHERE {$where}
             ORDER BY a.submitted_at DESC
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
        $admission = $this->db->fetch("SELECT * FROM admissions WHERE id = ? LIMIT 1", [$id]);
        if (!$admission) {
            $this->error('Admission not found', 404);
        }
        $this->success($admission, 'Admission retrieved');
    }

    public function store(): void
    {
        $data = $this->validate([
            'first_name' => 'required|max:255',
            'last_name'  => 'max:255',
            'email'      => 'required|email',
            'phone'      => 'required|max:20',
            'class_id'   => 'required|numeric',
            'gender'     => 'in:male,female,other',
        ]);

        $id = $this->db->insert('admissions', [
            'application_number' => 'APP-' . date('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'first_name'     => $data['first_name'],
            'last_name'      => $data['last_name'] ?? '',
            'email'          => $data['email'],
            'phone'          => $data['phone'],
            'class_id'       => $data['class_id'],
            'gender'         => $data['gender'] ?? null,
            'status'         => 'pending',
            'payment_status' => 'pending',
            'submitted_at'   => date('Y-m-d H:i:s'),
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->success(['id' => $id], 'Admission submitted', 201);
    }

    public function update(int $id): void
    {
        $admission = $this->db->fetch("SELECT * FROM admissions WHERE id = ? LIMIT 1", [$id]);
        if (!$admission) {
            $this->error('Admission not found', 404);
        }

        $data = $this->validate([
            'status' => 'in:pending,approved,rejected,enrolled',
            'payment_status' => 'in:pending,paid,refunded',
        ]);

        $updates = [];
        if (isset($data['status'])) {
            $updates['status'] = $data['status'];
            if ($data['status'] === 'approved') {
                $updates['approved_at'] = date('Y-m-d H:i:s');
            }
        }
        if (isset($data['payment_status'])) {
            $updates['payment_status'] = $data['payment_status'];
        }
        $updates['updated_at'] = date('Y-m-d H:i:s');
        $this->db->update('admissions', $updates, 'id = ?', [$id]);

        $this->success(['id' => $id], 'Admission updated');
    }

    public function destroy(int $id): void
    {
        $this->db->delete('admissions', 'id = ?', [$id]);
        $this->success(['id' => $id], 'Admission deleted');
    }
}