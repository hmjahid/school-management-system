<?php $pageTitle = 'Gallery'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Gallery Management</h1>
    <button onclick="document.getElementById('upload-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Upload Photos</button>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/galleries" method="GET" class="flex gap-4">
        <select name="category" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">All Categories</option>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                <option value="<?= e($category->slug) ?>" <?= ($categorySlug ?? '') == $category->slug ? 'selected' : '' ?>><?= e($category->name) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
    </form>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
    <?php if (!empty($photos)): ?>
        <?php foreach ($photos as $photo): ?>
        <div class="relative group">
            <div class="aspect-square bg-gray-200 rounded-xl overflow-hidden">
                <img src="/uploads/gallery/<?= e($photo->image) ?>" alt="<?= e($photo->title) ?>" class="w-full h-full object-cover">
            </div>
            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 rounded-xl transition flex items-center justify-center opacity-0 group-hover:opacity-100">
                <form action="/dashboard/galleries/<?= e($photo->id) ?>" method="POST" onsubmit="return confirm('Delete this photo?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700">Delete</button>
                </form>
            </div>
            <p class="text-xs text-gray-500 mt-1 truncate"><?= e($photo->title) ?></p>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-full text-center py-12 text-gray-500">No photos in the gallery.</div>
    <?php endif; ?>
</div>

<div id="upload-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-lg">
        <h2 class="text-xl font-bold mb-4">Upload Photos</h2>
        <form action="/dashboard/galleries" method="POST" enctype="multipart/form-data" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category_id" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Uncategorized</option>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category->id) ?>"><?= e($category->name) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                <input type="text" name="title" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Photos *</label>
                <input type="file" name="images[]" multiple accept="image/*" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <p class="text-xs text-gray-500 mt-1">You can select multiple photos</p>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('upload-modal').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Upload</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>