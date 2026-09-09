<?php $pageTitle = 'Activity Log'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Activity / Audit Log</h1>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/activity" method="GET" class="flex flex-col sm:flex-row gap-4">
        <select name="user_id" class="border border-gray-300 rounded-lg px-4 py-2">
            <option value="">All Users</option>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                <option value="<?= e($user['id']) ?>"<?= ($userId ?? '') == $user['id'] ? ' selected' : '' ?>><?= e($user['name'] ?? '') ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <input type="text" name="action" value="<?= e($action ?? '') ?>" placeholder="Action (e.g. login, create, update)" class="border border-gray-300 rounded-lg px-4 py-2">
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Time</th>
                    <th class="py-3 px-4 font-semibold border-b">User</th>
                    <th class="py-3 px-4 font-semibold border-b">Action</th>
                    <th class="py-3 px-4 font-semibold border-b">Subject</th>
                    <th class="py-3 px-4 font-semibold border-b">Details</th>
                    <th class="py-3 px-4 font-semibold border-b">IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($row['created_at'] ?? '') ?></td>
                        <td class="py-3 px-4 font-medium"><?= e($row['user_name'] ?? 'System') ?></td>
                        <td class="py-3 px-4">
                            <?php
                            $actionColors = [
                                'login' => 'bg-blue-100 text-blue-700',
                                'create' => 'bg-green-100 text-green-700',
                                'update' => 'bg-yellow-100 text-yellow-700',
                                'delete' => 'bg-red-100 text-red-700',
                                'view' => 'bg-gray-100 text-gray-700',
                            ];
                            $aColor = $actionColors[$row['action'] ?? ''] ?? 'bg-gray-100 text-gray-700';
                            ?>
                            <span class="px-2 py-1 text-xs rounded-full <?= $aColor ?>"><?= e(ucfirst($row['action'] ?? '')) ?></span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e(($row['subject_type'] ?? '') . ' #' . ($row['subject_id'] ?? '')) ?></td>
                        <td class="py-3 px-4 text-sm text-gray-500 max-w-xs truncate"><?= e($row['description'] ?? '-') ?></td>
                        <td class="py-3 px-4 text-sm text-gray-400"><?= e($row['ip_address'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="py-8 text-center text-gray-500">No activity logs found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($lastPage > 1): ?>
    <div class="p-6 flex justify-center space-x-2">
        <?php for ($i = 1; $i <= $lastPage; $i++): ?>
        <a href="?page=<?= $i ?>" class="px-3 py-1 rounded-lg <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
