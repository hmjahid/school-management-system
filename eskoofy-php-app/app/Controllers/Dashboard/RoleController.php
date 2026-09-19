<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class RoleController extends Controller
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
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND r.name LIKE ?";
            $params[] = "%{$search}%";
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM roles r WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT r.*,
                (SELECT COUNT(*) FROM users WHERE role_id = r.id) as user_count
             FROM roles r
             WHERE {$where}
             ORDER BY r.name ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $permissions = $this->db->fetchAll("SELECT * FROM permissions ORDER BY name ASC");

        $this->view('dashboard.roles.index', [
            'rows'        => $this->paginateRows($rows, $total, $perPage, $page, \App\Models\Role::class),
            'roles' => $this->paginateRows($rows, $total, $perPage, $page, \App\Models\Role::class),
            'total'       => $total,
            'page'        => $page,
            'perPage'     => $perPage,
            'lastPage'    => max(1, (int) ceil($total / $perPage)),
            'search'      => $search,
            'permissions' => \App\Models\Permission::hydrate($permissions),
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.roles.create', [
            'permissions' => $this->groupedPermissions(),
        ]);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $row = $this->db->fetch("SELECT * FROM roles WHERE id = ? LIMIT 1", [$id]);
        if (!$row) {
            Session::getInstance()->flash('error', 'Role not found.');
            $this->redirect('/dashboard/roles');
            return;
        }
        $role = \App\Models\Role::newFromRow($row);

        $permissions = new \App\Core\Support\Collection();
        try {
            $permissions = new \App\Core\Support\Collection(\App\Models\Permission::hydrate(
                $this->db->fetchAll(
                    "SELECT p.* FROM permissions p
                     INNER JOIN role_permissions rp ON rp.permission_id = p.id
                     WHERE rp.role_id = ?
                     ORDER BY p.name ASC",
                    [$id]
                )
            ));
        } catch (\Throwable) {
            // Empty DB — no permissions yet.
        }
        $role->setRelation('permissions', $permissions);

        $this->view('dashboard.roles.edit', [
            'role'        => $role,
            'permissions' => $this->groupedPermissions(),
        ]);
    }

    /**
     * Permissions grouped by their first underscore-delimited word (the
     * module prefix), mirroring the Laravel app's groupBy for the roles forms.
     */
    private function groupedPermissions(): \App\Core\Support\Collection
    {
        $permissions = new \App\Core\Support\Collection();
        try {
            $permissions = new \App\Core\Support\Collection(
                \App\Models\Permission::hydrate($this->db->fetchAll("SELECT * FROM permissions ORDER BY name ASC"))
            );
        } catch (\Throwable) {
            // Empty DB — no permissions yet.
        }

        return $permissions->groupBy(function ($permission) {
            $parts = explode('_', (string) $permission->name);

            return count($parts) > 1 ? $parts[0] : 'general';
        });
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'        => 'required|max:255',
            'description' => 'max:500',
            'permissions' => 'max:5000',
        ]);

        $roleId = $this->db->insert('roles', [
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        if (!empty($data['permissions'])) {
            $permIds = array_map('intval', explode(',', $data['permissions']));
            foreach ($permIds as $permId) {
                if ($permId > 0) {
                    $this->db->insert('role_permissions', [
                        'role_id'       => $roleId,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }

        Session::getInstance()->flash('success', 'Role created successfully.');
        $this->redirect('/dashboard/roles');
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $role = $this->db->fetch("SELECT * FROM roles WHERE id = ? LIMIT 1", [$id]);
        if (!$role) {
            Session::getInstance()->flash('error', 'Role not found.');
            $this->redirect('/dashboard/roles');
            return;
        }

        $data = $this->validate([
            'name'        => 'required|max:255',
            'description' => 'max:500',
            'permissions' => 'max:5000',
        ]);

        $this->db->update('roles', [
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->db->delete('role_permissions', 'role_id = ?', [$id]);
        if (!empty($data['permissions'])) {
            $permIds = array_map('intval', explode(',', $data['permissions']));
            foreach ($permIds as $permId) {
                if ($permId > 0) {
                    $this->db->insert('role_permissions', [
                        'role_id'       => $id,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }

        Session::getInstance()->flash('success', 'Role updated successfully.');
        $this->redirect('/dashboard/roles');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $role = $this->db->fetch("SELECT * FROM roles WHERE id = ? LIMIT 1", [$id]);
        if ($role && $role['name'] === 'admin') {
            Session::getInstance()->flash('error', 'The admin role cannot be deleted.');
            $this->redirect('/dashboard/roles');
            return;
        }

        $this->db->delete('role_permissions', 'role_id = ?', [$id]);
        $this->db->delete('roles', 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Role deleted.');
        $this->redirect('/dashboard/roles');
    }
}
