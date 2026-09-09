<?php $pageTitle = 'Student Attendance'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Attendance: <?= e($student['name'] ?? '') ?></h1>
        <p class="text-gray-500">Admission: <?= e($student['admission_number'] ?? '') ?> | Class: <?= e($student['class_name'] ?? '') ?></p>
    </div>
    <div class="flex space-x-2">
        <a href="/dashboard/students/<?= e($student['id']) ?>" class="text-gray-600 hover:text-gray-800">&larr; Back to Student</a>
    </div>
</div>

<div class="grid grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-blue-600"><?= e($totalDays) ?></div>
        <div class="text-gray-500">Total Days</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-green-600"><?= e($presentDays) ?></div>
        <div class="text-gray-500">Present</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold <?= $rate >= 75 ? 'text-green-600' : 'text-red-600' ?>"><?= e($rate) ?>%</div>
        <div class="text-gray-500">Attendance Rate</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/students/<?= e($student['id']) ?>/attendance" method="GET" class="flex flex-col sm:flex-row gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
            <input type="date" name="from" value="<?= e($from) ?>" class="border border-gray-300 rounded-lg px-4 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
            <input type="date" name="to" value="<?= e($to) ?>" class="border border-gray-300 rounded-lg px-4 py-2">
        </div>
        <div class="flex items-end">
            <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Status</th>
                    <th class="py-3 px-4 font-semibold border-b">Check In</th>
                    <th class="py-3 px-4 font-semibold border-b">Check Out</th>
                    <th class="py-3 px-4 font-semibold border-b">Note</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)): ?>
                    <?php foreach ($records as $record): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($record['date']) ?></td>
                        <td class="py-3 px-4">
                            <?php
                            $colors = [
                                'present' => 'bg-green-100 text-green-700',
                                'absent' => 'bg-red-100 text-red-700',
                                'late' => 'bg-yellow-100 text-yellow-700',
                                'half_day' => 'bg-orange-100 text-orange-700',
                                'excused' => 'bg-blue-100 text-blue-700',
                            ];
                            $color = $colors[$record['status']] ?? 'bg-gray-100 text-gray-700';
                            ?>
                            <span class="px-2 py-1 text-xs rounded-full <?= $color ?>"><?= e(ucfirst(str_replace('_', ' ', $record['status']))) ?></span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($record['check_in'] ?? '-') ?></td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($record['check_out'] ?? '-') ?></td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($record['note'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="py-8 text-center text-gray-500">No attendance records found for this period.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
