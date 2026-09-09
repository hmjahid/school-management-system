<?php $pageTitle = 'Cash Flow Statement'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Cash Flow Statement</h1>
    <p class="text-gray-500"><?= e(date('M d, Y', strtotime($from))) ?> – <?= e(date('M d, Y', strtotime($to))) ?></p>
</div>

<div class="bg-white rounded-xl shadow-sm p-8 mb-6">
    <h2 class="text-lg font-bold mb-4 text-green-600">Operating Activities</h2>
    <div class="space-y-2 mb-4">
        <div class="flex justify-between"><span>Cash Inflows</span><span class="font-bold"><?= e(format_currency($operatingIn)) ?></span></div>
        <div class="flex justify-between"><span>Cash Outflows</span><span class="font-bold text-red-600">- <?= e(format_currency($operatingOut)) ?></span></div>
        <div class="flex justify-between border-t pt-2 font-bold"><span>Net Operating Cash Flow</span><span><?= e(format_currency($netOperating)) ?></span></div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-8 mb-6">
    <h2 class="text-lg font-bold mb-4 text-blue-600">Investing Activities</h2>
    <div class="space-y-2">
        <div class="flex justify-between"><span>Asset Purchases</span><span class="font-bold text-red-600">- <?= e(format_currency($investingIn)) ?></span></div>
        <div class="flex justify-between border-t pt-2 font-bold"><span>Net Investing Cash Flow</span><span><?= e(format_currency($netInvesting)) ?></span></div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-8 mb-6">
    <h2 class="text-lg font-bold mb-4 text-purple-600">Financing Activities</h2>
    <div class="space-y-2">
        <div class="flex justify-between"><span>Loans / Equity Inflows</span><span class="font-bold"><?= e(format_currency($financingIn)) ?></span></div>
        <div class="flex justify-between border-t pt-2 font-bold"><span>Net Financing Cash Flow</span><span><?= e(format_currency($netFinancing)) ?></span></div>
    </div>
</div>

<div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-xl shadow-sm p-8">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold">Net Cash Flow</h2>
        <span class="text-3xl font-bold"><?= e(format_currency($netCashFlow)) ?></span>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/dashboard.php'; ?>
