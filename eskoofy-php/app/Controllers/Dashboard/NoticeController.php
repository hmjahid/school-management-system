<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class NoticeController extends Controller
{
    private Database $db;

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
            $where .= " AND (n.title LIKE ? OR n.content LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM notices n WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT n.*, u.name as creator_name
             FROM notices n
             LEFT JOIN users u ON n.created_by = u.id
             WHERE {$where}
             ORDER BY n.pinned DESC, n.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('dashboard.notices.index', [
            'rows'     => $rows,
            'notices' => $rows,
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
            'title'    => 'required|max:255',
            'content'  => 'required',
            'pinned'   => 'numeric',
            'audience' => 'max:50',
        ]);

        $this->db->insert('notices', [
            'title'      => $data['title'],
            'content'    => $data['content'],
            'pinned'     => $data['pinned'] ?? 0,
            'audience'   => $data['audience'] ?? 'all',
            'created_by' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Notice created successfully.');
        $this->redirect('/dashboard/notices');
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $notice = $this->db->fetch("SELECT * FROM notices WHERE id = ? LIMIT 1", [$id]);
        if (!$notice) {
            Session::getInstance()->flash('error', 'Notice not found.');
            $this->redirect('/dashboard/notices');
            return;
        }

        $data = $this->validate([
            'title'    => 'required|max:255',
            'content'  => 'required',
            'pinned'   => 'numeric',
            'audience' => 'max:50',
        ]);

        $this->db->update('notices', [
            'title'      => $data['title'],
            'content'    => $data['content'],
            'pinned'     => $data['pinned'] ?? 0,
            'audience'   => $data['audience'] ?? 'all',
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Notice updated successfully.');
        $this->redirect('/dashboard/notices');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('notices', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Notice deleted.');
        $this->redirect('/dashboard/notices');
    }
}
