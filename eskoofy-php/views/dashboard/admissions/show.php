<?php $pageTitle = 'Admission Detail'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Admission #<?= e($admission['id']) ?></h1>
        <p class="text-gray-500">Applied: <?= e(date('M d, Y', strtotime($admission['submitted_at'] ?? $admission['created_at'] ?? 'now'))) ?></p>
    </div>
    <a href="/dashboard/admissions" class="text-gray-600 hover:text-gray-800">← Back</a>
</div>

<?php if (($admission['status'] ?? '') == 'submitted'): ?>
<div class="flex gap-3 mb-6">
    <form action="/dashboard/admissions/<?= e($admission['id']) ?>/approve" method="POST" onsubmit="return confirm('Approve this application?')">
        <?= csrf_field() ?>
        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">✓ Approve</button>
    </form>
    <form action="/dashboard/admissions/<?= e($admission['id']) ?>/reject" method="POST" onsubmit="return confirm('Reject this application?')">
        <?= csrf_field() ?>
        <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">✗ Reject</button>
    </form>
</div>
<?php endif; ?>

<?php if (($admission['status'] ?? '') == 'approved'): ?>
<div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 flex justify-between items-center">
    <p class="text-green-700">This application has been approved.</p>
    <form action="/dashboard/admissions/<?= e($admission['id']) ?>/enroll" method="POST" onsubmit="return confirm('Enroll this student?')">
        <?= csrf_field() ?>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">🎓 Enroll Student</button>
    </form>
</div>
<?php endif; ?>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Student Information</h2>
        <dl class="space-y-2 text-sm">
            <div class="flex"><dt class="w-36 text-gray-500">Name:</dt><dd class="font-medium"><?= e($admission['first_name']) ?> <?= e($admission['last_name']) ?></dd></div>
            <div class="flex"><dt class="w-36 text-gray-500">DOB:</dt><dd class="font-medium"><?= e(!empty($admission['date_of_birth']) ? date('M d, Y', strtotime($admission['date_of_birth'])) : '-') ?></dd></div>
            <div class="flex"><dt class="w-36 text-gray-500">Gender:</dt><dd class="font-medium"><?= e(ucfirst($admission['gender'] ?? '')) ?></dd></div>
            <div class="flex"><dt class="w-36 text-gray-500">Class:</dt><dd class="font-medium"><?= e($admission['class_name'] ?? '') ?></dd></div>
            <div class="flex"><dt class="w-36 text-gray-500">Phone:</dt><dd class="font-medium"><?= e($admission['phone'] ?? '-') ?></dd></div>
            <div class="flex"><dt class="w-36 text-gray-500">Email:</dt><dd class="font-medium"><?= e($admission['email'] ?? '-') ?></dd></div>
            <div class="flex"><dt class="w-36 text-gray-500">Address:</dt><dd class="font-medium"><?= e($admission['address'] ?? '-') ?></dd></div>
        </dl>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Parent Information</h2>
        <dl class="space-y-2 text-sm">
            <div class="flex"><dt class="w-36 text-gray-500">Father:</dt><dd class="font-medium"><?= e($admission['father_name'] ?? '-') ?></dd></div>
            <div class="flex"><dt class="w-36 text-gray-500">Father Phone:</dt><dd class="font-medium"><?= e($admission['father_phone'] ?? '-') ?></dd></div>
            <div class="flex"><dt class="w-36 text-gray-500">Mother:</dt><dd class="font-medium"><?= e($admission['mother_name'] ?? '-') ?></dd></div>
            <div class="flex"><dt class="w-36 text-gray-500">Mother Phone:</dt><dd class="font-medium"><?= e($admission['mother_phone'] ?? '-') ?></dd></div>
        </dl>

        <h3 class="font-bold mt-6 mb-3">Previous School</h3>
        <dl class="space-y-2 text-sm">
            <div class="flex"><dt class="w-36 text-gray-500">School:</dt><dd class="font-medium"><?= e($admission['previous_school'] ?? '-') ?></dd></div>
            <div class="flex"><dt class="w-36 text-gray-500">Class:</dt><dd class="font-medium"><?= e($admission['previous_class'] ?? '-') ?></dd></div>
            <div class="flex"><dt class="w-36 text-gray-500">Result:</dt><dd class="font-medium"><?= e($admission['previous_result'] ?? '-') ?></dd></div>
        </dl>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>