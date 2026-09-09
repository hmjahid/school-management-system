<?php $pageTitle = 'Generate ID Card'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Generate Student ID Card</h1>
    <a href="/dashboard/id-cards" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">ID Card Details</h2>
        <form action="/dashboard/id-cards/generate" method="POST" class="space-y-4">
            <?= csrf_field() ?>
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
            <div class="w-64 mx-auto bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="bg-blue-600 text-white p-3 text-center">
                    <p class="font-bold text-sm"><?= e($settings['school_name'] ?? config('school.name', 'School')) ?></p>
                    <p class="text-xs opacity-80">Student ID Card</p>
                </div>
                <div class="p-4 text-center">
                    <?php if (!empty($student['photo'])): ?>
                    <img src="/uploads/avatars/<?= e($student['photo']) ?>" alt="Photo" class="w-16 h-16 rounded-full mx-auto mb-2 object-cover">
                    <?php else: ?>
                    <div class="w-16 h-16 bg-gray-200 rounded-full mx-auto mb-2 flex items-center justify-center text-gray-400 text-xl">
                        <?= strtoupper(substr($student['name'] ?? 'S', 0, 1)) ?>
                    </div>
                    <?php endif; ?>
                    <p class="font-bold text-sm"><?= e($student['name'] ?? '') ?></p>
                    <p class="text-xs text-gray-500">Class: <?= e($student['class_name'] ?? '') ?> | Section: <?= e($student['section_name'] ?? '') ?></p>
                    <p class="text-xs text-gray-500">Roll: <?= e($student['roll_number'] ?? '-') ?></p>
                    <p class="text-xs text-gray-500">ID: <?= e($student['admission_number'] ?? '') ?></p>
                </div>
                <div class="bg-gray-50 p-2 text-center text-xs text-gray-400">
                    <?= e($settings['phone'] ?? '') ?>
                </div>
            </div>
        </div>
        <div class="mt-4 flex justify-center">
            <button onclick="window.print()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Print ID Card</button>
        </div>
        <?php else: ?>
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
            <div class="w-48 mx-auto bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="bg-blue-600 text-white p-3 text-center">
                    <p class="font-bold text-sm"><?= e(config('school.name', 'School')) ?></p>
                </div>
                <div class="p-4 text-center">
                    <div class="w-16 h-16 bg-gray-200 rounded-full mx-auto mb-2"></div>
                    <p class="font-bold text-sm">Student Name</p>
                    <p class="text-xs text-gray-500">Class: X | Section: A</p>
                    <p class="text-xs text-gray-500">Roll: 001</p>
                    <p class="text-xs text-gray-500">ID: STU-001</p>
                </div>
                <div class="bg-gray-50 p-2 text-center text-xs text-gray-400">
                    <?= e(config('school.phone', '')) ?>
                </div>
            </div>
            <p class="text-gray-500 text-sm mt-4">Select a student to generate ID card</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
