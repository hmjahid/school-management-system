<?php $pageTitle = 'Student Fees'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Fee History: <?= e($student['name'] ?? '') ?></h1>
        <p class="text-gray-500">Admission: <?= e($student['admission_number'] ?? '') ?> | Class: <?= e($student['class_name'] ?? '') ?></p>
    </div>
    <a href="/dashboard/students/<?= e($student['id']) ?>" class="text-gray-600 hover:text-gray-800">&larr; Back to Student</a>
</div>

<div class="grid grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-green-600"><?= e(number_format($totalPaid, 2)) ?></div>
        <div class="text-gray-500">Total Paid</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-red-600"><?= e(number_format($totalDue, 2)) ?></div>
        <div class="text-gray-500">Outstanding Balance</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="p-6 border-b">
        <h2 class="text-lg font-bold">Assigned Fees</h2>
    </div>
    <?php if (!empty($fees)): ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Fee Name</th>
                    <th class="py-3 px-4 font-semibold border-b">Class</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Amount</th>
                    <th class="py-3 px-4 font-semibold border-b">Due Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Type</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fees as $fee): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4 font-medium"><?= e($fee['name'] ?? '') ?></td>
                    <td class="py-3 px-4"><?= e($fee['class_name'] ?? 'All') ?></td>
                    <td class="py-3 px-4 text-center font-medium"><?= e(number_format((float) ($fee['amount'] ?? 0), 2)) ?></td>
                    <td class="py-3 px-4 text-sm text-gray-500"><?= e($fee['due_date'] ?? '-') ?></td>
                    <td class="py-3 px-4">
                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700"><?= e(ucfirst($fee['fee_type'] ?? 'one_time')) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="p-8 text-center text-gray-500">No fees assigned.</div>
    <?php endif; ?>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-lg font-bold">Payment History</h2>
    </div>
    <?php if (!empty($payments)): ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Fee</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Amount</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Balance</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Status</th>
                    <th class="py-3 px-4 font-semibold border-b">Method</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4 text-sm"><?= e($payment['created_at'] ?? '') ?></td>
                    <td class="py-3 px-4 font-medium"><?= e($payment['fee_name'] ?? '') ?></td>
                    <td class="py-3 px-4 text-center font-medium text-green-600"><?= e(number_format((float) ($payment['amount_paid'] ?? 0), 2)) ?></td>
                    <td class="py-3 px-4 text-center text-red-600"><?= e(number_format((float) ($payment['balance'] ?? 0), 2)) ?></td>
                    <td class="py-3 px-4 text-center">
                        <?php
                        $statusColor = 'bg-gray-100 text-gray-700';
                        if (($payment['status'] ?? '') === 'paid') $statusColor = 'bg-green-100 text-green-700';
                        if (($payment['status'] ?? '') === 'pending') $statusColor = 'bg-yellow-100 text-yellow-700';
                        if (($payment['status'] ?? '') === 'partial') $statusColor = 'bg-orange-100 text-orange-700';
                        ?>
                        <span class="px-2 py-1 text-xs rounded-full <?= $statusColor ?>"><?= e(ucfirst($payment['status'] ?? '')) ?></span>
                    </td>
                    <td class="py-3 px-4 text-sm text-gray-500"><?= e($payment['payment_method'] ?? '-') ?></td>
                    <td class="py-3 px-4">
                        <?php if (!empty($payment['id'])): ?>
                        <a href="/dashboard/fee-payments/<?= e($payment['id']) ?>/receipt" class="text-blue-600 hover:underline text-sm">Receipt</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="p-8 text-center text-gray-500">No payment records found.</div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
