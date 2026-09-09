<?php $pageTitle = 'Mark Attendance'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Mark Attendance</h1>
    <a href="/dashboard/attendance" class="text-gray-600 hover:text-gray-800">← Back to Records</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/attendance/mark" method="GET" class="flex flex-col sm:flex-row gap-4">
        <select name="class_id" id="class-select" required class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">Select Class</option>
            <?php if (!empty($classes)): ?>
                <?php foreach ($classes as $class): ?>
                <option value="<?= e($class->id) ?>" <?= ($class_id ?? '') == $class->id ? 'selected' : '' ?>><?= e($class->name) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <select name="section_id" id="section-select" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">All Sections</option>
        </select>
        <input type="date" name="date" value="<?= e($date ?? date('Y-m-d')) ?>" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Load Students</button>
    </form>
</div>

<?php if (!empty($students)): ?>
<form action="/dashboard/attendance" method="POST" class="space-y-6">
    <?= csrf_field() ?>
    <input type="hidden" name="class_id" value="<?= e($class_id) ?>">
    <input type="hidden" name="section_id" value="<?= e($section_id ?? '') ?>">
    <input type="hidden" name="date" value="<?= e($date) ?>">

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="font-bold"><?= count($students) ?> Students</h3>
            <div class="flex gap-2">
                <button type="button" onclick="setAll('present')" class="px-3 py-1 bg-green-100 text-green-700 rounded text-sm hover:bg-green-200">All Present</button>
                <button type="button" onclick="setAll('absent')" class="px-3 py-1 bg-red-100 text-red-700 rounded text-sm hover:bg-red-200">All Absent</button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="py-3 px-4 font-semibold border-b">Roll</th>
                        <th class="py-3 px-4 font-semibold border-b">Name</th>
                        <th class="py-3 px-4 font-semibold border-b text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4"><?= e($student->roll ?? '-') ?></td>
                        <td class="py-3 px-4 font-medium"><?= e($student->name) ?></td>
                        <td class="py-3 px-4">
                            <div class="flex justify-center gap-4">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="attendance[<?= e($student->id) ?>]" value="present" class="text-green-600 focus:ring-green-500" checked>
                                    <span class="ml-1 text-sm text-green-700">Present</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="attendance[<?= e($student->id) ?>]" value="absent" class="text-red-600 focus:ring-red-500">
                                    <span class="ml-1 text-sm text-red-700">Absent</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="attendance[<?= e($student->id) ?>]" value="late" class="text-yellow-600 focus:ring-yellow-500">
                                    <span class="ml-1 text-sm text-yellow-700">Late</span>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">Save Attendance</button>
    </div>
</form>
<?php endif; ?>

<script>
function setAll(status) {
    document.querySelectorAll('input[type="radio"][value="' + status + '"]').forEach(r => r.checked = true);
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>