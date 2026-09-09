<?php $pageTitle = 'Payment Detail'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Payment Detail</h1>
    <a href="/dashboard/payments" class="text-gray-600 hover:text-gray-800">← Back to Payments</a>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Transaction Information</h2>
        <dl class="space-y-3">
            <div class="flex justify-between"><dt class="text-gray-500">Transaction ID:</dt><dd class="font-mono font-medium"><?= e($payment['transaction_id'] ?? '') ?></dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Status:</dt><dd><span class="px-2 py-1 text-xs rounded-full <?= ($payment['payment_status'] ?? '') == 'completed' ? 'bg-green-100 text-green-700' : (($payment['payment_status'] ?? '') == 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') ?>"><?= e(ucfirst($payment['payment_status'] ?? '')) ?></span></dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Amount:</dt><dd class="font-bold text-lg"><?= e(format_currency((float)($payment['amount'] ?? 0))) ?></dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Gateway:</dt><dd class="font-medium"><?= e(ucfirst($payment['payment_method'] ?? '')) ?></dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Method:</dt><dd class="font-medium"><?= e($payment['payment_method'] ?? '-') ?></dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Date:</dt><dd class="font-medium"><?= e(date('M d, Y H:i:s', strtotime($payment['created_at'] ?? 'now'))) ?></dd></div>
        </dl>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Student Information</h2>
        <dl class="space-y-3">
            <div class="flex justify-between"><dt class="text-gray-500">Name:</dt><dd class="font-medium"><?= e($payment['student_name'] ?? '') ?></dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">ID:</dt><dd class="font-medium"><?= e($payment['admission_number'] ?? '') ?></dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Class:</dt><dd class="font-medium"><?= e($payment['class_name'] ?? '') ?></dd></div>
        </dl>

        <?php if (($payment['payment_status'] ?? '') == 'completed'): ?>
        <div class="mt-6 pt-4 border-t">
            <h3 class="font-bold mb-3">Actions</h3>
            <form action="/dashboard/payments/<?= e($payment['id']) ?>/refund" method="POST" onsubmit="return confirm('Are you sure you want to refund this payment?')">
                <?= csrf_field() ?>
                <div class="flex gap-2">
                    <input type="number" name="refund_amount" value="<?= e($payment['amount'] ?? 0) ?>" step="0.01" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-32" placeholder="Amount">
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700">Refund</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($payment['meta'] ?? null): ?>
<div class="bg-white rounded-xl shadow-sm p-6 mt-6">
    <h2 class="text-lg font-bold mb-4">Gateway Response</h2>
    <pre class="bg-gray-50 p-4 rounded-lg text-sm overflow-x-auto"><?= e(json_encode($payment['meta'], JSON_PRETTY_PRINT)) ?></pre>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>