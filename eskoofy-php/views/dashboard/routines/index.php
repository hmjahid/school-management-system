<?php $pageTitle = 'Routines'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Class Routines</h1>
    <a href="/dashboard/routines/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Add Routine</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/routines" method="GET" class="flex flex-col sm:flex-row gap-4">
        <select name="class_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">Select Class</option>
            <?php if (!empty($classes)): ?>
                <?php foreach ($classes as $class): ?>
                <option value="<?= e($class['id']) ?>" <?= ($classId ?? '') == $class['id'] ? 'selected' : '' ?>><?= e($class['name']) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <select name="section_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">Select Section</option>
        </select>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">View Routine</button>
    </form>
</div>

<?php if (!empty($routine)): ?>
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Time</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Saturday</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Sunday</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Monday</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Tuesday</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Wednesday</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Thursday</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($periods as $period): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4 font-medium text-sm whitespace-nowrap">
                        <?= e($period['start_time']) ?> - <?= e($period['end_time']) ?>
                    </td>
                    <?php foreach (['sat', 'sun', 'mon', 'tue', 'wed', 'thu'] as $day): ?>
                    <td class="py-3 px-4 text-center text-sm">
                        <?php if (isset($routine[$day][$period['id']])): ?>
                            <?php $slot = $routine[$day][$period['id']]; ?>
                            <div class="font-medium"><?= e($slot['subject_name'] ?? '') ?></div>
                            <div class="text-gray-500 text-xs"><?= e($slot['teacher_name'] ?? '') ?></div>
                        <?php else: ?>
                            <span class="text-gray-300">-</span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
    Select a class to view the routine.
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>