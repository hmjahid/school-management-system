<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardRoleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage_roles');

        $query = Role::withCount('users');

        if ($search = $request->string('search')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        $roles = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('dashboard.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $this->authorize('manage_roles');

        $permissions = Permission::orderBy('name')->get()->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);

            return count($parts) > 1 ? $parts[0] : 'general';
        });

        return view('dashboard.roles.create', compact('permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage_roles');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
            'guard_name' => 'required|string|in:web,api',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'guard_name' => $validated['guard_name'],
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('dashboard.roles.index')
            ->with('status', __('Role created successfully.'));
    }

    public function edit(Role $role): View
    {
        $this->authorize('manage_roles');

        $role->load('permissions');

        $permissions = Permission::orderBy('name')->get()->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);

            return count($parts) > 1 ? $parts[0] : 'general';
        });

        return view('dashboard.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('manage_roles');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$role->id,
            'description' => 'nullable|string|max:500',
            'guard_name' => 'required|string|in:web,api',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'guard_name' => $validated['guard_name'],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('dashboard.roles.index')
            ->with('status', __('Role updated successfully.'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('manage_roles');

        if ($role->name === 'admin') {
            return back()->with('error', __('The admin role cannot be deleted.'));
        }

        $role->delete();

        return redirect()->route('dashboard.roles.index')
            ->with('status', __('Role deleted successfully.'));
    }
}
