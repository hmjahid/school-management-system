<?php $pageTitle = 'Reject Admission'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Reject Admission #<?= e($admission['id'] ?? '') ?></h1>
        <p class="text-gray-500"><?= e($admission['first_name'] ?? '') ?> <?= e($admission['last_name'] ?? '') ?> | Class: <?= e($admission['class_name'] ?? '') ?></p>
    </div>
    <a href="/dashboard/admissions/<?= e($admission['id'] ?? '') ?>" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<?php if (($admission['status'] ?? '') !== 'pending'): ?>
<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
    <p class="text-yellow-700">This application is currently <strong><?= e(strtoupper($admission['status'] ?? '')) ?></strong> and cannot be rejected.</p>
    <a href="/dashboard/admissions/<?= e($admission['id'] ?? '') ?>" class="text-blue-600 hover:underline text-sm inline-block mt-2">View admission details</a>
</div>
<?php else: ?>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-lg">
    <h2 class="text-lg font-bold mb-4 text-red-600">Confirm Rejection</h2>
    <div class="bg-gray-50 rounded-lg p-4 mb-6 space-y-2 text-sm">
        <div class="flex"><dt class="w-36 text-gray-500">Applicant:</dt><dd class="font-medium"><?= e($admission['first_name'] ?? '') ?> <?= e($admission['last_name'] ?? '') ?></dd></div>
        <div class="flex"><dt class="w-36 text-gray-500">Email:</dt><dd class="font-medium"><?= e($admission['email'] ?? '-') ?></dd></div>
        <div class="flex"><dt class="w-36 text-gray-500">Phone:</dt><dd class="font-medium"><?= e($admission['phone'] ?? '-') ?></dd></div>
        <div class="flex"><dt class="w-36 text-gray-500">Class:</dt><dd class="font-medium"><?= e($admission['class_name'] ?? '') ?></dd></div>
    </div>
    <form action="/dashboard/admissions/<?= e($admission['id'] ?? '') ?>/reject" method="POST" onsubmit="return confirm('Reject this application?')">
        <?= csrf_field() ?>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason</label>
            <textarea name="rejection_reason" rows="4" required class="w-full border border-gray-300 rounded-lg px-4 py-2" placeholder="Please specify why this application is being rejected..."></textarea>
        </div>
        <div class="flex justify-end space-x-3">
            <a href="/dashboard/admissions/<?= e($admission['id'] ?? '') ?>" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Reject Application</button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>