<?php $pageTitle = 'Edit CMS Page'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <a href="/dashboard/cms" class="text-sm text-gray-500 hover:text-gray-700">← Back to CMS</a>
    <h1 class="text-2xl font-bold text-gray-800">Edit Page: <?= e($page['page']) ?></h1>
</div>

<div class="bg-white rounded-xl shadow-sm p-8">
    <form action="/dashboard/cms/<?= e($page['id']) ?>" method="POST" class="space-y-6">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                <input type="text" name="page" value="<?= e($page['page']) ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                <input type="text" name="title" value="<?= e($page['title'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title (English)</label>
                <input type="text" name="title_en" value="<?= e($page['title_en'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title (বাংলা)</label>
                <input type="text" name="title_bn" value="<?= e($page['title_bn'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
            <textarea name="content" rows="6" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"><?= e(is_string($page['content'] ?? null) ? $page['content'] : '') ?></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Content (English)</label>
            <textarea name="content_en" rows="6" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"><?= e(is_string($page['content_en'] ?? null) ? $page['content_en'] : '') ?></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Content (বাংলা)</label>
            <textarea name="content_bn" rows="6" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"><?= e(is_string($page['content_bn'] ?? null) ? $page['content_bn'] : '') ?></textarea>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                <input type="text" name="meta_description" value="<?= e($page['meta_description'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                <input type="text" name="meta_keywords" value="<?= e($page['meta_keywords'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div>
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" <?= !empty($page['is_active']) ? 'checked' : '' ?> class="rounded border-gray-300 text-blue-600">
                <span class="ml-2 text-sm text-gray-700">Active</span>
            </label>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Save</button>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/dashboard.php'; ?>
