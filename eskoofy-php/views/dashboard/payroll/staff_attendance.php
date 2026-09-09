<?php $pageTitle = 'Staff Attendance'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Staff Attendance</h1>
    <button onclick="document.getElementById('mark-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Mark Attendance</button>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/payroll/staff-attendance" method="GET" class="flex flex-col sm:flex-row gap-4">
        <input type="date" name="date" value="<?= e($date ?? date('Y-m-d')) ?>" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Staff</th>
                    <th class="py-3 px-4 font-semibold border-b">Role</th>
                    <th class="py-3 px-4 font-semibold border-b">Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Status</th>
                    <th class="py-3 px-4 font-semibold border-b">Check In</th>
                    <th class="py-3 px-4 font-semibold border-b">Check Out</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)): ?>
                    <?php foreach ($records as $record): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($record['staff']['name'] ?? '') ?></td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($record['staff']['role'] ?? '') ?></td>
                        <td class="py-3 px-4 text-sm"><?= e(date('M d, Y', strtotime($record['date']))) ?></td>
                        <td class="py-3 px-4">
                            <?php
                            $colors = ['present' => 'bg-green-100 text-green-700', 'absent' => 'bg-red-100 text-red-700', 'late' => 'bg-yellow-100 text-yellow-700', 'leave' => 'bg-blue-100 text-blue-700'];
                            ?>
                            <span class="px-2 py-1 text-xs rounded-full <?= $colors[$record['status'] ?? 'present'] ?>"><?= e(ucfirst($record['status'] ?? '')) ?></span>
                        </td>
                        <td class="py-3 px-4 text-sm"><?= e($record['check_in'] ?? '-') ?></td>
                        <td class="py-3 px-4 text-sm"><?= e($record['check_out'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="py-8 text-center text-gray-500">No records found for this date.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="mark-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-lg">
        <h2 class="text-xl font-bold mb-4">Mark Staff Attendance</h2>
        <form action="/dashboard/payroll/staff-attendance" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                <input type="date" name="date" value="<?= date('Y-m-d') ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="max-h-64 overflow-y-auto border rounded-lg">
                <?php if (!empty($allStaff)): ?>
                    <?php foreach ($allStaff as $staff): ?>
                    <div class="flex items-center justify-between p-3 border-b">
                        <span class="text-sm font-medium"><?= e($staff['name']) ?></span>
                        <div class="flex gap-3">
                            <label class="flex items-center text-xs"><input type="radio" name="attendance[<?= e($staff['id']) ?>]" value="present" checked class="mr-1"> Present</label>
                            <label class="flex items-center text-xs"><input type="radio" name="attendance[<?= e($staff['id']) ?>]" value="absent" class="mr-1"> Absent</label>
                            <label class="flex items-center text-xs"><input type="radio" name="attendance[<?= e($staff['id']) ?>]" value="late" class="mr-1"> Late</label>
                            <label class="flex items-center text-xs"><input type="radio" name="attendance[<?= e($staff['id']) ?>]" value="leave" class="mr-1"> Leave</label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('mark-modal').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>