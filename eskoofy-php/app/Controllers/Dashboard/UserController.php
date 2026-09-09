<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class UserController extends Controller
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
        $roleId = (int) ($_GET['role_id'] ?? 0);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND (u.name LIKE ? OR u.email LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }
        if ($roleId > 0) {
            $where .= ' AND u.role_id = ?';
            $params[] = $roleId;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM users u WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT u.*, r.name as role_name
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE {$where}
             ORDER BY u.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $roles = $this->db->fetchAll("SELECT id, name FROM roles ORDER BY name ASC");

        $this->view('dashboard.users.index', [
            'rows'     => $rows,
            'users' => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'search'   => $search,
            'roleId'   => $roleId,
            'roles'    => $roles,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $roles = $this->db->fetchAll("SELECT id, name FROM roles ORDER BY name ASC");
        $this->view('dashboard.users.create', ['roles' => $roles]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'     => 'required|max:255',
            'email'    => 'required|email',
            'phone'    => 'max:20',
            'password' => 'required|min:8|confirmed',
            'role_id'  => 'required|numeric',
        ]);

        $exists = $this->db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$data['email']]);
        if ($exists) {
            Session::getInstance()->flash('error', 'A user with this email already exists.');
            $this->back();
            return;
        }

        $role = $this->db->fetch("SELECT * FROM roles WHERE id = ? LIMIT 1", [$data['role_id']]);

        $this->db->insert('users', [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'password'   => Auth::hashPassword($data['password']),
            'role'       => $role['name'] ?? 'user',
            'role_id'    => $data['role_id'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'User created successfully.');
        $this->redirect('/dashboard/users');
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ? LIMIT 1", [$id]);
        if (!$user) {
            Session::getInstance()->flash('error', 'User not found.');
            $this->redirect('/dashboard/users');
            return;
        }

        $data = $this->validate([
            'name'     => 'required|max:255',
            'email'    => 'required|email',
            'phone'    => 'max:20',
            'password' => 'min:8|confirmed',
            'role_id'  => 'required|numeric',
        ]);

        $updateData = [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'role_id'    => $data['role_id'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Auth::hashPassword($data['password']);
        }

        $role = $this->db->fetch("SELECT * FROM roles WHERE id = ? LIMIT 1", [$data['role_id']]);
        if ($role) {
            $updateData['role'] = $role['name'];
        }

        $this->db->update('users', $updateData, 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'User updated successfully.');
        $this->redirect('/dashboard/users');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        if ((int) Auth::id() === $id) {
            Session::getInstance()->flash('error', 'You cannot delete your own account.');
            $this->redirect('/dashboard/users');
            return;
        }

        $this->db->update('users', ['deleted_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'User deleted.');
        $this->redirect('/dashboard/users');
    }
}
