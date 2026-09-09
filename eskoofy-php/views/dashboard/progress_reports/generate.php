<?php $pageTitle = 'Progress Report'; ?>
<?php ob_start(); ?>

<div class="mb-6 print:hidden">
    <a href="/dashboard/progress-reports" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    <button onclick="window.print()" class="ml-4 text-sm bg-blue-600 text-white px-3 py-1 rounded">Print</button>
</div>

<div class="bg-white rounded-xl shadow-sm p-8">
    <div class="text-center mb-6 pb-4 border-b">
        <h1 class="text-2xl font-bold">Student Progress Report</h1>
        <p class="text-gray-500"><?= e($student['class_name'] ?? '') ?><?= !empty($student['section_name']) ? ' - ' . e($student['section_name']) : '' ?></p>
    </div>

    <div class="grid md:grid-cols-2 gap-6 mb-6">
        <div>
            <p class="text-xs text-gray-500">Student Name</p>
            <p class="font-bold"><?= e($student['name']) ?></p>
        </div>
        <div>
            <p class="text-xs text-gray-500">Admission No.</p>
            <p class="font-bold"><?= e($student['admission_number']) ?></p>
        </div>
        <div>
            <p class="text-xs text-gray-500">Average Marks</p>
            <p class="font-bold"><?= e(number_format($average, 2)) ?></p>
        </div>
        <div>
            <p class="text-xs text-gray-500">Attendance Rate</p>
            <p class="font-bold"><?= e(number_format($attendanceRate, 1)) ?>%</p>
        </div>
    </div>

    <h3 class="text-lg font-bold mb-2">Exam Results</h3>
    <table class="w-full text-left">
        <thead><tr class="border-b bg-gray-50"><th class="py-2 px-3">Exam</th><th class="py-2 px-3">Subject</th><th class="py-2 px-3 text-right">Marks</th><th class="py-2 px-3">Grade</th></tr></thead>
        <tbody>
            <?php if (!empty($results)): ?>
                <?php foreach ($results as $r): ?>
                <tr class="border-b">
                    <td class="py-2 px-3"><?= e($r['exam_name']) ?></td>
                    <td class="py-2 px-3"><?= e($r['subject_name']) ?></td>
                    <td class="py-2 px-3 text-right"><?= e(number_format((float)$r['marks'], 1)) ?></td>
                    <td class="py-2 px-3"><?= e($r['grade'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" class="py-4 text-center text-gray-500">No results yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/dashboard.php'; ?>
