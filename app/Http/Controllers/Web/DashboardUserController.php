<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardUserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage_users');

        $query = User::with(['schoolRole', 'roles']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleId = $request->integer('role_id')) {
            $query->where('role_id', $roleId);
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $roles = Role::orderBy('name')->get();

        return view('dashboard.users.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        $this->authorize('manage_users');

        return view('dashboard.users.create', [
            'roles' => Role::orderBy('name')->get(),
            'permissions' => \App\Models\Permission::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage_users');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('photos', 'public');
        }

        $validated['password'] = bcrypt($validated['password']);
        $role = Role::findOrFail($validated['role_id']);
        $user = User::createWithCredential($validated);
        $user->assignRole($role->name);

        if ($request->has('direct_permissions')) {
            $user->syncPermissions($request->input('direct_permissions', []));
        }

        activity()->performedOn($user)
            ->causedBy($request->user())
            ->withProperties([
                'role' => $role->name,
                'permissions' => $request->input('direct_permissions', []),
            ])
            ->log('role_changed');

        return redirect()->route('dashboard.users.index')
            ->with('status', __('User created successfully.'));
    }

    public function show(User $user): View
    {
        $this->authorize('manage_users');

        $user->load(['schoolRole', 'roles', 'permissions']);

        return view('dashboard.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $this->authorize('manage_users');

        $user->load(['roles', 'permissions']);

        return view('dashboard.users.edit', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
            'permissions' => \App\Models\Permission::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manage_users');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $validated['photo'] = $request->file('photo')->store('photos', 'public');
        } else {
            unset($validated['photo']);
        }

        $role = Role::findOrFail($validated['role_id']);

        if (! empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
            $user->save();
        }

        $user->role_id = $role->id;
        $user->save();

        $user->update(array_diff_key($validated, array_flip(['password', 'role_id'])));

        $user->syncRoles([$role->name]);

        if ($request->has('direct_permissions')) {
            $user->syncPermissions($request->input('direct_permissions', []));
        } else {
            $user->syncPermissions([]);
        }

        activity()->performedOn($user)
            ->causedBy($request->user())
            ->withProperties([
                'role' => $role->name,
                'permissions' => $request->input('direct_permissions', []),
            ])
            ->log('role_changed');

        return redirect()->route('dashboard.users.index')
            ->with('status', __('User updated successfully.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('manage_users');

        if ($user->id === auth()->id()) {
            return back()->with('error', __('You cannot delete your own account.'));
        }

        $user->delete();

        return redirect()->route('dashboard.users.index')
            ->with('status', __('User deleted successfully.'));
    }
}
