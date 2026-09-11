<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class SubjectController extends Controller
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
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND (name LIKE ? OR code LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM subjects WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT * FROM subjects WHERE {$where} ORDER BY name ASC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('dashboard.subjects.index', [
            'rows'     => $rows,
            'subjects' => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'search'   => $search,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'        => 'required|max:255',
            'code'        => 'max:50',
            'description' => 'max:500',
            'type'        => 'max:50',
        ]);

        $this->db->insert('subjects', [
            'name'        => $data['name'],
            'code'        => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'type'        => $data['type'] ?? null,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Subject created successfully.');
        $this->redirect('/dashboard/subjects');
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $subject = $this->db->fetch("SELECT * FROM subjects WHERE id = ? LIMIT 1", [$id]);
        if (!$subject) {
            Session::getInstance()->flash('error', 'Subject not found.');
            $this->redirect('/dashboard/subjects');
            return;
        }

        $data = $this->validate([
            'name'        => 'required|max:255',
            'code'        => 'max:50',
            'description' => 'max:500',
            'type'        => 'max:50',
        ]);

        $this->db->update('subjects', [
            'name'        => $data['name'],
            'code'        => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'type'        => $data['type'] ?? null,
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Subject updated successfully.');
        $this->redirect('/dashboard/subjects');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('class_subject_teacher', 'subject_id = ?', [$id]);
        $this->db->delete('subjects', 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Subject removed.');
        $this->redirect('/dashboard/subjects');
    }
}
