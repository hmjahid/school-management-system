<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class CommitteeController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $rows = $this->db->fetchAll(
            "SELECT mc.*, u.name as member_name, u.photo, u.designation
             FROM management_committees mc
             LEFT JOIN users u ON mc.user_id = u.id
             ORDER BY mc.designation ASC, mc.sort_order ASC, mc.id ASC"
        );

        $this->view('dashboard.committees.index', ['rows' => $rows]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'user_id'      => 'required|numeric',
            'designation'  => 'required|max:100',
            'role'         => 'required|max:100',
            'sort_order'   => 'numeric',
            'bio'          => 'max:1000',
        ]);

        $this->db->insert('management_committees', [
            'user_id'      => $data['user_id'],
            'designation'  => $data['designation'],
            'role'         => $data['role'],
            'sort_order'   => $data['sort_order'] ?? 0,
            'bio'          => $data['bio'] ?? null,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Committee member added.');
        $this->redirect('/dashboard/committees');
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $member = $this->db->fetch("SELECT * FROM management_committees WHERE id = ? LIMIT 1", [$id]);
        if (!$member) {
            Session::getInstance()->flash('error', 'Member not found.');
            $this->redirect('/dashboard/committees');
            return;
        }

        $data = $this->validate([
            'user_id'      => 'required|numeric',
            'designation'  => 'required|max:100',
            'role'         => 'required|max:100',
            'sort_order'   => 'numeric',
            'bio'          => 'max:1000',
        ]);

        $this->db->update('management_committees', [
            'user_id'      => $data['user_id'],
            'designation'  => $data['designation'],
            'role'         => $data['role'],
            'sort_order'   => $data['sort_order'] ?? 0,
            'bio'          => $data['bio'] ?? null,
            'updated_at'   => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Member updated.');
        $this->redirect('/dashboard/committees');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('management_committees', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Member removed.');
        $this->redirect('/dashboard/committees');
    }
}
