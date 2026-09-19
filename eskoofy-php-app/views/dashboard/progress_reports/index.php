<?php $pageTitle = 'Progress Reports'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Progress Reports</h1>
    <p class="text-gray-500">Generate per-student progress reports</p>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Admission</th>
                    <th class="py-3 px-4 font-semibold border-b">Name</th>
                    <th class="py-3 px-4 font-semibold border-b">Class</th>
                    <th class="py-3 px-4 font-semibold border-b">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($students)): ?>
                    <?php foreach ($students as $s): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4"><?= e($s['admission_number']) ?></td>
                        <td class="py-3 px-4"><?= e($s['name']) ?></td>
                        <td class="py-3 px-4"><?= e($s['class_name'] ?? '') ?></td>
                        <td class="py-3 px-4"><a href="/dashboard/progress-reports/<?= e($s['id']) ?>/generate" class="text-blue-600 hover:underline text-sm">Generate</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="py-8 text-center text-gray-500">No students found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
