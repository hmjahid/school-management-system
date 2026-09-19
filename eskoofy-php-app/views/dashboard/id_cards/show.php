<?php $pageTitle = 'Student ID Card'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Student ID Card</h1>
    <div class="flex gap-2">
        <a href="/dashboard/id-cards/<?= e($card['id']) ?>/print" target="_blank" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Print</a>
        <a href="/dashboard/id-cards/<?= e($card['id']) ?>/edit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Edit</a>
        <a href="/dashboard/id-cards" class="text-gray-600 hover:text-gray-800 px-4 py-2">&larr; Back</a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-xl">
    <dl class="grid grid-cols-2 gap-4">
        <div><dt class="text-xs text-gray-500">Card Number</dt><dd class="font-medium"><?= e($card['id_card_number'] ?? '') ?></dd></div>
        <div><dt class="text-xs text-gray-500">Student</dt><dd class="font-medium"><?= e($card['student_name'] ?? '') ?></dd></div>
        <div><dt class="text-xs text-gray-500">Class</dt><dd><?= e($card['class_name'] ?? '') ?></dd></div>
        <div><dt class="text-xs text-gray-500">Section</dt><dd><?= e($card['section_name'] ?? '-') ?></dd></div>
        <div><dt class="text-xs text-gray-500">Roll</dt><dd><?= e($card['roll_number'] ?? '-') ?></dd></div>
        <div><dt class="text-xs text-gray-500">Blood group</dt><dd><?= e($card['blood_group'] ?? '-') ?></dd></div>
        <div><dt class="text-xs text-gray-500">Issue date</dt><dd><?= e(date('d M Y', strtotime($card['issue_date'] ?? 'now'))) ?></dd></div>
        <div><dt class="text-xs text-gray-500">Expiry</dt><dd><?= !empty($card['expiry_date']) ? e(date('d M Y', strtotime($card['expiry_date']))) : 'Never' ?></dd></div>
        <div><dt class="text-xs text-gray-500">Status</dt><dd><?= e(ucfirst($card['status'] ?? 'active')) ?></dd></div>
    </dl>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>