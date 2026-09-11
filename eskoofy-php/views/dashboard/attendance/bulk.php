<?php $pageTitle = 'Bulk Attendance'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Bulk Attendance</h1>
    <a href="/dashboard/attendance" class="text-gray-600 hover:text-gray-800">&larr; Back to Attendance</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/attendance/bulk" method="GET" class="flex flex-col sm:flex-row gap-4">
        <input type="date" name="date" value="<?= e($date ?? '') ?>" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        <select name="batch_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">Select batch</option>
            <?php foreach ($batches as $batch): ?>
            <option value="<?= e($batch['id']) ?>" <?= ($batchId ?? 0) == $batch['id'] ? 'selected' : '' ?>><?= e($batch['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="section_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">All sections</option>
            <?php foreach ($sections as $section): ?>
            <option value="<?= e($section['id']) ?>" <?= ($sectionId ?? 0) == $section['id'] ? 'selected' : '' ?>><?= e($section['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Load students</button>
    </form>
</div>

<?php if (!empty($students)): ?>
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-lg font-bold">Mark attendance for <?= count($students) ?> students</h2>
        <p class="text-gray-500 text-sm mt-1">Date: <?= e($date ?? '') ?> | Existing entries are pre-filled and will be updated.</p>
    </div>
    <form action="/dashboard/attendance/bulk" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="date" value="<?= e($date ?? '') ?>">
        <input type="hidden" name="batch_id" value="<?= e($batchId ?? '') ?>">
        <input type="hidden" name="section_id" value="<?= e($sectionId ?? '') ?>">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="py-3 px-4 font-semibold border-b">#</th>
                        <th class="py-3 px-4 font-semibold border-b">Student</th>
                        <th class="py-3 px-4 font-semibold border-b">Roll</th>
                        <th class="py-3 px-4 font-semibold border-b">Status</th>
                        <th class="py-3 px-4 font-semibold border-b">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($students as $student): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4"><?= $i++ ?></td>
                        <td class="py-3 px-4 font-medium"><?= e($student['name'] ?? '') ?></td>
                        <td class="py-3 px-4"><?= e($student['roll_number'] ?? '-') ?></td>
                        <td class="py-3 px-4">
                            <?php $existingStatus = $existing[$student['id']]['status'] ?? ''; ?>
                            <select name="status[<?= e($student['id']) ?>]" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                                <?php foreach (['present', 'absent', 'late', 'half_day', 'holiday', 'on_leave'] as $opt): ?>
                                <option value="<?= $opt ?>" <?= $existingStatus === $opt ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $opt)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="py-3 px-4">
                            <?php $existingRemarks = $existing[$student['id']]['remarks'] ?? ''; ?>
                            <input type="text" name="remarks[<?= e($student['id']) ?>]" value="<?= e($existingRemarks) ?>" maxlength="500" placeholder="Optional"
                                   class="w-40 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="p-6 border-t flex justify-end gap-2">
            <a href="/dashboard/attendance/bulk?date=<?= e(urlencode($date ?? '')) ?>&batch_id=<?= e($batchId ?? '') ?>&section_id=<?= e($sectionId ?? '') ?>" class="text-gray-600 hover:text-gray-800 px-4 py-2">Reset</a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Save attendance</button>
        </div>
    </form>
</div>
<?php elseif (($batchId ?? 0) > 0): ?>
<div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
    No active students found for this batch. Please adjust the filter.
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>