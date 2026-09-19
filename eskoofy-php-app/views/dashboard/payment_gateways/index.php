<?php $pageTitle = 'Payment Gateways'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Payment Gateway Configuration</h1>
    <p class="text-gray-500">Configure your payment gateway settings</p>
</div>

<form action="/dashboard/payment-gateways" method="POST" class="space-y-6">
    <?= csrf_field() ?>

    <?php foreach ($gateways as $key => $gateway): ?>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                <div class="text-2xl mr-3"><?= e($gateway['icon'] ?? '💳') ?></div>
                <div>
                    <h2 class="text-lg font-bold"><?= e($gateway['name']) ?></h2>
                    <p class="text-sm text-gray-500"><?= e($gateway['description'] ?? '') ?></p>
                </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="gateways[<?= e($key) ?>][enabled]" value="1" <?= ($gateway['enabled'] ?? false) ? 'checked' : '' ?> class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
            </label>
        </div>

        <div class="gateway-config grid md:grid-cols-2 gap-4 <?= ($gateway['enabled'] ?? false) ? '' : 'hidden' ?>" data-gateway="<?= e($key) ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                <input type="text" name="gateways[<?= e($key) ?>][api_key]" value="<?= e($gateway['api_key'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" placeholder="Enter API key">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">API Secret</label>
                <input type="password" name="gateways[<?= e($key) ?>][api_secret]" value="<?= e($gateway['api_secret'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" placeholder="Enter API secret">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mode</label>
                <select name="gateways[<?= e($key) ?>][mode]" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="test" <?= ($gateway['mode'] ?? 'test') == 'test' ? 'selected' : '' ?>>Test</option>
                    <option value="live" <?= ($gateway['mode'] ?? 'test') == 'live' ? 'selected' : '' ?>>Live</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Webhook URL</label>
                <input type="text" value="<?= e(url('/payments/webhook/' . $key)) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-50" readonly>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="flex justify-end">
        <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">Save Configuration</button>
    </div>
</form>

<script>
document.querySelectorAll('input[type="checkbox"][data-gateway]').forEach(checkbox => {
    // Already handled by generic toggle below
});
document.querySelectorAll('.gateway-config').forEach(config => {
    const toggle = config.previousElementSibling.querySelector('input[type="checkbox"]');
    toggle.addEventListener('change', function() {
        config.classList.toggle('hidden', !this.checked);
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>