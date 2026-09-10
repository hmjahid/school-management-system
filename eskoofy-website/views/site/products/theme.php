<?php $title = __('product.theme_title'); $siteTitle = $title; ?>

<section class="esk-hero text-white">
    <div class="max-w-7xl mx-auto px-4 py-16">
        <span class="text-xs uppercase tracking-widest bg-blue-500/20 text-blue-200 px-3 py-1 rounded-full"><?= __('pricing.theme_section') ?></span>
        <h1 class="text-4xl md:text-5xl font-extrabold mt-4"><?= __('product.theme_title') ?></h1>
        <p class="mt-4 text-slate-300 text-lg max-w-2xl"><?= __('product.theme_sub') ?></p>
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 py-16 grid md:grid-cols-3 gap-8">
    <?php foreach ($plans as $plan): ?>
        <div class="bg-white rounded-2xl p-8 border border-slate-200 esk-card-hover flex flex-col">
            <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?= htmlspecialchars($plan['period']) ?> <?= __('product.license') ?></div>
            <h2 class="text-2xl font-bold mt-2"><?= htmlspecialchars($plan['name']) ?></h2>
            <p class="text-slate-500 mt-2 text-sm flex-1"><?= htmlspecialchars((string) ($plan['description'] ?? '')) ?></p>
            <div class="mt-4 text-4xl font-extrabold text-blue-700">$<?= number_format((float) $plan['price'], 2) ?></div>
            <div class="text-xs text-slate-400 uppercase tracking-wide mt-1"><?= htmlspecialchars($plan['period']) ?> · USD</div>
            <a href="/checkout?plan=<?= (int) $plan['id'] ?>" class="mt-6 block bg-slate-900 hover:bg-blue-600 text-white py-3 rounded-lg text-center font-semibold"><?= __('product.buy') ?></a>
        </div>
    <?php endforeach; ?>
</section>

<section class="max-w-7xl mx-auto px-4 pb-16">
    <div class="bg-white rounded-2xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-4"><?= __('product.theme_features') ?></h2>
        <div class="grid md:grid-cols-2 gap-x-8 gap-y-2 text-sm text-slate-600">
            <?php foreach (['theme_f1', 'theme_f2', 'theme_f3', 'theme_f4', 'theme_f5', 'theme_f6'] as $key): ?>
                <p>• <?= __('product.' . $key) ?></p>
            <?php endforeach; ?>
        </div>
    </div>
</section>