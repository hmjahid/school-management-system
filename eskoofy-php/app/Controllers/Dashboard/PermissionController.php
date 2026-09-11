<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Support\Collection;

class PermissionController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();

        $permissions = $db->fetchAll("SELECT * FROM permissions ORDER BY name ASC LIMIT 200");
        $roles = $db->fetchAll("SELECT * FROM roles ORDER BY name ASC LIMIT 50");

        $grouped = (new Collection(\App\Models\Permission::hydrate($permissions)))->groupBy(
            static function ($permission) {
                $parts = explode('_', (string) $permission->name);
                return count($parts) > 1 ? $parts[0] : 'general';
            }
        );

        $this->view('dashboard.permissions.index', [
            'permissions' => $grouped,
            'roles'       => new Collection(\App\Models\Role::hydrate($roles)),
        ]);
    }
}
