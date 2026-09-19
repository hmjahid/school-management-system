<?php $pageTitle = 'Payment Settings'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <a href="/dashboard/settings" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    <h1 class="text-2xl font-bold text-gray-800">Payment Settings</h1>
</div>

<div class="bg-white rounded-xl shadow-sm p-8 mb-6">
    <h2 class="text-lg font-bold mb-4">General</h2>
    <form action="/dashboard/settings/payment" method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Default Gateway</label>
                <select name="default_gateway" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="bkash">bKash</option>
                    <option value="nagad">Nagad</option>
                    <option value="rocket">Rocket</option>
                    <option value="stripe">Stripe</option>
                    <option value="paypal">PayPal</option>
                    <option value="paddle">Paddle</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                <input type="text" name="currency" value="<?= e(config('payment.currency', 'BDT')) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Save</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm p-8">
    <h2 class="text-lg font-bold mb-4">Active Gateways</h2>
    <table class="w-full text-left">
        <thead><tr class="bg-gray-50"><th class="py-2 px-3">Name</th><th class="py-2 px-3">Code</th><th class="py-2 px-3">Active</th></tr></thead>
        <tbody>
            <?php if (!empty($gateways)): ?>
                <?php foreach ($gateways as $g): ?>
                <tr class="border-b">
                    <td class="py-2 px-3"><?= e($g['name']) ?></td>
                    <td class="py-2 px-3 font-mono text-xs"><?= e($g['code']) ?></td>
                    <td class="py-2 px-3"><?= !empty($g['is_active']) ? '✓' : '✗' ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" class="py-4 text-center text-gray-500">No gateways configured.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <p class="mt-4 text-sm"><a href="/dashboard/payment-gateways" class="text-blue-600 hover:underline">Manage Gateways →</a></p>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
