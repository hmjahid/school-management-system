<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class CommitteeController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $rows = $this->db->fetchAll(
            "SELECT cm.*, cm.designation as position
             FROM committee_members cm
             ORDER BY cm.sort_order ASC, cm.id ASC"
        );

        $this->view('dashboard.committee.index', [
            'rows' => $this->paginateRows($rows, count($rows), max(1, count($rows)), 1, \App\Models\CommitteeMember::class),
            'members' => $this->paginateRows($rows, count($rows), max(1, count($rows)), 1, \App\Models\CommitteeMember::class),
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $member = new \App\Models\CommitteeMember(['is_active' => true, 'sort_order' => 0]);
        $this->view('dashboard.committee.create', ['member' => $member]);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $row = $this->db->fetch("SELECT * FROM committee_members WHERE id = ? LIMIT 1", [$id]);
        if (!$row) {
            Session::getInstance()->flash('error', 'Member not found.');
            $this->redirect('/dashboard/committee');
            return;
        }
        $member = \App\Models\CommitteeMember::newFromRow($row);
        $member->setAttribute('exists', true);

        $this->view('dashboard.committee.edit', ['member' => $member]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'        => 'required|max:191',
            'designation' => 'max:191',
            'position'    => 'max:191',
            'phone'       => 'max:191',
            'email'       => 'email',
            'bio'         => 'max:2000',
            'sort_order'  => 'numeric',
        ]);

        $this->db->insert('committee_members', [
            'name'        => $data['name'],
            'designation' => $data['designation'] ?? $data['position'] ?? '',
            'phone'       => $data['phone'] ?? null,
            'email'       => $data['email'] ?? null,
            'bio'         => $data['bio'] ?? null,
            'photo'       => $this->storePhoto(),
            'sort_order'  => $data['order'] ?? $data['sort_order'] ?? 0,
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Committee member added.');
        $this->redirect('/dashboard/committee');
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $member = $this->db->fetch("SELECT * FROM committee_members WHERE id = ? LIMIT 1", [$id]);
        if (!$member) {
            Session::getInstance()->flash('error', 'Member not found.');
            $this->redirect('/dashboard/committee');
            return;
        }

        $data = $this->validate([
            'name'        => 'required|max:191',
            'designation' => 'max:191',
            'position'    => 'max:191',
            'phone'       => 'max:191',
            'email'       => 'email',
            'bio'         => 'max:2000',
            'sort_order'  => 'numeric',
        ]);

        $payload = [
            'name'        => $data['name'],
            'designation' => $data['designation'] ?? $data['position'] ?? '',
            'phone'       => $data['phone'] ?? null,
            'email'       => $data['email'] ?? null,
            'bio'         => $data['bio'] ?? null,
            'sort_order'  => $data['order'] ?? $data['sort_order'] ?? 0,
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        $photo = $this->storePhoto();
        if ($photo !== null) {
            if (!empty($member['photo'])) {
                $old = __DIR__ . '/../../public/' . ltrim($member['photo'], '/');
                if (file_exists($old)) {
                    @unlink($old);
                }
            }
            $payload['photo'] = $photo;
        }

        $this->db->update('committee_members', $payload, 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Member updated.');
        $this->redirect('/dashboard/committee');
    }

    /**
     * Store an uploaded member photo under public/uploads/committee.
     */
    private function storePhoto(): ?string
    {
        if (empty($_FILES['photo']['tmp_name']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        $uploadDir = __DIR__ . '/../../public/uploads/committee/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['photo']['name']));
        move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename);

        return 'uploads/committee/' . $filename;
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $member = $this->db->fetch("SELECT * FROM committee_members WHERE id = ? LIMIT 1", [$id]);
        if ($member && !empty($member['photo'])) {
            $old = __DIR__ . '/../../public/' . ltrim($member['photo'], '/');
            if (file_exists($old)) {
                @unlink($old);
            }
        }
        $this->db->delete('committee_members', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Member removed.');
        $this->redirect('/dashboard/committee');
    }
}