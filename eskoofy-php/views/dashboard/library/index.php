<?php $pageTitle = 'Library'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Library</h1>
    <div class="flex gap-2">
        <a href="/dashboard/library/issues" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">📋 Issues</a>
        <button onclick="document.getElementById('create-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Add Book</button>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/library" method="GET" class="flex flex-col sm:flex-row gap-4">
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Search by title, author, or ISBN..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        <select name="category" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">All Categories</option>
            <option value="fiction">Fiction</option>
            <option value="non-fiction">Non-Fiction</option>
            <option value="textbook">Textbook</option>
            <option value="reference">Reference</option>
        </select>
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Search</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">ISBN</th>
                    <th class="py-3 px-4 font-semibold border-b">Title</th>
                    <th class="py-3 px-4 font-semibold border-b">Author</th>
                    <th class="py-3 px-4 font-semibold border-b">Category</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Quantity</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Available</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($books)): ?>
                    <?php foreach ($books as $book): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-mono text-sm"><?= e($book->isbn ?? '-') ?></td>
                        <td class="py-3 px-4 font-medium"><?= e($book->title) ?></td>
                        <td class="py-3 px-4"><?= e($book->author ?? '-') ?></td>
                        <td class="py-3 px-4"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700"><?= e(ucfirst($book->category ?? '')) ?></span></td>
                        <td class="py-3 px-4 text-center"><?= e($book->quantity ?? 0) ?></td>
                        <td class="py-3 px-4 text-center">
                            <span class="font-bold <?= ($book->available ?? 0) > 0 ? 'text-green-600' : 'text-red-600' ?>"><?= e($book->available ?? 0) ?></span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="/dashboard/library/<?= e($book->id) ?>/edit" class="text-green-600 hover:underline text-sm">Edit</a>
                                <form action="/dashboard/library/<?= e($book->id) ?>" method="POST" onsubmit="return confirm('Delete?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="py-8 text-center text-gray-500">No books found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="create-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Add Book</h2>
        <form action="/dashboard/library" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                <input type="text" name="title" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ISBN</label>
                <input type="text" name="isbn" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Author</label>
                <input type="text" name="author" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="fiction">Fiction</option>
                    <option value="non-fiction">Non-Fiction</option>
                    <option value="textbook">Textbook</option>
                    <option value="reference">Reference</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                <input type="number" name="quantity" value="1" min="1" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>