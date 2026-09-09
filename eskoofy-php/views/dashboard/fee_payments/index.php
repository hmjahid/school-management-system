<?php $pageTitle = 'Fee Payments'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Fee Payments</h1>
    <a href="/dashboard/fee-payments/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Record Payment</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/fee-payments" method="GET" class="flex flex-col sm:flex-row gap-4">
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Search by student name or ID..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        <select name="status" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">All Status</option>
            <option value="paid" <?= ($status ?? '') == 'paid' ? 'selected' : '' ?>>Paid</option>
            <option value="pending" <?= ($status ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="partial" <?= ($status ?? '') == 'partial' ? 'selected' : '' ?>>Partial</option>
        </select>
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Receipt #</th>
                    <th class="py-3 px-4 font-semibold border-b">Student</th>
                    <th class="py-3 px-4 font-semibold border-b">Fee Type</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Amount</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Paid</th>
                    <th class="py-3 px-4 font-semibold border-b">Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Status</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($payments)): ?>
                    <?php foreach ($payments as $payment): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-mono text-sm"><?= e($payment->receipt_number) ?></td>
                        <td class="py-3 px-4"><?= e($payment->student->name ?? '') ?></td>
                        <td class="py-3 px-4"><?= e($payment->fee->name ?? '') ?></td>
                        <td class="py-3 px-4 text-right"><?= e(config('currency.symbol', '$')) ?><?= number_format($payment->amount, 2) ?></td>
                        <td class="py-3 px-4 text-right"><?= e(config('currency.symbol', '$')) ?><?= number_format($payment->paid_amount, 2) ?></td>
                        <td class="py-3 px-4"><?= e($payment->created_at->format('M d, Y')) ?></td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded-full <?= $payment->status == 'paid' ? 'bg-green-100 text-green-700' : ($payment->status == 'partial' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') ?>">
                                <?= e(ucfirst($payment->status)) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="/dashboard/fee-payments/<?= e($payment->id) ?>/receipt" class="text-blue-600 hover:underline text-sm" target="_blank">Receipt</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="py-8 text-center text-gray-500">No payments found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($paginator) && $paginator->hasPages()): ?>
        <div class="p-6">
            <?php include __DIR__ . '/../../partials/pagination.php'; ?>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>