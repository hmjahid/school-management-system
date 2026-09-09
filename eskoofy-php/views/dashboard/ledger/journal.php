<?php $pageTitle = 'Journal'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Journal Entries</h1>
    <button onclick="document.getElementById('create-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Add Entry</button>
</div>

<div class="flex space-x-2 mb-6">
    <a href="/dashboard/ledger/journal" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-lg">Journal</a>
    <a href="/dashboard/ledger/cashbook" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Cash Book</a>
    <a href="/dashboard/ledger/bankbook" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Bank Book</a>
    <a href="/dashboard/ledger/income-statement" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Income Statement</a>
    <a href="/dashboard/ledger/balance-sheet" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Balance Sheet</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/ledger/journal" method="GET" class="flex flex-col sm:flex-row gap-4">
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

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Account</th>
                    <th class="py-3 px-4 font-semibold border-b">Description</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Debit</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Credit</th>
                    <th class="py-3 px-4 font-semibold border-b">Reference</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 text-sm"><?= e($row['entry_date'] ?? '') ?></td>
                        <td class="py-3 px-4 font-medium"><?= e($row['account_name'] ?? '') ?></td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($row['description'] ?? '-') ?></td>
                        <td class="py-3 px-4 text-center text-green-600 font-medium"><?= (float) ($row['debit'] ?? 0) > 0 ? e(number_format((float) $row['debit'], 2)) : '-' ?></td>
                        <td class="py-3 px-4 text-center text-red-600 font-medium"><?= (float) ($row['credit'] ?? 0) > 0 ? e(number_format((float) $row['credit'], 2)) : '-' ?></td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($row['reference'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="py-8 text-center text-gray-500">No journal entries found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="create-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Add Journal Entry</h2>
        <form action="/dashboard/ledger" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Account Name *</label>
                <input type="text" name="account_name" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Debit</label>
                    <input type="number" name="debit" step="0.01" value="0" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Credit</label>
                    <input type="number" name="credit" step="0.01" value="0" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                <input type="date" name="entry_date" value="<?= e(date('Y-m-d')) ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <input type="text" name="description" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
                <input type="text" name="reference" class="w-full border border-gray-300 rounded-lg px-4 py-2">
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
