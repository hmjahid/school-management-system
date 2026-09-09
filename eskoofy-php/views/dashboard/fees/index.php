<?php $pageTitle = 'Fee Structure'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Fee Structure</h1>
    <a href="/dashboard/fees/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Add Fee</a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Name</th>
                    <th class="py-3 px-4 font-semibold border-b">Class</th>
                    <th class="py-3 px-4 font-semibold border-b">Type</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Amount</th>
                    <th class="py-3 px-4 font-semibold border-b">Frequency</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($fees)): ?>
                    <?php foreach ($fees as $fee): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($fee->name) ?></td>
                        <td class="py-3 px-4"><?= e($fee->class->name ?? 'All') ?></td>
                        <td class="py-3 px-4"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700"><?= e(ucfirst($fee->type ?? 'tuition')) ?></span></td>
                        <td class="py-3 px-4 text-right font-bold"><?= e(config('currency.symbol', '$')) ?><?= number_format($fee->amount, 2) ?></td>
                        <td class="py-3 px-4"><?= e(ucfirst($fee->frequency ?? 'monthly')) ?></td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="/dashboard/fees/<?= e($fee->id) ?>/edit" class="text-green-600 hover:underline text-sm">Edit</a>
                                <form action="/dashboard/fees/<?= e($fee->id) ?>" method="POST" onsubmit="return confirm('Are you sure?')">
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
                        <td colspan="6" class="py-8 text-center text-gray-500">No fee structures found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>