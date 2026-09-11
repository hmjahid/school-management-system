<?php $title = __('product.app_title'); $siteTitle = $title; ?>

<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-16 md:py-20">
        <div class="max-w-3xl">
            <span class="text-xs uppercase tracking-widest bg-blue-500/20 text-blue-200 px-3 py-1 rounded-full border border-blue-400/30"><?= __('pricing.app_section') ?></span>
            <h1 class="text-4xl md:text-5xl font-extrabold mt-4"><?= __('product.app_title') ?></h1>
            <p class="mt-4 text-slate-300 text-lg leading-relaxed"><?= __('product.app_sub') ?></p>
            <div class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-200">
                <span>✓ <?= __('product.app_deploy_1') ?></span>
                <span>✓ <?= __('product.app_deploy_2') ?></span>
            </div>
        </div>
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 py-16 grid md:grid-cols-3 gap-8">
    <?php foreach ($plans as $plan): ?>
        <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover flex flex-col">
            <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?= htmlspecialchars($plan['period']) ?> <?= __('product.license') ?></div>
            <h2 class="text-2xl font-bold mt-2"><?= htmlspecialchars($plan['name']) ?></h2>
            <p class="text-slate-500 mt-2 text-sm flex-1"><?= htmlspecialchars((string) ($plan['description'] ?? '')) ?></p>
            <div class="mt-5">
                <span class="text-4xl font-extrabold text-blue-700">$<?= number_format((float) $plan['price'], 2) ?></span>
                <span class="text-xs text-slate-400 uppercase"> per <?= htmlspecialchars($plan['period']) ?></span>
            </div>
            <div class="text-xs text-slate-400 uppercase tracking-wide mt-1">USD · <?= __('pricing.one_time_label') ?></div>
            <a href="/checkout?plan=<?= (int) $plan['id'] ?>" class="mt-6 block bg-slate-900 hover:bg-blue-600 text-white py-3 rounded-xl text-center font-semibold"><?= __('product.buy') ?></a>
        </div>
    <?php endforeach; ?>
</section>

<section class="max-w-7xl mx-auto px-4 pb-16">
    <div class="bg-white rounded-3xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-6"><?= __('product.app_features') ?></h2>
        <div class="grid md:grid-cols-2 gap-x-8 gap-y-3 text-sm text-slate-600">
            <?php foreach (['app_f1', 'app_f2', 'app_f3', 'app_f4', 'app_f5', 'app_f6', 'app_f7', 'app_f8'] as $key): ?>
                <p class="esk-check"><?= __('product.' . $key) ?></p>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 pb-16">
    <div class="rounded-3xl bg-slate-50 border border-slate-200 p-8 md:p-10">
        <h2 class="text-2xl font-bold mb-6"><?= __('product.faq_heading') ?></h2>
        <div class="space-y-4">
            <?php for ($i = 1; $i <= 4; $i++): ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-6">
                    <div class="font-semibold"><?= __('product.faq.' . $i . 'q') ?></div>
                    <p class="text-sm text-slate-600 mt-2 leading-relaxed"><?= __('product.faq.' . $i . 'a') ?></p>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</section>