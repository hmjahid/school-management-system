<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class AnnouncementController extends Controller
{
    private Database $db;

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

        $total = $this->db->count('announcements');
        $rows = $this->db->fetchAll(
            "SELECT a.*, u.name as creator_name
             FROM announcements a
             LEFT JOIN users u ON a.created_by = u.id
             ORDER BY a.id DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );

        $this->view('dashboard.announcements.index', [
            'rows'     => $rows,
            'announcements' => $rows,
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
            'title'      => 'required|max:255',
            'message'    => 'required|max:5000',
            'priority'   => 'in:low,normal,high,urgent',
            'starts_at'  => '',
            'expires_at' => '',
        ]);

        $this->db->insert('announcements', [
            'title'      => $data['title'],
            'message'    => $data['message'],
            'priority'   => $data['priority'] ?? 'normal',
            'starts_at'  => $data['starts_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_active'  => 1,
            'created_by' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Announcement created.');
        $this->redirect('/dashboard/announcements');
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $row = $this->db->fetch("SELECT * FROM announcements WHERE id = ? LIMIT 1", [$id]);
        if (!$row) {
            Session::getInstance()->flash('error', 'Announcement not found.');
            $this->redirect('/dashboard/announcements');
            return;
        }

        $data = $this->validate([
            'title'      => 'required|max:255',
            'message'    => 'required|max:5000',
            'priority'   => 'in:low,normal,high,urgent',
            'is_active'  => 'numeric',
            'starts_at'  => '',
            'expires_at' => '',
        ]);

        $this->db->update('announcements', [
            'title'      => $data['title'],
            'message'    => $data['message'],
            'priority'   => $data['priority'] ?? 'normal',
            'is_active'  => $data['is_active'] ?? 1,
            'starts_at'  => $data['starts_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Announcement updated.');
        $this->redirect('/dashboard/announcements');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('announcements', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Announcement deleted.');
        $this->redirect('/dashboard/announcements');
    }
}
