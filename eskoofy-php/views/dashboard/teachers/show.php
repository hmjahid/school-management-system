<?php $pageTitle = 'Teacher Detail'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800"><?= e($teacher['name']) ?></h1>
        <p class="text-gray-500">ID: <?= e($teacher['employee_id']) ?> | Subject: <?= e($teacher['subject_name'] ?? '') ?></p>
    </div>
    <div class="flex space-x-2">
        <a href="/dashboard/teachers/<?= e($teacher['id']) ?>/edit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Edit</a>
        <a href="/dashboard/teachers" class="text-gray-600 hover:text-gray-800">← Back</a>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Personal Information</h2>
        <dl class="space-y-2">
            <div class="flex"><dt class="w-40 text-gray-500">Name:</dt><dd class="font-medium"><?= e($teacher['name']) ?></dd></div>
            <div class="flex"><dt class="w-40 text-gray-500">Email:</dt><dd class="font-medium"><?= e($teacher['email']) ?></dd></div>
            <div class="flex"><dt class="w-40 text-gray-500">Phone:</dt><dd class="font-medium"><?= e($teacher['phone'] ?? '-') ?></dd></div>
            <div class="flex"><dt class="w-40 text-gray-500">Date of Birth:</dt><dd class="font-medium"><?= e(!empty($teacher['date_of_birth']) ? date('M d, Y', strtotime($teacher['date_of_birth'])) : '-') ?></dd></div>
            <div class="flex"><dt class="w-40 text-gray-500">Gender:</dt><dd class="font-medium"><?= e(ucfirst($teacher['gender'] ?? '-')) ?></dd></div>
            <div class="flex"><dt class="w-40 text-gray-500">Blood Group:</dt><dd class="font-medium"><?= e($teacher['blood_group'] ?? '-') ?></dd></div>
            <div class="flex"><dt class="w-40 text-gray-500">Address:</dt><dd class="font-medium"><?= e($teacher['address'] ?? '-') ?></dd></div>
        </dl>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Academic Information</h2>
        <dl class="space-y-2">
            <div class="flex"><dt class="w-40 text-gray-500">Teacher ID:</dt><dd class="font-medium"><?= e($teacher['employee_id']) ?></dd></div>
            <div class="flex"><dt class="w-40 text-gray-500">Subject:</dt><dd class="font-medium"><?= e($teacher['subject_name'] ?? '-') ?></dd></div>
            <div class="flex"><dt class="w-40 text-gray-500">Department:</dt><dd class="font-medium"><?= e($teacher['department'] ?? '-') ?></dd></div>
            <div class="flex"><dt class="w-40 text-gray-500">Qualification:</dt><dd class="font-medium"><?= e($teacher['qualification'] ?? '-') ?></dd></div>
            <div class="flex"><dt class="w-40 text-gray-500">Experience:</dt><dd class="font-medium"><?= e($teacher['experience'] ?? '0') ?> years</dd></div>
            <div class="flex"><dt class="w-40 text-gray-500">Joining Date:</dt><dd class="font-medium"><?= e(!empty($teacher['joining_date']) ? date('M d, Y', strtotime($teacher['joining_date'])) : '-') ?></dd></div>
            <div class="flex"><dt class="w-40 text-gray-500">Salary:</dt><dd class="font-medium"><?= e(config('currency.symbol', '$')) ?><?= number_format($teacher['salary'] ?? 0, 2) ?></dd></div>
            <div class="flex"><dt class="w-40 text-gray-500">Status:</dt><dd class="font-medium"><span class="px-2 py-1 text-xs rounded-full <?= $teacher['status'] == 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= e(ucfirst($teacher['status'])) ?></span></dd></div>
        </dl>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mt-6">
    <h2 class="text-lg font-bold mb-4">Assigned Classes</h2>
    <?php if (!empty($teacher['assignedClasses'])): ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b">
                    <th class="py-3 px-4 font-semibold">Class</th>
                    <th class="py-3 px-4 font-semibold">Section</th>
                    <th class="py-3 px-4 font-semibold">Subject</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teacher['assignedClasses'] as $assignment): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4"><?= e($assignment['class_name'] ?? '') ?></td>
                    <td class="py-3 px-4"><?= e($assignment['section_name'] ?? '') ?></td>
                    <td class="py-3 px-4"><?= e($assignment['subject_name'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <p class="text-gray-500 text-center py-4">No classes assigned yet.</p>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>