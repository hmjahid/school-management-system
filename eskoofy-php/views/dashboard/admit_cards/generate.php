<?php $pageTitle = 'Generate Admit Card'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Generate Admit Card</h1>
    <a href="/dashboard/admit-cards" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Admit Card Details</h2>
        <form action="/dashboard/admit-cards/generate" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Exam *</label>
                <select name="exam_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Select Exam</option>
                    <?php if (!empty($exams)): ?>
                        <?php foreach ($exams as $exam): ?>
                        <option value="<?= e($exam['id']) ?>" <?= ($exam['id'] ?? 0) == ($selectedExamId ?? 0) ? 'selected' : '' ?>><?= e($exam['name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Student *</label>
                <select name="student_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Select Student</option>
                    <?php if (!empty($students)): ?>
                        <?php foreach ($students as $student): ?>
                        <option value="<?= e($student['id']) ?>" <?= ($student['id'] ?? 0) == ($selectedStudentId ?? 0) ? 'selected' : '' ?>><?= e($student['name'] ?? '') ?> (<?= e($student['admission_number'] ?? '') ?>)</option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Generate</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Preview</h2>
        <?php if (!empty($generated)): ?>
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
            <div class="bg-white shadow-lg rounded-lg overflow-hidden max-w-sm mx-auto">
                <div class="bg-blue-600 text-white p-3 text-center">
                    <p class="font-bold"><?= e($settings['school_name'] ?? config('school.name', 'School')) ?></p>
                    <p class="text-xs opacity-80">ADMIT CARD</p>
                </div>
                <div class="p-4">
                    <div class="text-center mb-3">
                        <p class="text-lg font-bold"><?= e($exam['name'] ?? '') ?></p>
                        <p class="text-xs text-gray-500"><?= e($exam['exam_date'] ?? '') ?></p>
                    </div>
                    <hr class="mb-3">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Student:</span>
                            <span class="font-medium"><?= e($student['name'] ?? '') ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Class:</span>
                            <span class="font-medium"><?= e($student['class_name'] ?? '') ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Admission No:</span>
                            <span class="font-medium"><?= e($student['admission_number'] ?? '') ?></span>
                        </div>
                    </div>
                    <hr class="my-3">
                    <p class="text-xs text-gray-500 text-center">Please bring this card to the examination hall. Valid photo ID required.</p>
                </div>
                <div class="bg-gray-50 p-2 text-center text-xs text-gray-400">
                    <?= e($settings['phone'] ?? '') ?>
                </div>
            </div>
        </div>
        <div class="mt-4 flex justify-center">
            <button onclick="window.print()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Print Admit Card</button>
        </div>
        <?php else: ?>
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center text-gray-500">
            Select an exam and student, then click Generate to preview.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
