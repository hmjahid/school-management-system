<?php $pageTitle = 'Payslips'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Payslips</h1>
    <button onclick="document.getElementById('generate-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Generate Payslips</button>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/payroll/payslips" method="GET" class="flex flex-col sm:flex-row gap-4">
        <select name="month" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= ($month ?? date('m')) == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m)) ?></option>
            <?php endfor; ?>
        </select>
        <select name="year" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
            <option value="<?= $y ?>" <?= ($year ?? date('Y')) == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Staff</th>
                    <th class="py-3 px-4 font-semibold border-b">Month</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Basic</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Allowances</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Deductions</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Net</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($payslips)): ?>
                    <?php foreach ($payslips as $payslip): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($payslip->staff->name ?? '') ?></td>
                        <td class="py-3 px-4"><?= e(date('F Y', strtotime($payslip->month . '-01'))) ?></td>
                        <td class="py-3 px-4 text-right"><?= e(config('currency.symbol', '$')) ?><?= number_format($payslip->basic_salary, 2) ?></td>
                        <td class="py-3 px-4 text-right text-green-600">+<?= e(config('currency.symbol', '$')) ?><?= number_format($payslip->total_allowances, 2) ?></td>
                        <td class="py-3 px-4 text-right text-red-600">-<?= e(config('currency.symbol', '$')) ?><?= number_format($payslip->total_deductions, 2) ?></td>
                        <td class="py-3 px-4 text-right font-bold"><?= e(config('currency.symbol', '$')) ?><?= number_format($payslip->net_salary, 2) ?></td>
                        <td class="py-3 px-4">
                            <a href="/dashboard/payroll/payslips/<?= e($payslip->id) ?>/print" target="_blank" class="text-blue-600 hover:underline text-sm">Print</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="py-8 text-center text-gray-500">No payslips found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="generate-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Generate Payslips</h2>
        <form action="/dashboard/payroll/payslips/generate" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Month *</label>
                <select name="month" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>"><?= date('F', mktime(0, 0, 0, $m)) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Year *</label>
                <select name="year" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <?php for ($y = date('Y') - 1; $y <= date('Y'); $y++): ?>
                    <option value="<?= $y ?>"><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="all_staff" value="1" class="rounded border-gray-300 text-blue-600 mr-2" checked>
                    <span class="text-sm text-gray-700">Generate for all active staff</span>
                </label>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('generate-modal').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Generate</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>