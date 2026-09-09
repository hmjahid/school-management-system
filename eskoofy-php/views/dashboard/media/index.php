<?php $pageTitle = 'Media Library'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Media Library</h1>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/media" method="POST" enctype="multipart/form-data" class="flex gap-2">
        <?= csrf_field() ?>
        <input type="file" name="files[]" multiple required class="flex-1 border border-gray-300 rounded-lg px-4 py-2">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Upload</button>
    </form>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <?php if (!empty($media)): ?>
        <?php foreach ($media as $m): ?>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="aspect-square bg-gray-200 flex items-center justify-center text-3xl text-gray-400">
                <?php if (str_starts_with($m['mime_type'] ?? '', 'image/')): ?>
                    <img src="/<?= e($m['file_path']) ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    📄
                <?php endif; ?>
            </div>
            <div class="p-3">
                <p class="text-xs font-medium truncate"><?= e($m['original']) ?></p>
                <p class="text-xs text-gray-500"><?= e(number_format(($m['size'] ?? 0) / 1024, 1)) ?> KB</p>
                <div class="mt-2 flex justify-between">
                    <a href="/dashboard/media/<?= e($m['id']) ?>/download" class="text-xs text-blue-600 hover:underline">Download</a>
                    <form action="/dashboard/media/<?= e($m['id']) ?>" method="POST" onsubmit="return confirm('Delete?')" class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="text-xs text-red-600 hover:underline">Delete</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="col-span-4 text-center text-gray-500">No media uploaded yet.</p>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/dashboard.php'; ?>
