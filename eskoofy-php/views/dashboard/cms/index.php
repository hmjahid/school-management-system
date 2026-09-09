<?php $pageTitle = 'CMS Pages'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">CMS Pages</h1>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Slug</th>
                    <th class="py-3 px-4 font-semibold border-b">Title</th>
                    <th class="py-3 px-4 font-semibold border-b">Active</th>
                    <th class="py-3 px-4 font-semibold border-b">Updated</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pages)): ?>
                    <?php foreach ($pages as $p): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-mono text-sm"><?= e($p['page']) ?></td>
                        <td class="py-3 px-4"><?= e($p['title'] ?? $p['title_en'] ?? '-') ?></td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded-full <?= !empty($p['is_active']) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>">
                                <?= !empty($p['is_active']) ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e(date('M d, Y', strtotime($p['updated_at'] ?? 'now'))) ?></td>
                        <td class="py-3 px-4">
                            <a href="/dashboard/cms/<?= e($p['id']) ?>/edit" class="text-green-600 hover:underline text-sm">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="py-8 text-center text-gray-500">No pages yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/dashboard.php'; ?>
