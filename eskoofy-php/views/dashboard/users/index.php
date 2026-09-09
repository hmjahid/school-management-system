<?php $pageTitle = 'Users'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Users</h1>
    <a href="/dashboard/users/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Add User</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/users" method="GET" class="flex flex-col sm:flex-row gap-4">
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Search users..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        <select name="role" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">All Roles</option>
            <?php if (!empty($roles)): ?>
                <?php foreach ($roles as $role): ?>
                <option value="<?= e($role->slug) ?>" <?= ($roleFilter ?? '') == $role->slug ? 'selected' : '' ?>><?= e($role->name) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Name</th>
                    <th class="py-3 px-4 font-semibold border-b">Email</th>
                    <th class="py-3 px-4 font-semibold border-b">Role</th>
                    <th class="py-3 px-4 font-semibold border-b">Status</th>
                    <th class="py-3 px-4 font-semibold border-b">Last Login</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-bold mr-3">
                                    <?= strtoupper(substr($user->name, 0, 1)) ?>
                                </div>
                                <?= e($user->name) ?>
                            </div>
                        </td>
                        <td class="py-3 px-4"><?= e($user->email) ?></td>
                        <td class="py-3 px-4"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700"><?= e($user->role->name ?? '') ?></span></td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded-full <?= ($user->is_active ?? true) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= ($user->is_active ?? true) ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never') ?></td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="/dashboard/users/<?= e($user->id) ?>/edit" class="text-green-600 hover:underline text-sm">Edit</a>
                                <form action="/dashboard/users/<?= e($user->id) ?>" method="POST" onsubmit="return confirm('Delete this user?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="py-8 text-center text-gray-500">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>