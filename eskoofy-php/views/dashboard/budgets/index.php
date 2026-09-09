<?php $pageTitle = 'Budget'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Budget Management</h1>
    <a href="/dashboard/budgets/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Add Entry</a>
</div>

<div class="grid grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-green-600"><?= e(number_format($totalBudget, 2)) ?></div>
        <div class="text-gray-500">Total Income (<?= e($year) ?>)</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-red-600"><?= e(number_format($totalExpense, 2)) ?></div>
        <div class="text-gray-500">Total Expense (<?= e($year) ?>)</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold <?= ($totalBudget - $totalExpense) >= 0 ? 'text-blue-600' : 'text-red-600' ?>"><?= e(number_format($totalBudget - $totalExpense, 2)) ?></div>
        <div class="text-gray-500">Net Balance</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/budgets" method="GET" class="flex flex-col sm:flex-row gap-4">
        <select name="year" class="border border-gray-300 rounded-lg px-4 py-2">
            <?php for ($y = date('Y') - 3; $y <= date('Y'); $y++): ?>
            <option value="<?= $y ?>" <?= ($year ?? date('Y')) == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <select name="category" class="border border-gray-300 rounded-lg px-4 py-2">
            <option value="">All Categories</option>
            <option value="tuition" <?= ($category ?? '') === 'tuition' ? 'selected' : '' ?>>Tuition</option>
            <option value="salary" <?= ($category ?? '') === 'salary' ? 'selected' : '' ?>>Salary</option>
            <option value="infrastructure" <?= ($category ?? '') === 'infrastructure' ? 'selected' : '' ?>>Infrastructure</option>
            <option value="utilities" <?= ($category ?? '') === 'utilities' ? 'selected' : '' ?>>Utilities</option>
            <option value="other" <?= ($category ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
        </select>
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Title</th>
                    <th class="py-3 px-4 font-semibold border-b">Type</th>
                    <th class="py-3 px-4 font-semibold border-b">Category</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Amount</th>
                    <th class="py-3 px-4 font-semibold border-b">Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Created By</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($row['title'] ?? '') ?></td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded-full <?= ($row['type'] ?? '') === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= e(ucfirst($row['type'] ?? '')) ?></span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e(ucfirst($row['category'] ?? '')) ?></td>
                        <td class="py-3 px-4 text-center font-medium"><?= e(number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
                        <td class="py-3 px-4 text-sm"><?= e($row['budget_date'] ?? '') ?></td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($row['creator_name'] ?? '') ?></td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <form action="/dashboard/budgets/<?= e($row['id']) ?>" method="POST" onsubmit="return confirm('Delete?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="py-8 text-center text-gray-500">No budget entries found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
