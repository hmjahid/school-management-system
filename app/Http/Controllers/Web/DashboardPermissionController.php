<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\View\View;

class DashboardPermissionController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage_permissions');

        $permissions = Permission::with('roles')
            ->orderBy('name')
            ->get()
            ->groupBy(function ($permission) {
                $parts = explode('_', $permission->name);

                return count($parts) > 1 ? $parts[0] : 'general';
            });

        return view('dashboard.permissions.index', compact('permissions'));
    }
}
