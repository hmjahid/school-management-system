<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

class ClassController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $rows = $this->db->fetchAll(
            "SELECT id, name, code, description, (SELECT COUNT(*) FROM students s WHERE s.class_id = school_classes.id) as student_count
             FROM school_classes
             ORDER BY name ASC"
        );
        $this->success($rows, 'Classes list retrieved');
    }

    public function show(int $id): void
    {
        $class = $this->db->fetch("SELECT * FROM school_classes WHERE id = ? LIMIT 1", [$id]);
        if (!$class) {
            $this->error('Class not found', 404);
        }
        $this->success($class, 'Class retrieved');
    }

    public function store(): void
    {
        $data = $this->validate([
            'name'  => 'required|max:255',
            'code'  => 'max:50',
            'description' => 'max:500',
        ]);

        $id = $this->db->insert('school_classes', [
            'name'        => $data['name'],
            'code'        => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $this->success(['id' => $id], 'Class created', 201);
    }

    public function update(int $id): void
    {
        $class = $this->db->fetch("SELECT * FROM school_classes WHERE id = ? LIMIT 1", [$id]);
        if (!$class) {
            $this->error('Class not found', 404);
        }

        $data = $this->validate([
            'name'  => 'required|max:255',
            'code'  => 'max:50',
            'description' => 'max:500',
        ]);

        $this->db->update('school_classes', [
            'name'        => $data['name'],
            'code'        => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->success(['id' => $id], 'Class updated');
    }

    public function destroy(int $id): void
    {
        $this->db->delete('school_classes', 'id = ?', [$id]);
        $this->success(['id' => $id], 'Class deleted');
    }
}