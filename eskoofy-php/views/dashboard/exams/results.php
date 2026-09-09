<?php $pageTitle = 'Enter Exam Results'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Results Entry: <?= e($exam['name'] ?? '') ?></h1>
        <p class="text-gray-500"><?= e(ucfirst($exam['exam_type'] ?? '')) ?> | Total Marks: <?= e($exam['total_marks'] ?? '') ?> | Passing: <?= e($exam['passing_marks'] ?? '') ?></p>
    </div>
    <div class="flex space-x-2">
        <form action="/dashboard/exams/<?= e($exam['id']) ?>/publish" method="POST" onsubmit="return confirm('Publish/unpublish this exam?')">
            <?= csrf_field() ?>
            <button type="submit" class="<?= ($exam['is_published'] ?? 0) ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' ?> text-white px-4 py-2 rounded-lg transition">
                <?= ($exam['is_published'] ?? 0) ? 'Unpublish' : 'Publish' ?>
            </button>
        </form>
        <a href="/dashboard/exams/<?= e($exam['id']) ?>" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
    </div>
</div>

<?php if (!empty($students)): ?>
<form action="/dashboard/exams/<?= e($exam['id']) ?>/results" method="POST">
    <?= csrf_field() ?>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold">Enter Marks for <?= count($students) ?> Students</h2>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Save All Results</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="py-3 px-4 font-semibold border-b sticky left-0 bg-gray-50">Roll</th>
                        <th class="py-3 px-4 font-semibold border-b sticky left-16 bg-gray-50">Student</th>
                        <?php foreach ($subjects as $subject): ?>
                        <th class="py-3 px-4 font-semibold border-b text-center min-w-[100px]"><?= e($subject['name'] ?? '') ?></th>
                        <?php endforeach; ?>
                        <th class="py-3 px-4 font-semibold border-b text-center">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 sticky left-0 bg-white"><?= e($student['roll_number'] ?? '-') ?></td>
                        <td class="py-3 px-4 font-medium sticky left-16 bg-white">
                            <input type="hidden" name="student_ids[]" value="<?= e($student['id']) ?>">
                            <?= e($student['name'] ?? '') ?>
                        </td>
                        <?php foreach ($subjects as $subject): ?>
                        <td class="py-3 px-4 text-center">
                            <?php
                            $val = $existingResults[$student['id']][$subject['id']]['obtained_marks'] ?? '';
                            ?>
                            <input type="number"
                                   name="marks[<?= e($student['id']) ?>][<?= e($subject['id']) ?>]"
                                   value="<?= e($val) ?>"
                                   min="0"
                                   max="<?= e($exam['total_marks']) ?>"
                                   step="0.5"
                                   class="w-20 border border-gray-300 rounded-lg px-2 py-1 text-center text-sm focus:ring-2 focus:ring-blue-500">
                        </td>
                        <?php endforeach; ?>
                        <td class="py-3 px-4">
                            <?php
                            $remark = '';
                            reset($existingResults[$student['id']] ?? []);
                            $firstKey = array_key_first($existingResults[$student['id']] ?? []);
                            if ($firstKey) {
                                $remark = $existingResults[$student['id']][$firstKey]['remarks'] ?? '';
                            }
                            ?>
                            <input type="text"
                                   name="result_remarks[<?= e($student['id']) ?>][<?= e(array_key_first($subjects) ?: 0) ?>]"
                                   value="<?= e($remark) ?>"
                                   placeholder="Optional"
                                   class="w-32 border border-gray-300 rounded-lg px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="p-6 border-t flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Save All Results</button>
        </div>
    </div>
</form>
<?php else: ?>
<div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
    No active students found for this exam's batch. Please check the exam configuration.
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
