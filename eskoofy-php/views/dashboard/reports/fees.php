<?php $pageTitle = 'Fee Report'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Fee Collection Report</h1>
    <div class="flex gap-2">
        <a href="/dashboard/reports" class="text-gray-600 hover:text-gray-800">← Back</a>
        <button onclick="window.print()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">🖨️ Print</button>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/reports/fees" method="GET" class="flex flex-col sm:flex-row gap-4">
        <select name="class_id" class="border border-gray-300 rounded-lg px-4 py-2">
            <option value="">All Classes</option>
            <?php if (!empty($classes)): ?>
                <?php foreach ($classes as $class): ?>
                <option value="<?= e($class->id) ?>" <?= ($class_id ?? '') == $class->id ? 'selected' : '' ?>><?= e($class->name) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <input type="month" name="month" value="<?= e($month ?? date('Y-m')) ?>" class="border border-gray-300 rounded-lg px-4 py-2">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Generate</button>
    </form>
</div>

<div class="grid md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-blue-600"><?= e(config('currency.symbol', '$')) ?><?= number_format($report['total_expected'] ?? 0, 2) ?></div>
        <div class="text-sm text-gray-500">Total Expected</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-green-600"><?= e(config('currency.symbol', '$')) ?><?= number_format($report['total_collected'] ?? 0, 2) ?></div>
        <div class="text-sm text-gray-500">Collected</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-red-600"><?= e(config('currency.symbol', '$')) ?><?= number_format($report['total_pending'] ?? 0, 2) ?></div>
        <div class="text-sm text-gray-500">Pending</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-purple-600"><?= e($report['collection_rate'] ?? 0) ?>%</div>
        <div class="text-sm text-gray-500">Collection Rate</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Student</th>
                    <th class="py-3 px-4 font-semibold border-b">Class</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Expected</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Paid</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Due</th>
                    <th class="py-3 px-4 font-semibold border-b">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)): ?>
                    <?php foreach ($records as $record): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($record['student_name'] ?? '') ?></td>
                        <td class="py-3 px-4"><?= e($record['class'] ?? '') ?></td>
                        <td class="py-3 px-4 text-right"><?= e(config('currency.symbol', '$')) ?><?= number_format($record['expected'] ?? 0, 2) ?></td>
                        <td class="py-3 px-4 text-right text-green-600 font-bold"><?= e(config('currency.symbol', '$')) ?><?= number_format($record['paid'] ?? 0, 2) ?></td>
                        <td class="py-3 px-4 text-right text-red-600 font-bold"><?= e(config('currency.symbol', '$')) ?><?= number_format($record['due'] ?? 0, 2) ?></td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded-full <?= ($record['due'] ?? 0) == 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= ($record['due'] ?? 0) == 0 ? 'Paid' : 'Pending' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="py-8 text-center text-gray-500">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>