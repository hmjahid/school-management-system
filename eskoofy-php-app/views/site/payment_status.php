<?php $pageTitle = 'Payment Status'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Payment Status</h1>
    </div>
</section>

<section class="py-16">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (!$payment): ?>
            <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                <div class="text-6xl mb-4">❓</div>
                <h2 class="text-2xl font-bold mb-2">Payment Not Found</h2>
                <p class="text-gray-600">We couldn't find a payment with that reference. Please contact the school office.</p>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                <?php if ($status === 'completed'): ?>
                    <div class="text-6xl mb-4">✅</div>
                    <h2 class="text-2xl font-bold text-green-600 mb-2">Payment Successful</h2>
                <?php elseif ($status === 'pending'): ?>
                    <div class="text-6xl mb-4">⏳</div>
                    <h2 class="text-2xl font-bold text-yellow-600 mb-2">Payment Pending</h2>
                <?php else: ?>
                    <div class="text-6xl mb-4">❌</div>
                    <h2 class="text-2xl font-bold text-red-600 mb-2">Payment <?= e(ucfirst($status)) ?></h2>
                <?php endif; ?>

                <div class="mt-6 text-left bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm"><strong>Invoice:</strong> <?= e($payment['invoice_number'] ?? $payment['id']) ?></p>
                    <p class="text-sm"><strong>Amount:</strong> <?= e(format_currency((float)($payment['paid_amount'] ?? $payment['amount'] ?? 0))) ?></p>
                    <p class="text-sm"><strong>Method:</strong> <?= e(ucfirst($payment['payment_method'] ?? 'N/A')) ?></p>
                    <?php if ($gateway): ?><p class="text-sm"><strong>Gateway:</strong> <?= e($gateway) ?></p><?php endif; ?>
                    <?php if (!empty($payment['transaction_id'])): ?>
                        <p class="text-sm"><strong>Transaction ID:</strong> <?= e($payment['transaction_id']) ?></p>
                    <?php endif; ?>
                    <p class="text-sm"><strong>Verified:</strong> <?= $verified ? 'Yes' : 'Pending verification' ?></p>
                    <p class="text-sm"><strong>Date:</strong> <?= e(date('M d, Y H:i', strtotime($payment['created_at'] ?? 'now'))) ?></p>
                </div>

                <a href="/" class="inline-block mt-6 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Back to Home</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
