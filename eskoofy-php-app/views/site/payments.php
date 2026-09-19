<?php $pageTitle = 'Payments'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Online Payments</h1>
        <p class="text-blue-100 text-lg">Pay your fees online securely</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-sm p-8 mb-8">
            <h2 class="text-xl font-bold mb-6">Select Payment Method</h2>
            <div class="grid md:grid-cols-3 gap-4 mb-8">
                <?php if (!empty($gateways)): ?>
                    <?php foreach ($gateways as $gateway): ?>
                    <label class="border-2 rounded-lg p-4 cursor-pointer hover:border-blue-500 transition <?= ($selectedGateway ?? '') == $gateway['id'] ? 'border-blue-500 bg-blue-50' : 'border-gray-200' ?>">
                        <input type="radio" name="payment_gateway" value="<?= e($gateway['id']) ?>" class="hidden gateway-radio" <?= ($selectedGateway ?? '') == $gateway['id'] ? 'checked' : '' ?>>
                        <div class="text-center">
                            <div class="text-2xl mb-2">💳</div>
                            <div class="font-bold"><?= e($gateway['name']) ?></div>
                            <?php if (!empty($gateway['is_test_mode'])): ?>
                                <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded">Test Mode</span>
                            <?php endif; ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="col-span-3 text-center text-gray-500">No payment gateways available.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-8">
            <h2 class="text-xl font-bold mb-6">Payment Details</h2>
            <form action="/payments/process" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="gateway_id" id="selected_gateway_id" value="<?= e($selectedGateway ?? '') ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Student ID *</label>
                    <input type="text" name="student_id" value="<?= old('student_id') ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fee Type *</label>
                    <select name="fee_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Fee Type</option>
                        <?php if (!empty($fees)): ?>
                            <?php foreach ($fees as $fee): ?>
                            <option value="<?= e($fee['id']) ?>" data-amount="<?= e($fee['amount']) ?>"><?= e($fee['name']) ?> - <?= e(config('currency.symbol', '$')) ?><?= number_format($fee['amount'], 2) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                    <input type="number" name="amount" value="<?= old('amount') ?>" step="0.01" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email for Receipt *</label>
                    <input type="email" name="email" value="<?= old('email') ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">Proceed to Payment</button>
            </form>
        </div>
    </div>
</section>

<script>
document.querySelectorAll('.gateway-radio').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('selected_gateway_id').value = this.value;
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>