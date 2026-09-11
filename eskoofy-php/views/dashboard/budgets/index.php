<?php $pageTitle = 'Budget'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Budget Management</h1>
    <a href="/dashboard/budgets/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Add Budget</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-blue-600"><?= e(number_format($totalBudget, 2)) ?></div>
        <div class="text-gray-500">Total Budget (<?= e($year) ?>)</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-green-600"><?= e(number_format($totalBudget, 2)) ?></div>
        <div class="text-gray-500">Allocated Budget (<?= e($year) ?>)</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-gray-600"><?= e(number_format($total, 0)) ?></div>
        <div class="text-gray-500">Budget Entries</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/budgets" method="GET" class="flex flex-col sm:flex-row gap-4">
        <select name="year" class="border border-gray-300 rounded-lg px-4 py-2">
            <?php for ($y = date('Y') - 3; $y <= date('Y') + 1; $y++): ?>
            <option value="<?= $y ?>" <?= ($year ?? date('Y')) == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <select name="category_id" class="border border-gray-300 rounded-lg px-4 py-2">
            <option value="">All Categories</option>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat['id']) ?>" <?= ($categoryId ?? 0) == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Category</th>
                    <th class="py-3 px-4 font-semibold border-b">Period</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Amount</th>
                    <th class="py-3 px-4 font-semibold border-b">Notes</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($row['category_name'] ?? 'General') ?></td>
                        <td class="py-3 px-4 text-sm">
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700"><?= e(ucfirst($row['period_type'] ?? 'monthly')) ?></span>
                            <div class="mt-1 text-xs text-gray-500"><?= e($row['period_start'] ?? '') ?> &rarr; <?= e($row['period_end'] ?? '') ?></div>
                        </td>
                        <td class="py-3 px-4 text-center font-medium"><?= e(number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e(mb_strimwidth($row['notes'] ?? '-', 0, 60, '...')) ?></td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <form action="/dashboard/budgets/<?= e($row['id']) ?>" method="POST" onsubmit="return confirm('Delete this budget?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="py-8 text-center text-gray-500">No budget entries found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>