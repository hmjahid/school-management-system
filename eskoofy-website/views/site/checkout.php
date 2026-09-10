<?php $title = __('checkout.title'); $siteTitle = $title; ?>

<section class="max-w-3xl mx-auto px-4 py-16">
    <h1 class="text-4xl font-extrabold mb-6"><?= __('checkout.title') ?></h1>

    <div class="bg-white rounded-2xl border border-slate-200 p-8">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?= htmlspecialchars(ucfirst($plan['product'])) ?></div>
                <div class="text-xl font-bold"><?= htmlspecialchars($plan['name']) ?></div>
            </div>
            <div class="text-2xl font-extrabold text-blue-700">$<?= number_format((float) $plan['price'], 2) ?></div>
        </div>

        <form method="post" action="/checkout" class="mt-6 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
            <div>
                <label class="block text-sm font-semibold mb-1"><?= __('checkout.customer') ?></label>
                <div class="text-slate-600"><?= htmlspecialchars($customer['name']) ?> · <?= htmlspecialchars($customer['email']) ?></div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1"><?= __('checkout.method') ?></label>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="border-2 border-blue-600 text-blue-700 rounded-lg px-4 py-2 text-sm font-semibold cursor-default">
                        <?= htmlspecialchars(ucfirst($gateway)) ?> — pay on invoice
                    </button>
                    <input type="hidden" name="gateway" value="<?= htmlspecialchars($gateway) ?>">
                </div>
                <p class="text-xs text-slate-400 mt-2"><?= __('checkout.method_note') ?></p>
            </div>
            <button type="submit" class="bg-slate-900 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold"><?= __('checkout.confirm') ?> — $<?= number_format((float) $plan['price'], 2) ?></button>
        </form>
    </div>
</section>