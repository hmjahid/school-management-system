<?php $pageTitle = 'Fee Receipt'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl font-bold mb-2"><?= e($school['school_name'] ?? 'School') ?></h1>
        <p class="text-blue-100 text-sm"><?= e($school['address'] ?? '') ?></p>
        <h2 class="text-xl font-bold mt-4">Fee Receipt</h2>
    </div>
</section>

<section class="py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-sm p-8">
            <div class="flex justify-between items-start mb-6 pb-4 border-b">
                <div>
                    <p class="text-xs text-gray-500">Receipt No.</p>
                    <p class="font-bold"><?= e($payment['invoice_number']) ?></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">Date</p>
                    <p class="font-bold"><?= e(date('M d, Y', strtotime($payment['payment_date'] ?? $payment['created_at'] ?? 'now'))) ?></p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="text-xs uppercase text-gray-500 mb-2">Student</h3>
                    <p class="font-bold"><?= e($payment['student_name'] ?? 'N/A') ?></p>
                    <p class="text-sm text-gray-600">Admission: <?= e($payment['admission_number'] ?? '') ?></p>
                    <p class="text-sm text-gray-600">Class: <?= e($payment['class_name'] ?? '') ?><?= !empty($payment['section_name']) ? ' - ' . e($payment['section_name']) : '' ?></p>
                </div>
                <div>
                    <h3 class="text-xs uppercase text-gray-500 mb-2">Fee</h3>
                    <p class="font-bold"><?= e($payment['fee_name'] ?? 'N/A') ?></p>
                    <p class="text-sm text-gray-600">Status: <span class="font-semibold <?= ($payment['status'] ?? '') === 'paid' ? 'text-green-600' : 'text-yellow-600' ?>"><?= e(ucfirst($payment['status'] ?? '')) ?></span></p>
                </div>
            </div>

            <table class="w-full text-left mb-6">
                <thead>
                    <tr class="border-b">
                        <th class="py-2">Description</th>
                        <th class="py-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="py-2"><?= e($payment['fee_name'] ?? 'Fee') ?></td>
                        <td class="py-2 text-right"><?= e(format_currency((float)$payment['amount'])) ?></td>
                    </tr>
                    <?php if ((float)($payment['discount_amount'] ?? 0) > 0): ?>
                    <tr>
                        <td class="py-2">Discount</td>
                        <td class="py-2 text-right text-green-600">- <?= e(format_currency((float)$payment['discount_amount'])) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ((float)($payment['fine_amount'] ?? 0) > 0): ?>
                    <tr>
                        <td class="py-2">Fine</td>
                        <td class="py-2 text-right text-red-600">+ <?= e(format_currency((float)$payment['fine_amount'])) ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="border-t font-bold">
                        <td class="py-2">Total Paid</td>
                        <td class="py-2 text-right"><?= e(format_currency((float)$payment['paid_amount'])) ?></td>
                    </tr>
                    <?php if ((float)($payment['balance'] ?? 0) > 0): ?>
                    <tr>
                        <td class="py-2">Balance Due</td>
                        <td class="py-2 text-right text-red-600"><?= e(format_currency((float)$payment['balance'])) ?></td>
                    </tr>
                    <?php endif; ?>
                </tfoot>
            </table>

            <div class="border-t pt-4 text-xs text-gray-500">
                <p>Payment Method: <?= e(ucfirst($payment['payment_method'] ?? 'N/A')) ?></p>
                <?php if (!empty($payment['transaction_id'])): ?>
                    <p>Transaction ID: <?= e($payment['transaction_id']) ?></p>
                <?php endif; ?>
                <?php if (!empty($payment['receipt_name'])): ?>
                    <p>Received by: <?= e($payment['receipt_name']) ?></p>
                <?php endif; ?>
                <p class="mt-4 italic">This is a computer-generated receipt. No signature required.</p>
            </div>

            <div class="mt-6 text-center">
                <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Print Receipt</button>
            </div>
        </div>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
