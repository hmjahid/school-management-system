<?php $title = __('checkout.title'); $siteTitle = $title; $seo = ['title' => __('checkout.title'), 'description' => __('checkout.title') . ' — Eskoofy', 'canonical' => '/checkout', 'noindex' => true]; ?>

<?php
$gatewayNames = [
    'bkash'  => 'bKash',
    'rocket' => 'Rocket',
    'nagad'  => 'Nagad',
    'stripe' => 'Stripe',
    'paypal' => 'PayPal',
    'paddle' => 'Paddle',
    'manual' => __('checkout.manual_label'),
];
$sym = $is_bd ? '৳' : '$';
?>

<section class="max-w-3xl mx-auto px-4 py-16">
    <h1 class="text-4xl font-extrabold mb-6"><?= __('checkout.title') ?></h1>

    <div class="bg-white rounded-2xl border border-slate-200 p-8">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?= htmlspecialchars(ucfirst($plan['product'])) ?></div>
                <div class="text-xl font-bold"><?= htmlspecialchars($plan['name']) ?></div>
                <div class="text-sm text-slate-500 mt-1"><?= htmlspecialchars((string) $plan['description']) ?></div>
            </div>
            <div class="text-right">
                <div class="text-2xl font-extrabold text-blue-700"><?= $sym ?><?= number_format($display, $is_bd ? 0 : 2) ?></div>
                <div class="text-xs text-slate-400 uppercase"><?= $currency ?> / <?= htmlspecialchars($plan['period']) ?></div>
            </div>
        </div>

        <form method="post" action="/checkout" class="mt-6 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
            <div>
                <label class="block text-sm font-semibold mb-1"><?= __('checkout.customer') ?></label>
                <div class="text-slate-600"><?= htmlspecialchars($customer['name']) ?> · <?= htmlspecialchars($customer['email']) ?><?= $customer['country'] ? ' · ' . htmlspecialchars($customer['country']) : '' ?></div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2"><?= __('checkout.method') ?></label>
                <div class="grid sm:grid-cols-2 gap-2">
                    <?php foreach ($gateways as $code): ?>
                        <label class="flex items-center gap-3 border rounded-xl px-4 py-3 cursor-pointer has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50">
                            <input type="radio" name="gateway" value="<?= htmlspecialchars($code) ?>" class="accent-blue-600" required>
                            <span class="text-sm font-semibold text-slate-700"><?= htmlspecialchars($gatewayNames[$code] ?? ucfirst($code)) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-slate-400 mt-2"><?= $is_bd ? __('checkout.method_note_bd') : __('checkout.method_note') ?></p>
            </div>
            <button type="submit" class="w-full bg-slate-900 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold"><?= __('checkout.confirm') ?> — <?= $sym ?><?= number_format($display, $is_bd ? 0 : 2) ?> (<?= $currency ?>)</button>
        </form>
    </div>
</section>