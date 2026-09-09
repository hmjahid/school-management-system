<?php $pageTitle = 'Student Results'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Exam Results: <?= e($student['name'] ?? '') ?></h1>
        <p class="text-gray-500">Admission: <?= e($student['admission_number'] ?? '') ?> | Class: <?= e($student['class_name'] ?? '') ?></p>
    </div>
    <a href="/dashboard/students/<?= e($student['id']) ?>" class="text-gray-600 hover:text-gray-800">&larr; Back to Student</a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-lg font-bold">All Exam Results</h2>
    </div>
    <?php if (!empty($results)): ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Exam</th>
                    <th class="py-3 px-4 font-semibold border-b">Subject</th>
                    <th class="py-3 px-4 font-semibold border-b">Date</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Obtained</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Total</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Percentage</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Grade</th>
                    <th class="py-3 px-4 font-semibold border-b">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalObtained = 0;
                $totalMarks = 0;
                ?>
                <?php foreach ($results as $result): ?>
                <?php
                $totalObtained += (float) ($result['obtained_marks'] ?? 0);
                $totalMarks += (float) ($result['total_marks'] ?? 0);
                $pct = $result['total_marks'] > 0 ? round(100 * $result['obtained_marks'] / $result['total_marks'], 1) : 0;
                ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4 font-medium"><?= e($result['exam_name'] ?? '') ?></td>
                    <td class="py-3 px-4"><?= e($result['subject_name'] ?? '') ?></td>
                    <td class="py-3 px-4 text-sm text-gray-500"><?= e($result['exam_date'] ?? '') ?></td>
                    <td class="py-3 px-4 text-center font-medium"><?= e($result['obtained_marks'] ?? 0) ?></td>
                    <td class="py-3 px-4 text-center"><?= e($result['total_marks'] ?? 0) ?></td>
                    <td class="py-3 px-4 text-center">
                        <span class="font-medium <?= $pct >= 50 ? 'text-green-600' : 'text-red-600' ?>"><?= e($pct) ?>%</span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <?php
                        $gradeColor = 'bg-gray-100 text-gray-700';
                        if (($result['grade'] ?? '') === 'Pass') $gradeColor = 'bg-green-100 text-green-700';
                        if (($result['grade'] ?? '') === 'Fail') $gradeColor = 'bg-red-100 text-red-700';
                        ?>
                        <span class="px-2 py-1 text-xs rounded-full <?= $gradeColor ?>"><?= e($result['grade'] ?? '-') ?></span>
                    </td>
                    <td class="py-3 px-4 text-sm text-gray-500"><?= e($result['remarks'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 font-bold">
                    <td class="py-3 px-4" colspan="3">Overall</td>
                    <td class="py-3 px-4 text-center"><?= e($totalObtained) ?></td>
                    <td class="py-3 px-4 text-center"><?= e($totalMarks) ?></td>
                    <td class="py-3 px-4 text-center"><?= e($totalMarks > 0 ? round(100 * $totalObtained / $totalMarks, 1) : 0) ?>%</td>
                    <td class="py-3 px-4 text-center" colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else: ?>
    <div class="p-8 text-center text-gray-500">No results available for this student.</div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
