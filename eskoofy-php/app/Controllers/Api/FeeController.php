<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

class FeeController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $total = $this->db->count('fees');
        $rows = $this->db->fetchAll(
            "SELECT f.id, f.name, f.amount, f.fee_type, f.due_date, c.name as class_name
             FROM fees f
             LEFT JOIN school_classes c ON f.class_id = c.id
             ORDER BY f.id DESC
             LIMIT {$perPage} OFFSET {$offset}"
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
        $fee = $this->db->fetch("SELECT * FROM fees WHERE id = ? LIMIT 1", [$id]);
        if (!$fee) {
            $this->error('Fee not found', 404);
        }
        $this->success($fee, 'Fee retrieved');
    }

    public function store(): void
    {
        $data = $this->validate([
            'name'    => 'required|max:255',
            'amount'  => 'required|numeric',
            'fee_type'=> 'max:50',
            'class_id'=> 'numeric',
            'due_date'=> '',
        ]);

        $id = $this->db->insert('fees', [
            'name'       => $data['name'],
            'amount'     => $data['amount'],
            'fee_type'   => $data['fee_type'] ?? 'one_time',
            'class_id'   => $data['class_id'] ?? null,
            'due_date'   => $data['due_date'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->success(['id' => $id], 'Fee created', 201);
    }

    public function update(int $id): void
    {
        $fee = $this->db->fetch("SELECT * FROM fees WHERE id = ? LIMIT 1", [$id]);
        if (!$fee) {
            $this->error('Fee not found', 404);
        }

        $data = $this->validate([
            'name'    => 'max:255',
            'amount'  => 'numeric',
            'fee_type'=> 'max:50',
        ]);

        $updates = [];
        foreach (['name', 'amount', 'fee_type'] as $field) {
            if (isset($data[$field])) {
                $updates[$field] = $data[$field];
            }
        }
        $updates['updated_at'] = date('Y-m-d H:i:s');
        $this->db->update('fees', $updates, 'id = ?', [$id]);

        $this->success(['id' => $id], 'Fee updated');
    }

    public function destroy(int $id): void
    {
        $this->db->delete('fees', 'id = ?', [$id]);
        $this->success(['id' => $id], 'Fee deleted');
    }
}