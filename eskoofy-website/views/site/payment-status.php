<?php $title = __('checkout.status_title'); $siteTitle = $title; ?>

<section class="max-w-2xl mx-auto px-4 py-16 text-center">
    <div class="<?= $paid ? 'bg-green-50 border-green-200' : 'bg-amber-50 border-amber-200' ?> rounded-2xl border p-10">
        <div class="text-5xl mb-4"><?= $paid ? '✅' : ($cancelled ? '↩️' : '⏳') ?></div>
        <h1 class="text-2xl font-extrabold text-slate-900">
            <?= $paid ? __('checkout.status_paid') : ($cancelled ? __('checkout.status_cancelled') : __('checkout.status_pending')) ?>
        </h1>
        <p class="mt-2 text-slate-600"><?= $paid ? __('checkout.status_paid_msg') : ($cancelled ? __('checkout.status_cancelled_msg') : __('checkout.status_pending_msg')) ?></p>

        <dl class="mt-6 mx-auto max-w-sm text-left text-sm space-y-2 bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex justify-between"><dt class="text-slate-400"><?= __('checkout.reference') ?></dt><dd class="font-mono"><?= htmlspecialchars($payment['reference']) ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-400"><?= __('checkout.amount') ?></dt><dd><?= htmlspecialchars((string) $payment['currency']) ?> <?= number_format((float) $payment['amount'], 2) ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-400"><?= __('checkout.method') ?></dt><dd><?= htmlspecialchars(ucfirst($payment['gateway'])) ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-400"><?= __('checkout.status') ?></dt><dd><?= htmlspecialchars($payment['status']) ?></dd></div>
        </dl>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="/account/licenses" class="bg-slate-900 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold"><?= __('account.licenses') ?></a>
            <?php if ($paid && !empty($payment['license_id'])): ?>
                <a href="/account/licenses/<?= (int) $payment['license_id'] ?>" class="border border-slate-300 px-6 py-3 rounded-lg font-semibold text-slate-700"><?= __('checkout.view_license') ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>