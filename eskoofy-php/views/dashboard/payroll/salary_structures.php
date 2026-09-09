<?php $pageTitle = 'Salary Structures'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Salary Structures</h1>
    <button onclick="document.getElementById('create-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Add Structure</button>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Title</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Basic Salary</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Allowances</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Deductions</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Net Salary</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($structures)): ?>
                    <?php foreach ($structures as $structure): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($structure->title) ?></td>
                        <td class="py-3 px-4 text-right"><?= e(config('currency.symbol', '$')) ?><?= number_format($structure->basic_salary, 2) ?></td>
                        <td class="py-3 px-4 text-right text-green-600">+<?= e(config('currency.symbol', '$')) ?><?= number_format($structure->total_allowances ?? 0, 2) ?></td>
                        <td class="py-3 px-4 text-right text-red-600">-<?= e(config('currency.symbol', '$')) ?><?= number_format($structure->total_deductions ?? 0, 2) ?></td>
                        <td class="py-3 px-4 text-right font-bold"><?= e(config('currency.symbol', '$')) ?><?= number_format($structure->net_salary ?? 0, 2) ?></td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="/dashboard/payroll/salary-structures/<?= e($structure->id) ?>/edit" class="text-green-600 hover:underline text-sm">Edit</a>
                                <form action="/dashboard/payroll/salary-structures/<?= e($structure->id) ?>" method="POST" onsubmit="return confirm('Delete?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="py-8 text-center text-gray-500">No salary structures found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="create-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-lg">
        <h2 class="text-xl font-bold mb-4">Add Salary Structure</h2>
        <form action="/dashboard/payroll/salary-structures" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                <input type="text" name="title" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Basic Salary *</label>
                <input type="number" name="basic_salary" step="0.01" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">House Allowance</label>
                    <input type="number" name="house_allowance" step="0.01" value="0" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Transport Allowance</label>
                    <input type="number" name="transport_allowance" step="0.01" value="0" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Medical Allowance</label>
                    <input type="number" name="medical_allowance" step="0.01" value="0" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Other Allowances</label>
                    <input type="number" name="other_allowances" step="0.01" value="0" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Provident Fund</label>
                    <input type="number" name="provident_fund" step="0.01" value="0" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tax</label>
                    <input type="number" name="tax" step="0.01" value="0" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>