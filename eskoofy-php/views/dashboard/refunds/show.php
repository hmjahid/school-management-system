<?php $pageTitle = 'Refund Detail'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Refund #<?= e($refund['id'] ?? '') ?></h1>
        <p class="text-gray-500">Student: <?= e($refund['student_name'] ?? '') ?> | Admission: <?= e($refund['admission_number'] ?? '') ?></p>
    </div>
    <a href="/dashboard/refunds" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<?php if (($refund['status'] ?? '') === 'pending'): ?>
<div class="flex gap-3 mb-6">
    <form action="/dashboard/refunds/<?= e($refund['id']) ?>/process" method="POST" onsubmit="return confirm('Process this refund?')">
        <?= csrf_field() ?>
        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">Process Refund</button>
    </form>
    <form action="/dashboard/refunds/<?= e($refund['id']) ?>/cancel" method="POST" onsubmit="return confirm('Cancel this refund?')">
        <?= csrf_field() ?>
        <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">Cancel</button>
    </form>
</div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-lg">
    <dl class="space-y-3">
        <div class="flex"><dt class="w-40 text-gray-500">Amount:</dt><dd class="font-bold text-lg"><?= e(number_format((float) ($refund['amount'] ?? 0), 2)) ?></dd></div>
        <div class="flex"><dt class="w-40 text-gray-500">Status:</dt><dd>
            <?php
            $sc = ['pending' => 'bg-yellow-100 text-yellow-700', 'processed' => 'bg-green-100 text-green-700', 'cancelled' => 'bg-red-100 text-red-700'];
            ?>
            <span class="px-2 py-1 text-xs rounded-full <?= $sc[$refund['status'] ?? 'pending'] ?>"><?= e(ucfirst($refund['status'] ?? '')) ?></span>
        </dd></div>
        <div class="flex"><dt class="w-40 text-gray-500">Reason:</dt><dd class="font-medium"><?= e($refund['reason'] ?? '-') ?></dd></div>
        <div class="flex"><dt class="w-40 text-gray-500">Created:</dt><dd class="font-medium"><?= e($refund['created_at'] ?? '') ?></dd></div>
        <?php if ($refund['processed_at'] ?? null): ?>
        <div class="flex"><dt class="w-40 text-gray-500">Processed:</dt><dd class="font-medium"><?= e($refund['processed_at'] ?? '') ?> by <?= e($refund['processed_by_name'] ?? '') ?></dd></div>
        <?php endif; ?>
    </dl>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
