<?php $pageTitle = 'Payment Gateways'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Payment Gateway Configuration</h1>
    <p class="text-gray-500">Enable a gateway and store its credentials. Only <strong>enabled</strong> gateways are offered to payers.</p>
</div>

<?php if (empty($gateways)): ?>
    <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">No payment gateways found.</div>
<?php endif; ?>

<?php foreach ($gateways as $gateway): ?>
    <?php $enabled = !empty($gateway['is_active']); ?>
    <form action="/dashboard/payment-gateways/<?= (int) $gateway['id'] ?>" method="POST" class="bg-white rounded-xl shadow-sm p-6 mb-4">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <input type="hidden" name="name" value="<?= e($gateway['name']) ?>">
        <input type="hidden" name="sort_order" value="<?= (int) ($gateway['sort_order'] ?? 0) ?>">

        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold"><?= e($gateway['name']) ?></h2>
                <p class="text-sm text-gray-500 font-mono"><?= e($gateway['code']) ?></p>
            </div>
            <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" <?= $enabled ? 'checked' : '' ?> class="rounded border-gray-300">
                Enabled
            </label>
        </div>

        <?php if (!empty($gateway['is_online'])): ?>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                    <input type="text" name="api_key" value="<?= e($gateway['api_key'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API Secret</label>
                    <input type="password" name="api_secret" value="<?= e($gateway['api_secret'] ?? '') ?>" autocomplete="off" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div class="flex items-center">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" name="test_mode" value="1" <?= !empty($gateway['test_mode']) ? 'checked' : '' ?> class="rounded border-gray-300">
                        Sandbox / test mode
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Webhook URL</label>
                    <input type="text" value="<?= e(url('/payments/webhook/' . $gateway['code'])) ?>" readonly class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-50">
                </div>
            </div>
        <?php else: ?>
            <p class="text-sm text-gray-500">Offline gateway — no credentials required.</p>
        <?php endif; ?>

        <div class="mt-4 flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700">Save</button>
        </div>
    </form>
<?php endforeach; ?>

<p class="text-sm text-gray-500 mt-4">
    Only gateways with <strong>Enabled</strong> checked appear on the
    <a href="/payments" class="text-blue-600 hover:underline">/payments</a> page.
</p>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
