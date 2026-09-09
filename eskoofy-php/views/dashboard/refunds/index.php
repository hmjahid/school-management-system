<?php $pageTitle = 'Refunds'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Refund Management</h1>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/refunds" method="GET" class="flex flex-col sm:flex-row gap-4">
        <select name="status" class="border border-gray-300 rounded-lg px-4 py-2">
            <option value="">All Status</option>
            <option value="pending" <?= ($status ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="processed" <?= ($status ?? '') === 'processed' ? 'selected' : '' ?>>Processed</option>
            <option value="cancelled" <?= ($status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Student</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Amount</th>
                    <th class="py-3 px-4 font-semibold border-b">Reason</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Status</th>
                    <th class="py-3 px-4 font-semibold border-b">Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($row['student_name'] ?? '') ?></td>
                        <td class="py-3 px-4 text-center font-medium"><?= e(number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
                        <td class="py-3 px-4 text-sm text-gray-500 max-w-xs truncate"><?= e($row['reason'] ?? '-') ?></td>
                        <td class="py-3 px-4 text-center">
                            <?php
                            $sc = [
                                'pending' => 'bg-yellow-100 text-yellow-700',
                                'processed' => 'bg-green-100 text-green-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                            ];
                            ?>
                            <span class="px-2 py-1 text-xs rounded-full <?= $sc[$row['status'] ?? 'pending'] ?>"><?= e(ucfirst($row['status'] ?? '')) ?></span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($row['created_at'] ?? '') ?></td>
                        <td class="py-3 px-4">
                            <a href="/dashboard/refunds/<?= e($row['id']) ?>" class="text-blue-600 hover:underline text-sm">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="py-8 text-center text-gray-500">No refunds found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($lastPage > 1): ?>
    <div class="p-6 flex justify-center space-x-2">
        <?php for ($i = 1; $i <= $lastPage; $i++): ?>
        <a href="?page=<?= $i ?>&status=<?= e($status ?? '') ?>" class="px-3 py-1 rounded-lg <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
