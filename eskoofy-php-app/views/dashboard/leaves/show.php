<?php $pageTitle = 'Leave Request'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Leave Request</h1>
        <p class="text-gray-500"><?= e(date('M j, Y', strtotime($leave['from_date']))) ?> &rarr; <?= e(date('M j, Y', strtotime($leave['to_date']))) ?></p>
    </div>
    <a href="/dashboard/leaves" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-xl">
    <dl class="grid grid-cols-2 gap-4">
        <div><dt class="text-xs text-gray-500">Teacher</dt><dd class="font-medium"><?= e($leave['teacher_name'] ?? '—') ?></dd></div>
        <div><dt class="text-xs text-gray-500">Type</dt><dd><?= e($leave['leave_type_bn'] ?? $leave['leave_type'] ?? '') ?></dd></div>
        <div><dt class="text-xs text-gray-500">Status</dt><dd><?= e(ucfirst($leave['status'] ?? 'pending')) ?></dd></div>
        <div><dt class="text-xs text-gray-500">Days</dt><dd><?= e((string) ($leave['days'] ?? 1)) ?></dd></div>
        <?php if (!empty($leave['approver_name'])): ?>
        <div><dt class="text-xs text-gray-500">Decided by</dt><dd><?= e($leave['approver_name']) ?></dd></div>
        <div><dt class="text-xs text-gray-500">Decided at</dt><dd><?= !empty($leave['decided_at']) ? e(date('Y-m-d H:i', strtotime($leave['decided_at']))) : '—' ?></dd></div>
        <?php endif; ?>
    </dl>
    <div class="mt-4 border-t pt-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-1">Reason</h3>
        <p class="text-sm text-gray-600"><?= e($leave['reason'] ?? '') ?></p>
    </div>
    <?php if (!empty($leave['approver_note'])): ?>
    <div class="mt-4 border-t pt-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-1">Approver note</h3>
        <p class="text-sm text-gray-600"><?= e($leave['approver_note']) ?></p>
    </div>
    <?php endif; ?>
</div>

<?php if (($leave['status'] ?? '') === 'pending'): ?>
<div class="grid md:grid-cols-2 gap-6 mt-6 max-w-xl">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold mb-3">Approve</h3>
        <form action="/dashboard/leaves/<?= e($leave['id']) ?>/approve" method="POST" class="space-y-3">
            <?= csrf_field() ?>
            <textarea name="approver_note" rows="3" maxlength="1000" placeholder="Note (optional)" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500"></textarea>
            <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition">Approve</button>
        </form>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold mb-3">Reject</h3>
        <form action="/dashboard/leaves/<?= e($leave['id']) ?>/reject" method="POST" class="space-y-3">
            <?= csrf_field() ?>
            <textarea name="approver_note" rows="3" maxlength="1000" placeholder="Reason (optional)" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500"></textarea>
            <button type="submit" class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 transition">Reject</button>
        </form>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 md:col-span-2">
        <form action="/dashboard/leaves/<?= e($leave['id']) ?>/cancel" method="POST" onsubmit="return confirm('Cancel this request?')">
            <?= csrf_field() ?>
            <button type="submit" class="text-red-600 hover:underline text-sm">Cancel request</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>