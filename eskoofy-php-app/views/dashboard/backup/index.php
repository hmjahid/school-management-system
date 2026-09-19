<?php $pageTitle = 'Backup'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Backup Management</h1>
</div>

<div class="grid md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Create Backup</h2>
        <p class="text-gray-500 text-sm mb-4">Create a backup of your database and files. Backups are stored locally.</p>
        <form action="/dashboard/backup" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Create Backup Now</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Backup Information</h2>
        <div class="space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Last Backup:</span>
                <span class="font-medium"><?= e($lastBackup ?? 'Never') ?></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Total Backups:</span>
                <span class="font-medium"><?= e($totalBackups ?? 0) ?></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Disk Usage:</span>
                <span class="font-medium"><?= e($diskUsage ?? '0 MB') ?></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Auto Backup:</span>
                <span class="font-medium text-green-600">Enabled (Daily)</span>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-lg font-bold">Previous Backups</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Filename</th>
                    <th class="py-3 px-4 font-semibold border-b">Size</th>
                    <th class="py-3 px-4 font-semibold border-b">Type</th>
                    <th class="py-3 px-4 font-semibold border-b">Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($backups)): ?>
                    <?php foreach ($backups as $backup): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-mono text-sm"><?= e($backup['name'] ?? '') ?></td>
                        <td class="py-3 px-4 text-sm"><?= e($backup['size'] ?? '') ?></td>
                        <td class="py-3 px-4 text-sm"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700"><?= e($backup['type'] ?? 'Full') ?></span></td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($backup['created_at'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="py-8 text-center text-gray-500">No backups yet. Create your first backup above.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>