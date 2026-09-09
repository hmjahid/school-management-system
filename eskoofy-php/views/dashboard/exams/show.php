<?php $pageTitle = 'Exam Detail'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800"><?= e($exam->name) ?></h1>
        <p class="text-gray-500"><?= e(ucfirst($exam->type ?? '')) ?> | <?= e($exam->start_date->format('M d')) ?> - <?= e($exam->end_date->format('M d, Y')) ?></p>
    </div>
    <div class="flex space-x-2">
        <a href="/dashboard/exams/<?= e($exam->id) ?>/edit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Edit</a>
        <a href="/dashboard/exams" class="text-gray-600 hover:text-gray-800">← Back</a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-lg font-bold">Results</h2>
    </div>
    <?php if (!empty($results)): ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Roll</th>
                    <th class="py-3 px-4 font-semibold border-b">Student</th>
                    <?php foreach ($exam->subjects as $subject): ?>
                    <th class="py-3 px-4 font-semibold border-b text-center"><?= e($subject->name) ?></th>
                    <?php endforeach; ?>
                    <th class="py-3 px-4 font-semibold border-b text-center">Total</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">GPA</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $studentResults): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4"><?= e($studentResults['roll'] ?? '') ?></td>
                    <td class="py-3 px-4 font-medium"><?= e($studentResults['name'] ?? '') ?></td>
                    <?php foreach ($exam->subjects as $subject): ?>
                    <td class="py-3 px-4 text-center"><?= e($studentResults['marks'][$subject->id] ?? '-') ?></td>
                    <?php endforeach; ?>
                    <td class="py-3 px-4 text-center font-bold"><?= e($studentResults['total'] ?? '') ?></td>
                    <td class="py-3 px-4 text-center font-bold"><?= e($studentResults['gpa'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="p-8 text-center text-gray-500">
        No results entered yet.
        <a href="/dashboard/exams/<?= e($exam->id) ?>/results/create" class="block mt-2 text-blue-600 hover:underline">Enter Results</a>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>