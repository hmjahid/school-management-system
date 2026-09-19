<?php $pageTitle = 'Certificate'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Certificate</h1>
    <div class="flex gap-2">
        <a href="/dashboard/certificates/<?= e($cert['id']) ?>/print" target="_blank" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Print</a>
        <a href="/dashboard/certificates/<?= e($cert['id']) ?>/edit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Edit</a>
        <a href="/dashboard/certificates" class="text-gray-600 hover:text-gray-800 px-4 py-2">&larr; Back</a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-xl">
    <dl class="grid grid-cols-2 gap-4">
        <div><dt class="text-xs text-gray-500">Certificate Number</dt><dd class="font-medium"><?= e($cert['certificate_number'] ?? '') ?></dd></div>
        <div><dt class="text-xs text-gray-500">Student</dt><dd class="font-medium"><?= e($cert['student_name'] ?? '') ?></dd></div>
        <div><dt class="text-xs text-gray-500">Type</dt><dd><?= e(ucfirst($cert['certificate_type'] ?? '')) ?></dd></div>
        <div><dt class="text-xs text-gray-500">Issue Date</dt><dd><?= e(date('d M Y', strtotime($cert['issue_date'] ?? 'now'))) ?></dd></div>
        <div><dt class="text-xs text-gray-500">Status</dt><dd><?= e(ucfirst($cert['status'] ?? 'draft')) ?></dd></div>
    </dl>
    <?php if (!empty($cert['body'])): ?>
    <div class="mt-4 border-t pt-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-2">Body</h3>
        <?php $bodyLines = is_array($cert['body']) ? $cert['body'] : [$cert['body']]; ?>
        <?php foreach ($bodyLines as $line): ?>
        <p class="text-sm text-gray-600"><?= e((string) $line) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>