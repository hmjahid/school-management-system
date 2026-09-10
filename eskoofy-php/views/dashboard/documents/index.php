<?php $pageTitle = 'Documents'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Document Library</h1>
    <a href="/dashboard/documents/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">+ Upload</a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Title</th>
                    <th class="py-3 px-4 font-semibold border-b">Category</th>
                    <th class="py-3 px-4 font-semibold border-b">Uploaded</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($documents)): ?>
                    <?php foreach ($documents as $d): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($d['title']) ?></td>
                        <td class="py-3 px-4 text-sm"><?= e($d['category'] ?? '-') ?></td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e(date('M d, Y', strtotime($d['created_at'] ?? 'now'))) ?></td>
                        <td class="py-3 px-4">
                            <a href="/<?= e($d['file_path']) ?>" target="_blank" class="text-blue-600 hover:underline text-sm">Download</a>
                            <form action="/dashboard/documents/<?= e($d['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Delete?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="ml-2 text-red-600 hover:underline text-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="py-8 text-center text-gray-500">No documents yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
