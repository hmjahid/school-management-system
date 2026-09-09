<?php $pageTitle = 'Balance Sheet'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Balance Sheet</h1>
    <a href="/dashboard/ledger/journal" class="text-gray-600 hover:text-gray-800">&larr; Back to Journal</a>
</div>

<div class="flex space-x-2 mb-6">
    <a href="/dashboard/ledger/journal" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Journal</a>
    <a href="/dashboard/ledger/cashbook" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Cash Book</a>
    <a href="/dashboard/ledger/bankbook" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Bank Book</a>
    <a href="/dashboard/ledger/income-statement" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Income Statement</a>
    <a href="/dashboard/ledger/balance-sheet" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-lg">Balance Sheet</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/ledger/balance-sheet" method="GET" class="flex flex-col sm:flex-row gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">As of Date</label>
            <input type="date" name="as_of" value="<?= e($asOf ?? date('Y-m-d')) ?>" class="border border-gray-300 rounded-lg px-4 py-2">
        </div>
        <div class="flex items-end">
            <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
        </div>
    </form>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Assets</h2>
        <div class="text-3xl font-bold text-blue-600 text-center py-8"><?= e(number_format($assets ?? 0, 2)) ?></div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Liabilities + Equity</h2>
        <div class="space-y-4">
            <div class="flex justify-between py-3 border-b">
                <span class="text-gray-600">Liabilities</span>
                <span class="font-bold"><?= e(number_format($liabilities ?? 0, 2)) ?></span>
            </div>
            <div class="flex justify-between py-3 border-b">
                <span class="text-gray-600">Equity</span>
                <span class="font-bold"><?= e(number_format($equity ?? 0, 2)) ?></span>
            </div>
            <div class="flex justify-between py-3 border-b-2 border-gray-300">
                <span class="text-lg font-bold">Total</span>
                <span class="text-lg font-bold"><?= e(number_format(($liabilities ?? 0) + ($equity ?? 0), 2)) ?></span>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
