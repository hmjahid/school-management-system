<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class BatchController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $total = $this->db->count('batches');

        $rows = $this->db->fetchAll(
            "SELECT b.*,
                (SELECT COUNT(*) FROM students s WHERE s.batch_id = b.id) as student_count
             FROM batches b
             ORDER BY b.id DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );

        $this->view('dashboard.batches.index', [
            'rows'     => $rows,
            'batches' => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'        => 'required|max:100',
            'start_date'  => 'date',
            'end_date'    => 'date',
            'description' => 'max:500',
        ]);

        $this->db->insert('batches', [
            'name'        => $data['name'],
            'start_date'  => $data['start_date'] ?? null,
            'end_date'    => $data['end_date'] ?? null,
            'description' => $data['description'] ?? null,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Batch created successfully.');
        $this->redirect('/dashboard/batches');
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $batch = $this->db->fetch("SELECT * FROM batches WHERE id = ? LIMIT 1", [$id]);
        if (!$batch) {
            Session::getInstance()->flash('error', 'Batch not found.');
            $this->redirect('/dashboard/batches');
            return;
        }

        $data = $this->validate([
            'name'        => 'required|max:100',
            'start_date'  => 'date',
            'end_date'    => 'date',
            'description' => 'max:500',
        ]);

        $this->db->update('batches', [
            'name'        => $data['name'],
            'start_date'  => $data['start_date'] ?? null,
            'end_date'    => $data['end_date'] ?? null,
            'description' => $data['description'] ?? null,
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Batch updated successfully.');
        $this->redirect('/dashboard/batches');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $studentCount = $this->db->count('students', "batch_id = ?", [$id]);
        if ($studentCount > 0) {
            Session::getInstance()->flash('error', 'Cannot delete batch with assigned students.');
            $this->redirect('/dashboard/batches');
            return;
        }

        $this->db->delete('batches', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Batch removed.');
        $this->redirect('/dashboard/batches');
    }
}
