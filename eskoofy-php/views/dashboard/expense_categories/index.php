<?php $pageTitle = 'Expense Categories'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Expense Categories</h1>
    <button onclick="document.getElementById('create-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Add Category</button>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Name</th>
                    <th class="py-3 px-4 font-semibold border-b">Description</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Total Expenses</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($category->name) ?></td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($category->description ?? '-') ?></td>
                        <td class="py-3 px-4 text-right font-bold text-red-600"><?= e(config('currency.symbol', '$')) ?><?= number_format($category->total_expenses ?? 0, 2) ?></td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="/dashboard/expense-categories/<?= e($category->id) ?>/edit" class="text-green-600 hover:underline text-sm">Edit</a>
                                <form action="/dashboard/expense-categories/<?= e($category->id) ?>" method="POST" onsubmit="return confirm('Delete?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="py-8 text-center text-gray-500">No categories found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="create-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Add Category</h2>
        <form action="/dashboard/expense-categories" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2"></textarea>
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