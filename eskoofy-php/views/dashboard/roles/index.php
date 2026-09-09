<?php $pageTitle = 'Roles & Permissions'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Roles & Permissions</h1>
    <button onclick="document.getElementById('create-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Add Role</button>
</div>

<div class="space-y-6">
    <?php if (!empty($roles)): ?>
        <?php foreach ($roles as $role): ?>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-6 border-b flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-bold"><?= e($role->name) ?></h3>
                    <p class="text-sm text-gray-500"><?= e($role->users_count ?? 0) ?> users</p>
                </div>
                <div class="flex space-x-2">
                    <a href="/dashboard/roles/<?= e($role->id) ?>/edit" class="text-green-600 hover:underline text-sm">Edit</a>
                    <?php if (!$role->is_default ?? false): ?>
                    <form action="/dashboard/roles/<?= e($role->id) ?>" method="POST" onsubmit="return confirm('Delete this role?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-3 md:grid-cols-6 gap-2">
                    <?php if (!empty($role->permissions)): ?>
                        <?php foreach ($role->permissions as $permission): ?>
                        <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded text-center"><?= e($permission->name) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-gray-400 text-sm">No permissions assigned</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">No roles found.</div>
    <?php endif; ?>
</div>

<div id="create-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Create Role</h2>
        <form action="/dashboard/roles" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role Name *</label>
                <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Permissions</label>
                <div class="grid grid-cols-2 gap-2 mt-2 max-h-48 overflow-y-auto border rounded-lg p-3">
                    <?php if (!empty($allPermissions)): ?>
                        <?php foreach ($allPermissions as $permission): ?>
                        <label class="flex items-center text-sm">
                            <input type="checkbox" name="permissions[]" value="<?= e($permission->id) ?>" class="rounded border-gray-300 text-blue-600 mr-2">
                            <?= e($permission->name) ?>
                        </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Create</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>