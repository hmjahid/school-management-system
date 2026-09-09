<?php $pageTitle = 'Staff Attendance Report'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <a href="/dashboard/staff-attendance" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    <h1 class="text-2xl font-bold text-gray-800">Staff Attendance Report</h1>
    <p class="text-gray-500"><?= e(date('M d, Y', strtotime($from))) ?> – <?= e(date('M d, Y', strtotime($to))) ?></p>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/staff-attendance/report" method="GET" class="flex gap-2">
        <input type="date" name="from" value="<?= e($from) ?>" class="border border-gray-300 rounded-lg px-4 py-2">
        <input type="date" name="to" value="<?= e($to) ?>" class="border border-gray-300 rounded-lg px-4 py-2">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-left">
        <thead><tr class="bg-gray-50">
            <th class="py-3 px-4 font-semibold border-b">Employee</th>
            <th class="py-3 px-4 font-semibold border-b">Name</th>
            <th class="py-3 px-4 font-semibold border-b text-right">Total</th>
            <th class="py-3 px-4 font-semibold border-b text-right">Present</th>
            <th class="py-3 px-4 font-semibold border-b text-right">Absent</th>
            <th class="py-3 px-4 font-semibold border-b text-right">Leave</th>
        </tr></thead>
        <tbody>
            <?php if (!empty($rows)): ?>
                <?php foreach ($rows as $r): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4 font-mono text-sm"><?= e($r['employee_id']) ?></td>
                    <td class="py-3 px-4"><?= e($r['name']) ?></td>
                    <td class="py-3 px-4 text-right"><?= e((int)($r['total_days'] ?? 0)) ?></td>
                    <td class="py-3 px-4 text-right text-green-600"><?= e((int)($r['present_days'] ?? 0)) ?></td>
                    <td class="py-3 px-4 text-right text-red-600"><?= e((int)($r['absent_days'] ?? 0)) ?></td>
                    <td class="py-3 px-4 text-right text-yellow-600"><?= e((int)($r['leave_days'] ?? 0)) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="py-8 text-center text-gray-500">No data.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/dashboard.php'; ?>
