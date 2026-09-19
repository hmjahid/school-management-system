<?php $pageTitle = 'Generate Payslips'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Generate Payslips</h1>
    <a href="/dashboard/payslips" class="text-gray-600 hover:text-gray-800">&larr; Back to Payslips</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/payroll/generate" method="GET" class="flex flex-col sm:flex-row gap-4">
        <select name="month" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $month === $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
            <?php endfor; ?>
        </select>
        <input type="number" name="year" value="<?= e((string) $year) ?>" min="2020" max="2099" class="w-32 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Preview</button>
    </form>
</div>

<?php if (!empty($preview)): ?>
<form action="/dashboard/payroll/generate" method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="month" value="<?= e((string) $month) ?>">
    <input type="hidden" name="year" value="<?= e((string) $year) ?>">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b flex justify-between items-center">
            <h2 class="font-bold">Payroll preview — <?= date('F', mktime(0, 0, 0, $month, 1)) ?> <?= e((string) $year) ?></h2>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Generate payslips</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="py-3 px-4 font-semibold border-b">Select</th>
                        <th class="py-3 px-4 font-semibold border-b">Teacher</th>
                        <th class="py-3 px-4 font-semibold border-b text-right">Basic</th>
                        <th class="py-3 px-4 font-semibold border-b text-right">Allowances</th>
                        <th class="py-3 px-4 font-semibold border-b text-center">Leave days</th>
                        <th class="py-3 px-4 font-semibold border-b text-right">Deductions</th>
                        <th class="py-3 px-4 font-semibold border-b text-right">Net</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preview as $p): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4"><input type="checkbox" name="teacher_ids[]" value="<?= e($p['teacher_id']) ?>" checked class="rounded"></td>
                        <td class="py-3 px-4 font-medium"><?= e($p['employee_name'] ?? '') ?></td>
                        <td class="py-3 px-4 text-right">$<?= number_format($p['basic'], 2) ?></td>
                        <td class="py-3 px-4 text-right">$<?= number_format($p['allowances'], 2) ?></td>
                        <td class="py-3 px-4 text-center"><?= (int) $p['leave_days'] ?></td>
                        <td class="py-3 px-4 text-right">$<?= number_format($p['deductions'] + $p['leave_deduction'], 2) ?></td>
                        <td class="py-3 px-4 text-right font-semibold">$<?= number_format($p['net'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Generate payslips</button>
        </div>
    </div>
</form>
<?php else: ?>
<div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
    No active salary structures found for preview. Add salary structures first.
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>