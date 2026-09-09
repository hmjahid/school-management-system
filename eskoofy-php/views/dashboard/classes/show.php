<?php $pageTitle = 'Class Detail'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800"><?= e($class->name) ?></h1>
        <p class="text-gray-500">Shift: <?= e(ucfirst($class->shift ?? '')) ?> | Monthly Fee: <?= e(config('currency.symbol', '$')) ?><?= number_format($class->monthly_fee ?? 0, 2) ?></p>
    </div>
    <div class="flex space-x-2">
        <a href="/dashboard/classes/<?= e($class->id) ?>/edit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Edit</a>
        <a href="/dashboard/classes" class="text-gray-600 hover:text-gray-800">← Back</a>
    </div>
</div>

<div class="grid md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-blue-600"><?= e($class->sections->count()) ?></div>
        <div class="text-gray-500">Sections</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-green-600"><?= e($class->students_count ?? 0) ?></div>
        <div class="text-gray-500">Total Students</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-purple-600"><?= e($class->teachers_count ?? 0) ?></div>
        <div class="text-gray-500">Teachers</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <h2 class="text-lg font-bold mb-4">Sections</h2>
    <?php if (!empty($class->sections)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php foreach ($class->sections as $section): ?>
        <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition">
            <h3 class="font-bold"><?= e($section->name) ?></h3>
            <p class="text-sm text-gray-500"><?= e($section->students_count ?? 0) ?> students</p>
            <p class="text-sm text-gray-500">Teacher: <?= e($section->teacher->name ?? 'Not assigned') ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-gray-500">No sections created yet.</p>
    <?php endif; ?>
</div>

<div class="bg-white rounded-xl shadow-sm p-6">
    <h2 class="text-lg font-bold mb-4">Students in this Class</h2>
    <?php if (!empty($class->students)): ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b">
                    <th class="py-3 px-4 font-semibold">Roll</th>
                    <th class="py-3 px-4 font-semibold">Name</th>
                    <th class="py-3 px-4 font-semibold">Section</th>
                    <th class="py-3 px-4 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($class->students as $student): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4"><?= e($student->roll ?? '-') ?></td>
                    <td class="py-3 px-4"><a href="/dashboard/students/<?= e($student->id) ?>" class="text-blue-600 hover:underline"><?= e($student->name) ?></a></td>
                    <td class="py-3 px-4"><?= e($student->section->name ?? '-') ?></td>
                    <td class="py-3 px-4"><span class="px-2 py-1 text-xs rounded-full <?= $student->status == 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= e(ucfirst($student->status)) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <p class="text-gray-500">No students enrolled in this class.</p>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>