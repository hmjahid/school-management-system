<?php $pageTitle = 'Add Budget Entry'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Add Budget Entry</h1>
    <a href="/dashboard/budgets" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <form action="/dashboard/budgets" method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <input type="text" name="title" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                <select name="type" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                <input type="number" name="amount" step="0.01" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                <select name="category" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="tuition">Tuition</option>
                    <option value="salary">Salary</option>
                    <option value="infrastructure">Infrastructure</option>
                    <option value="utilities">Utilities</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                <input type="date" name="budget_date" value="<?= e(date('Y-m-d')) ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2"></textarea>
        </div>
        <div class="flex justify-end space-x-3 pt-4">
            <a href="/dashboard/budgets" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
