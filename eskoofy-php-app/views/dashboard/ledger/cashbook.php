<?php $pageTitle = 'Cash Book'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Cash Book</h1>
    <a href="/dashboard/ledger/journal" class="text-gray-600 hover:text-gray-800">&larr; Back to Journal</a>
</div>

<div class="flex space-x-2 mb-6">
    <a href="/dashboard/ledger/journal" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Journal</a>
    <a href="/dashboard/ledger/cashbook" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-lg">Cash Book</a>
    <a href="/dashboard/ledger/bankbook" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Bank Book</a>
    <a href="/dashboard/ledger/income-statement" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Income Statement</a>
    <a href="/dashboard/ledger/balance-sheet" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Balance Sheet</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/ledger/cashbook" method="GET" class="flex flex-col sm:flex-row gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
            <input type="date" name="from" value="<?= e($from ?? '') ?>" class="border border-gray-300 rounded-lg px-4 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
            <input type="date" name="to" value="<?= e($to ?? '') ?>" class="border border-gray-300 rounded-lg px-4 py-2">
        </div>
        <div class="flex items-end">
            <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
        </div>
    </form>
</div>

<?php
$totalDebit = 0;
$totalCredit = 0;
foreach ($rows as $row) {
    $totalDebit += (float) ($row['debit'] ?? 0);
    $totalCredit += (float) ($row['credit'] ?? 0);
}
?>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Description</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Debit</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Credit</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php $running = 0; ?>
                <?php foreach ($rows as $row): ?>
                <?php
                $running += (float) ($row['debit'] ?? 0) - (float) ($row['credit'] ?? 0);
                ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4 text-sm"><?= e($row['entry_date'] ?? '') ?></td>
                    <td class="py-3 px-4"><?= e($row['description'] ?? $row['account_name'] ?? '') ?></td>
                    <td class="py-3 px-4 text-center text-green-600"><?= (float) ($row['debit'] ?? 0) > 0 ? e(number_format((float) $row['debit'], 2)) : '-' ?></td>
                    <td class="py-3 px-4 text-center text-red-600"><?= (float) ($row['credit'] ?? 0) > 0 ? e(number_format((float) $row['credit'], 2)) : '-' ?></td>
                    <td class="py-3 px-4 text-center font-medium <?= $running >= 0 ? 'text-green-600' : 'text-red-600' ?>"><?= e(number_format($running, 2)) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                <tr><td colspan="5" class="py-8 text-center text-gray-500">No cash transactions found.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 font-bold">
                    <td class="py-3 px-4" colspan="2">Total</td>
                    <td class="py-3 px-4 text-center text-green-600"><?= e(number_format($totalDebit, 2)) ?></td>
                    <td class="py-3 px-4 text-center text-red-600"><?= e(number_format($totalCredit, 2)) ?></td>
                    <td class="py-3 px-4 text-center <?= $running >= 0 ? 'text-green-600' : 'text-red-600' ?>"><?= e(number_format($running, 2)) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
