<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class PermissionController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();

        $permissions = $db->fetchAll("SELECT * FROM permissions ORDER BY name ASC LIMIT 200");
        $roles = $db->fetchAll("SELECT * FROM roles ORDER BY name ASC LIMIT 50");

        $this->view('dashboard.permissions.index', [
            'permissions' => $permissions,
            'roles'       => $roles,
        ]);
    }
}
