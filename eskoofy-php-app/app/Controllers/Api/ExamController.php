<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;

class ExamController extends Controller
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

        $total = $this->db->count('exams');
        $rows = $this->db->fetchAll(
            "SELECT e.id, e.name, e.exam_type, e.start_date, e.total_marks, e.passing_marks, e.is_published, e.status
             FROM exams e
             ORDER BY e.start_date DESC
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
        $exam = $this->db->fetch("SELECT * FROM exams WHERE id = ? LIMIT 1", [$id]);
        if (!$exam) {
            $this->error('Exam not found', 404);
        }
        $this->success($exam, 'Exam retrieved');
    }

    public function store(): void
    {
        $data = $this->validate([
            'name'          => 'required|max:255',
            'exam_type'     => 'required|max:50',
            'exam_date'     => 'required',
            'batch_id'      => 'required|numeric',
            'total_marks'   => 'required|numeric',
            'passing_marks' => 'required|numeric',
        ]);

        $id = $this->db->insert('exams', [
            'name'          => $data['name'],
            'exam_type'     => $data['exam_type'],
            'start_date'   => $data['exam_date'],
            'batch_id'      => $data['batch_id'],
            'total_marks'   => $data['total_marks'],
            'passing_marks' => $data['passing_marks'],
            'status'        => 'draft',
            'is_published'  => 0,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->success(['id' => $id], 'Exam created', 201);
    }

    public function update(int $id): void
    {
        $exam = $this->db->fetch("SELECT * FROM exams WHERE id = ? LIMIT 1", [$id]);
        if (!$exam) {
            $this->error('Exam not found', 404);
        }

        $data = $this->validate([
            'name'          => 'max:255',
            'exam_type'     => 'max:50',
            'start_date'   => '',
            'total_marks'   => 'numeric',
            'passing_marks' => 'numeric',
            'is_published'  => 'numeric',
        ]);

        $updates = [];
        $fieldMap = ['exam_date' => 'start_date'];
        foreach (['name', 'exam_type', 'exam_date', 'total_marks', 'passing_marks', 'is_published'] as $field) {
            if (isset($data[$field])) {
                $column = $fieldMap[$field] ?? $field;
                $updates[$column] = $data[$field];
            }
        }
        $updates['updated_at'] = date('Y-m-d H:i:s');
        $this->db->update('exams', $updates, 'id = ?', [$id]);

        $this->success(['id' => $id], 'Exam updated');
    }

    public function destroy(int $id): void
    {
        $this->db->delete('exam_results', 'exam_id = ?', [$id]);
        $this->db->delete('exams', 'id = ?', [$id]);
        $this->success(['id' => $id], 'Exam deleted');
    }
}