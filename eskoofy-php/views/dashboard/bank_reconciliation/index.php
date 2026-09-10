<?php $pageTitle = 'Bank Reconciliation'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Bank Reconciliation</h1>
</div>

<div class="grid md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white p-6 rounded-xl shadow-sm">
        <p class="text-sm text-gray-500">Statement Balance</p>
        <p class="text-2xl font-bold"><?= e(format_currency((float)$statementBalance)) ?></p>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm">
        <p class="text-sm text-gray-500">Book Balance</p>
        <p class="text-2xl font-bold"><?= e(format_currency((float)$bookBalance)) ?></p>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm">
        <p class="text-sm text-gray-500">Difference</p>
        <p class="text-2xl font-bold <?= abs($difference) > 0.01 ? 'text-red-600' : 'text-green-600' ?>"><?= e(format_currency((float)$difference)) ?></p>
    </div>
</div>

<form action="/dashboard/bank-reconciliation/reconcile" method="POST" class="mb-6">
    <?= csrf_field() ?>
    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Mark as Reconciled</button>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead><tr class="bg-gray-50"><th class="py-3 px-4 font-semibold border-b">Date</th><th class="py-3 px-4 font-semibold border-b">Account</th><th class="py-3 px-4 font-semibold border-b text-right">Debit</th><th class="py-3 px-4 font-semibold border-b text-right">Credit</th></tr></thead>
            <tbody>
                <?php if (!empty($bankEntries)): ?>
                    <?php foreach ($bankEntries as $r): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4"><?= e(date('M d, Y', strtotime($r['entry_date'] ?? 'now'))) ?></td>
                        <td class="py-3 px-4"><?= e($r['account_name']) ?></td>
                        <td class="py-3 px-4 text-right"><?= e(format_currency((float)$r['debit'])) ?></td>
                        <td class="py-3 px-4 text-right"><?= e(format_currency((float)$r['credit'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="py-8 text-center text-gray-500">No bank entries yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
