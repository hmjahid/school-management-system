<?php $pageTitle = 'Staff Attendance'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Staff Attendance</h1>
    <a href="/dashboard/staff-attendance/report" class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Report</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/staff-attendance" method="GET" class="flex gap-2">
        <input type="date" name="date" value="<?= e($date) ?>" class="border border-gray-300 rounded-lg px-4 py-2">
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Load</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <form action="/dashboard/staff-attendance" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="date" value="<?= e($date) ?>">
        <table class="w-full text-left">
            <thead><tr class="bg-gray-50">
                <th class="py-3 px-4 font-semibold border-b">Staff</th>
                <th class="py-3 px-4 font-semibold border-b">Status</th>
                <th class="py-3 px-4 font-semibold border-b">Check In</th>
                <th class="py-3 px-4 font-semibold border-b">Check Out</th>
                <th class="py-3 px-4 font-semibold border-b">Notes</th>
            </tr></thead>
            <tbody>
                <?php if (!empty($teachers)): ?>
                    <?php foreach ($teachers as $t):
                        $existing = null;
                        foreach ($records as $r) {
                            if ((int)$r['teacher_id'] === (int)$t['id']) {
                                $existing = $r;
                                break;
                            }
                        }
                    ?>
                    <tr class="border-b">
                        <td class="py-3 px-4">
                            <div class="font-medium"><?= e($t['name']) ?></div>
                            <div class="text-xs text-gray-500"><?= e($t['employee_id']) ?></div>
                        </td>
                        <td class="py-3 px-4">
                            <select name="status[<?= e($t['id']) ?>]" class="border border-gray-300 rounded px-2 py-1 text-sm">
                                <?php foreach (['present','absent','leave','half_day','late'] as $s): ?>
                                <option value="<?= e($s) ?>" <?= ($existing['status'] ?? '') === $s ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $s))) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="py-3 px-4"><input type="time" name="check_in[<?= e($t['id']) ?>]" value="<?= e($existing['check_in'] ?? '') ?>" class="border border-gray-300 rounded px-2 py-1 text-sm"></td>
                        <td class="py-3 px-4"><input type="time" name="check_out[<?= e($t['id']) ?>]" value="<?= e($existing['check_out'] ?? '') ?>" class="border border-gray-300 rounded px-2 py-1 text-sm"></td>
                        <td class="py-3 px-4"><input type="text" name="notes[<?= e($t['id']) ?>]" value="<?= e($existing['notes'] ?? '') ?>" class="border border-gray-300 rounded px-2 py-1 text-sm w-full"></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="py-8 text-center text-gray-500">No active staff.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if (!empty($teachers)): ?>
        <div class="p-4 text-right">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Save Attendance</button>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/dashboard.php'; ?>
