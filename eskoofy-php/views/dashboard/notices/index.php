<?php $pageTitle = 'Notices'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Notices</h1>
    <button onclick="document.getElementById('create-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Create Notice</button>
</div>

<div class="space-y-4">
    <?php if (!empty($notices)): ?>
        <?php foreach ($notices as $notice): ?>
        <div class="bg-white rounded-xl shadow-sm p-6 <?= ($notice->is_pinned ?? false) ? 'border-l-4 border-yellow-500' : '' ?>">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <div class="flex items-center mb-2">
                        <?php if ($notice->is_pinned ?? false): ?>
                            <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded mr-2">📌 Pinned</span>
                        <?php endif; ?>
                        <span class="text-sm text-gray-500"><?= e($notice->created_at->format('M d, Y')) ?></span>
                    </div>
                    <h3 class="text-lg font-bold mb-2"><?= e($notice->title) ?></h3>
                    <p class="text-gray-600 text-sm"><?= e(Str::limit($notice->content, 200)) ?></p>
                </div>
                <div class="flex space-x-2 ml-4">
                    <a href="/dashboard/notices/<?= e($notice->id) ?>/edit" class="text-green-600 hover:underline text-sm">Edit</a>
                    <form action="/dashboard/notices/<?= e($notice->id) ?>" method="POST" onsubmit="return confirm('Delete this notice?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">No notices yet.</div>
    <?php endif; ?>
</div>

<div id="create-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-lg">
        <h2 class="text-xl font-bold mb-4">Create Notice</h2>
        <form action="/dashboard/notices" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                <input type="text" name="title" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Content *</label>
                <textarea name="content" rows="5" required class="w-full border border-gray-300 rounded-lg px-4 py-2"></textarea>
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="is_pinned" value="1" class="rounded border-gray-300 text-blue-600 mr-2">
                    <span class="text-sm text-gray-700">Pin this notice</span>
                </label>
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