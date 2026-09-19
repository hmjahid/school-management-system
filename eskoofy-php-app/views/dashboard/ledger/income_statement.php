<?php $pageTitle = 'Income Statement'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Income Statement</h1>
    <a href="/dashboard/ledger/journal" class="text-gray-600 hover:text-gray-800">&larr; Back to Journal</a>
</div>

<div class="flex space-x-2 mb-6">
    <a href="/dashboard/ledger/journal" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Journal</a>
    <a href="/dashboard/ledger/cashbook" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Cash Book</a>
    <a href="/dashboard/ledger/bankbook" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Bank Book</a>
    <a href="/dashboard/ledger/income-statement" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-lg">Income Statement</a>
    <a href="/dashboard/ledger/balance-sheet" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Balance Sheet</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/ledger/income-statement" method="GET" class="flex flex-col sm:flex-row gap-4">
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

<div class="bg-white rounded-xl shadow-sm p-6 max-w-lg">
    <h2 class="text-lg font-bold mb-4">Period: <?= e($from ?? '') ?> to <?= e($to ?? '') ?></h2>
    <div class="space-y-4">
        <div class="flex justify-between py-3 border-b">
            <span class="text-gray-600">Total Income</span>
            <span class="font-bold text-green-600"><?= e(number_format($income ?? 0, 2)) ?></span>
        </div>
        <div class="flex justify-between py-3 border-b">
            <span class="text-gray-600">Total Expenses</span>
            <span class="font-bold text-red-600"><?= e(number_format($expenses ?? 0, 2)) ?></span>
        </div>
        <div class="flex justify-between py-3 border-b-2 border-gray-300">
            <span class="text-lg font-bold">Net Income</span>
            <span class="text-lg font-bold <?= ($net ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' ?>"><?= e(number_format($net ?? 0, 2)) ?></span>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
