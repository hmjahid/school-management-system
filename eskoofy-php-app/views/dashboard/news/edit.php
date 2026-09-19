<?php $pageTitle = 'Edit News'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Edit News</h1>
    <a href="/dashboard/news" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <form action="/dashboard/news/<?= e($news['id']) ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <input type="text" name="title" required maxlength="255" value="<?= e($news['title'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                <input type="text" name="slug" maxlength="255" value="<?= e($news['slug'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <input type="text" name="category" maxlength="120" value="<?= e($news['category'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Content *</label>
            <textarea name="content" rows="6" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"><?= e($news['content'] ?? '') ?></textarea>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Published at</label>
                <input type="date" name="published_at" value="<?= !empty($news['published_at']) ? e(date('Y-m-d', strtotime($news['published_at']))) : '' ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Author name</label>
                <input type="text" name="author_name" maxlength="120" value="<?= e($news['author_name'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="is_published" value="0">
            <input type="checkbox" name="is_published" value="1" <?= !empty($news['is_published']) ? 'checked' : '' ?> class="rounded"> Published
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="is_event" value="0">
            <input type="checkbox" name="is_event" value="1" <?= !empty($news['is_event']) ? 'checked' : '' ?> class="rounded"> Is event
        </label>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
            <input type="file" name="image" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <?php if (!empty($news['image_url'])): ?>
            <p class="text-xs text-gray-500 mt-1">Current: <?= e($news['image_url']) ?></p>
            <?php endif; ?>
        </div>
        <div class="flex justify-end gap-2">
            <form action="/dashboard/news/<?= e($news['id']) ?>" method="POST" class="inline mr-auto" onsubmit="return confirm('Delete this article?')">
                <?= csrf_field() ?>
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="text-red-600 hover:underline px-4 py-2">Delete</button>
            </form>
            <a href="/dashboard/news" class="text-gray-600 hover:text-gray-800 px-4 py-2">Cancel</a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Update</button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>