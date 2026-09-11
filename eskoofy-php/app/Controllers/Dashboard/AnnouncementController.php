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
            "SELECT a.*
             FROM announcements a
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
            'content'    => 'required|max:5000',
            'audience'   => 'max:191',
            'starts_at'  => '',
            'ends_at'    => '',
        ]);

        $this->db->insert('announcements', [
            'title'          => $data['title'],
            'body'           => $data['content'],
            'audience'       => $data['audience'] ?? 'all',
            'display_target' => 'header',
            'is_published'   => 1,
            'starts_at'      => $data['starts_at'] ?? null,
            'ends_at'        => $data['ends_at'] ?? null,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
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
            'content'    => 'required|max:5000',
            'audience'   => 'max:191',
            'is_published' => 'numeric',
            'starts_at'  => '',
            'ends_at'    => '',
        ]);

        $this->db->update('announcements', [
            'title'          => $data['title'],
            'body'           => $data['content'],
            'audience'       => $data['audience'] ?? 'all',
            'is_published'   => $data['is_published'] ?? 1,
            'starts_at'      => $data['starts_at'] ?? null,
            'ends_at'        => $data['ends_at'] ?? null,
            'updated_at'     => date('Y-m-d H:i:s'),
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
