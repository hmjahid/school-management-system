<?php $pageTitle = 'Payments'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Payment Transactions</h1>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/payments" method="GET" class="flex flex-col sm:flex-row gap-4">
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Search..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        <select name="gateway" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">All Gateways</option>
            <option value="stripe" <?= ($gateway ?? '') == 'stripe' ? 'selected' : '' ?>>Stripe</option>
            <option value="paypal" <?= ($gateway ?? '') == 'paypal' ? 'selected' : '' ?>>PayPal</option>
            <option value="bkash" <?= ($gateway ?? '') == 'bkash' ? 'selected' : '' ?>>bKash</option>
            <option value="nagad" <?= ($gateway ?? '') == 'nagad' ? 'selected' : '' ?>>Nagad</option>
        </select>
        <select name="status" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">All Status</option>
            <option value="completed" <?= ($status ?? '') == 'completed' ? 'selected' : '' ?>>Completed</option>
            <option value="pending" <?= ($status ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="failed" <?= ($status ?? '') == 'failed' ? 'selected' : '' ?>>Failed</option>
            <option value="refunded" <?= ($status ?? '') == 'refunded' ? 'selected' : '' ?>>Refunded</option>
        </select>
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Transaction ID</th>
                    <th class="py-3 px-4 font-semibold border-b">Student</th>
                    <th class="py-3 px-4 font-semibold border-b">Gateway</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Amount</th>
                    <th class="py-3 px-4 font-semibold border-b">Status</th>
                    <th class="py-3 px-4 font-semibold border-b">Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($payments)): ?>
                    <?php foreach ($payments as $payment): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-mono text-sm"><?= e($payment->transaction_id) ?></td>
                        <td class="py-3 px-4"><?= e($payment->student->name ?? '') ?></td>
                        <td class="py-3 px-4"><?= e(ucfirst($payment->gateway ?? '')) ?></td>
                        <td class="py-3 px-4 text-right font-bold"><?= e(config('currency.symbol', '$')) ?><?= number_format($payment->amount, 2) ?></td>
                        <td class="py-3 px-4">
                            <?php
                            $statusColors = [
                                'completed' => 'bg-green-100 text-green-700',
                                'pending' => 'bg-yellow-100 text-yellow-700',
                                'failed' => 'bg-red-100 text-red-700',
                                'refunded' => 'bg-purple-100 text-purple-700',
                            ];
                            ?>
                            <span class="px-2 py-1 text-xs rounded-full <?= $statusColors[$payment->status] ?? 'bg-gray-100 text-gray-700' ?>">
                                <?= e(ucfirst($payment->status)) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4"><?= e($payment->created_at->format('M d, Y')) ?></td>
                        <td class="py-3 px-4">
                            <a href="/dashboard/payments/<?= e($payment->id) ?>" class="text-blue-600 hover:underline text-sm">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-500">No payments found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($paginator) && $paginator->hasPages()): ?>
        <div class="p-6"><?php include __DIR__ . '/../../partials/pagination.php'; ?></div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>