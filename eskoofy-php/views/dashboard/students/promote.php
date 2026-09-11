<?php $pageTitle = 'Promote Students'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Promote Students</h1>
    <a href="/dashboard/students" class="text-gray-600 hover:text-gray-800">&larr; Back to Students</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <h2 class="text-lg font-bold mb-4">1. Select source students</h2>
    <form action="/dashboard/students/promote" method="GET" class="flex flex-col sm:flex-row gap-4">
        <select name="from_class_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
            <option value="">Select class</option>
            <?php foreach ($classes as $class): ?>
            <option value="<?= e($class['id']) ?>" <?= ($fromClassId ?? 0) == $class['id'] ? 'selected' : '' ?>><?= e($class['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="from_section_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">All sections</option>
            <?php foreach ($sections as $section): ?>
            <option value="<?= e($section['id']) ?>" <?= ($fromSectionId ?? 0) == $section['id'] ? 'selected' : '' ?>><?= e($section['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Load students</button>
    </form>
</div>

<?php if (!empty($students)): ?>
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-lg font-bold">2. Choose target & promote</h2>
        <p class="text-gray-500 text-sm mt-1"><?= count($students) ?> student(s) loaded from the selected class.</p>
    </div>
    <form action="/dashboard/students/promote" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="from_class_id" value="<?= e($fromClassId) ?>">
        <input type="hidden" name="from_section_id" value="<?= e($fromSectionId) ?>">
        <div class="p-6 grid md:grid-cols-3 gap-4">
            <select name="to_class_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                <option value="">Promote to class</option>
                <?php foreach ($classes as $class): ?>
                <option value="<?= e($class['id']) ?>"><?= e($class['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="to_section_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                <option value="">Section (optional)</option>
                <?php foreach ($sections as $section): ?>
                <option value="<?= e($section['id']) ?>"><?= e($section['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="to_batch_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" required>
                <option value="">Batch</option>
                <?php foreach ($batches as $batch): ?>
                <option value="<?= e($batch['id']) ?>"><?= e($batch['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="px-6 pb-2 flex gap-6 text-sm">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="keep_roll_number" value="1" class="rounded"> Keep roll numbers
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="promote_all" value="1" class="rounded"> Or promote ALL students in the source class
            </label>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="py-3 px-4 font-semibold border-b">Select</th>
                        <th class="py-3 px-4 font-semibold border-b">Roll</th>
                        <th class="py-3 px-4 font-semibold border-b">Student</th>
                        <th class="py-3 px-4 font-semibold border-b">Admission No.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4"><input type="checkbox" name="student_ids[]" value="<?= e($student['id']) ?>" class="rounded"></td>
                        <td class="py-3 px-4"><?= e($student['roll_number'] ?? '-') ?></td>
                        <td class="py-3 px-4 font-medium"><?= e($student['name'] ?? '') ?></td>
                        <td class="py-3 px-4"><?= e($student['admission_number'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="p-6 border-t flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Promote</button>
        </div>
    </form>
</div>
<?php elseif (($fromClassId ?? 0) > 0): ?>
<div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
    No active students found in this class. Please adjust the filter.
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>