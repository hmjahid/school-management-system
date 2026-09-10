<?php $pageTitle = 'Overdue Books'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <a href="/dashboard/library-reports" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    <h1 class="text-2xl font-bold text-gray-800">Overdue Books</h1>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-left">
        <thead><tr class="bg-gray-50"><th class="py-3 px-4 font-semibold border-b">Book</th><th class="py-3 px-4 font-semibold border-b">Borrower</th><th class="py-3 px-4 font-semibold border-b">Due Date</th><th class="py-3 px-4 font-semibold border-b">Days Overdue</th></tr></thead>
        <tbody>
            <?php if (!empty($rows)): ?>
                <?php foreach ($rows as $r): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4 font-medium"><?= e($r['title'] ?? '-') ?></td>
                    <td class="py-3 px-4"><?= e($r['borrower'] ?? '-') ?></td>
                    <td class="py-3 px-4 text-sm"><?= e(date('M d, Y', strtotime($r['due_date'] ?? 'now'))) ?></td>
                    <td class="py-3 px-4 text-sm text-red-600 font-bold"><?= e((int)($r['days_overdue'] ?? 0)) ?> days</td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" class="py-8 text-center text-gray-500">No overdue books. </td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
