<?php $pageTitle = 'Payment Receipt'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6 print:hidden">
    <h1 class="text-2xl font-bold text-gray-800">Payment Receipt</h1>
    <div class="flex space-x-2">
        <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">🖨️ Print</button>
        <a href="/dashboard/fee-payments" class="text-gray-600 hover:text-gray-800">← Back</a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-8 max-w-2xl mx-auto" id="receipt">
    <div class="text-center border-b pb-6 mb-6">
        <h1 class="text-2xl font-bold text-blue-600"><?= e(config('school.name', 'School')) ?></h1>
        <p class="text-gray-500 text-sm"><?= e(config('school.address', '')) ?></p>
        <p class="text-gray-500 text-sm"><?= e(config('school.phone', '')) ?> | <?= e(config('school.email', '')) ?></p>
    </div>

    <div class="text-center mb-6">
        <h2 class="text-xl font-bold">FEE PAYMENT RECEIPT</h2>
        <p class="text-sm text-gray-500">Receipt #: <?= e($payment->receipt_number) ?></p>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-6">
        <div>
            <p class="text-sm text-gray-500">Date</p>
            <p class="font-medium"><?= e($payment->created_at->format('M d, Y')) ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Payment Method</p>
            <p class="font-medium"><?= e(ucfirst($payment->method ?? 'Cash')) ?></p>
        </div>
    </div>

    <div class="border rounded-lg p-4 mb-6">
        <h3 class="font-bold mb-3">Student Information</h3>
        <div class="grid grid-cols-2 gap-2 text-sm">
            <div><span class="text-gray-500">Name:</span> <?= e($payment->student->name ?? '') ?></div>
            <div><span class="text-gray-500">ID:</span> <?= e($payment->student->student_id ?? '') ?></div>
            <div><span class="text-gray-500">Class:</span> <?= e($payment->student->class->name ?? '') ?></div>
            <div><span class="text-gray-500">Roll:</span> <?= e($payment->student->roll ?? '') ?></div>
        </div>
    </div>

    <table class="w-full text-left mb-6">
        <thead>
            <tr class="border-b">
                <th class="py-2 font-semibold">Description</th>
                <th class="py-2 font-semibold text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b">
                <td class="py-3"><?= e($payment->fee->name ?? 'Fee Payment') ?></td>
                <td class="py-3 text-right"><?= e(config('currency.symbol', '$')) ?><?= number_format($payment->amount, 2) ?></td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="font-bold text-lg">
                <td class="py-3">Total Paid</td>
                <td class="py-3 text-right text-green-600"><?= e(config('currency.symbol', '$')) ?><?= number_format($payment->paid_amount, 2) ?></td>
            </tr>
            <?php if (($payment->amount - $payment->paid_amount) > 0): ?>
            <tr class="text-red-600">
                <td class="py-1">Due</td>
                <td class="py-1 text-right"><?= e(config('currency.symbol', '$')) ?><?= number_format($payment->amount - $payment->paid_amount, 2) ?></td>
            </tr>
            <?php endif; ?>
        </tfoot>
    </table>

    <div class="text-center text-sm text-gray-500 border-t pt-6">
        <p>This is a computer-generated receipt and does not require a signature.</p>
        <p class="mt-1">&copy; <?= date('Y') ?> <?= e(config('school.name', 'School')) ?>. All rights reserved.</p>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>