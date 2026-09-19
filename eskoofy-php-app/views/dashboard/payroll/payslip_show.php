<?php $pageTitle = 'Payslip'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Payslip — <?= e($payslip['employee_name'] ?? '') ?></h1>
        <p class="text-gray-500"><?= e(date('F', mktime(0, 0, 0, (int) $payslip['month'], 1))) ?> <?= e((string) $payslip['year']) ?></p>
    </div>
    <div class="flex gap-2">
        <?php if (($payslip['status'] ?? '') === 'draft'): ?>
        <form action="/dashboard/payslips/<?= e($payslip['id']) ?>/paid" method="POST">
            <?= csrf_field() ?>
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">Mark paid</button>
        </form>
        <?php endif; ?>
        <a href="/dashboard/payslips" class="text-gray-600 hover:text-gray-800 px-4 py-2">&larr; Back</a>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Earnings</h2>
        <div class="space-y-2">
            <div class="flex justify-between border-b pb-2"><span>Basic</span><span class="font-medium">$<?= number_format((float) $payslip['basic'], 2) ?></span></div>
            <?php $allowances = $payslip['details']['allowances'] ?? []; ?>
            <?php foreach ($allowances as $name => $amount): ?>
            <div class="flex justify-between border-b pb-2"><span><?= e(ucwords(str_replace('_', ' ', (string) $name))) ?></span><span>$<?= number_format((float) $amount, 2) ?></span></div>
            <?php endforeach; ?>
            <div class="flex justify-between font-semibold pt-2"><span>Total earnings</span><span>$<?= number_format((float) $payslip['basic'] + (float) $payslip['total_allowances'], 2) ?></span></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Deductions</h2>
        <div class="space-y-2">
            <?php $deductions = $payslip['details']['deductions'] ?? []; ?>
            <?php foreach ($deductions as $name => $amount): ?>
            <div class="flex justify-between border-b pb-2"><span><?= e(ucwords(str_replace('_', ' ', (string) $name))) ?></span><span>$<?= number_format((float) $amount, 2) ?></span></div>
            <?php endforeach; ?>
            <?php if (($payslip['details']['leave_days'] ?? 0) > 0): ?>
            <div class="flex justify-between border-b pb-2">
                <span>Leave deduction (<?= (int) ($payslip['details']['leave_days'] ?? 0) ?> days)</span>
                <span>$<?= number_format((float) ($payslip['details']['leave_deduction'] ?? 0), 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="flex justify-between font-semibold pt-2"><span>Total deductions</span><span>$<?= number_format((float) $payslip['total_deductions'], 2) ?></span></div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mt-6 flex justify-between items-center">
    <div>
        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Net salary</div>
        <div class="text-3xl font-bold text-emerald-600 mt-1">$<?= number_format((float) $payslip['net_salary'], 2) ?></div>
    </div>
    <div>
        <?php if (($payslip['status'] ?? '') === 'paid'): ?>
        <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-700 font-semibold">Paid on <?= e(date('Y-m-d H:i', strtotime($payslip['paid_at'] ?? 'now'))) ?></span>
        <?php else: ?>
        <span class="px-3 py-1 text-sm rounded-full bg-amber-100 text-amber-700 font-semibold">Draft</span>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>