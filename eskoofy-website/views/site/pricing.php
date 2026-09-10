<?php $title = __('pricing.title'); $siteTitle = $title; ?>

<section class="max-w-7xl mx-auto px-4 py-16">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-extrabold"><?= __('pricing.title') ?></h1>
        <p class="text-slate-500 mt-2"><?= __('pricing.sub') ?></p>
    </div>

    <div class="mb-10">
        <h2 class="text-2xl font-bold mb-4 text-blue-700"><?= __('pricing.app_section') ?></h2>
        <div class="grid md:grid-cols-3 gap-6">
            <?php foreach ($appPlans as $plan): ?>
                <div class="bg-white rounded-2xl p-8 border border-slate-200 esk-card-hover flex flex-col">
                    <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?= htmlspecialchars($plan['period']) ?></div>
                    <h3 class="text-xl font-bold mt-2"><?= htmlspecialchars($plan['name']) ?></h3>
                    <p class="text-slate-500 mt-2 text-sm flex-1"><?= htmlspecialchars((string) ($plan['description'] ?? '')) ?></p>
                    <div class="mt-4 text-4xl font-extrabold">$<?= number_format((float) $plan['price'], 2) ?></div>
                    <div class="text-xs text-slate-400 uppercase tracking-wide"><?= __('pricing.per') ?> <?= htmlspecialchars($plan['period']) ?></div>
                    <a href="/checkout?plan=<?= (int) $plan['id'] ?>" class="mt-6 block bg-slate-900 hover:bg-blue-600 text-white py-3 rounded-lg text-center font-semibold"><?= __('pricing.buy') ?></a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div>
        <h2 class="text-2xl font-bold mb-4 text-blue-700"><?= __('pricing.theme_section') ?></h2>
        <div class="grid md:grid-cols-3 gap-6">
            <?php foreach ($themePlans as $plan): ?>
                <div class="bg-white rounded-2xl p-8 border border-slate-200 esk-card-hover flex flex-col">
                    <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?= htmlspecialchars($plan['period']) ?></div>
                    <h3 class="text-xl font-bold mt-2"><?= htmlspecialchars($plan['name']) ?></h3>
                    <p class="text-slate-500 mt-2 text-sm flex-1"><?= htmlspecialchars((string) ($plan['description'] ?? '')) ?></p>
                    <div class="mt-4 text-4xl font-extrabold">$<?= number_format((float) $plan['price'], 2) ?></div>
                    <div class="text-xs text-slate-400 uppercase tracking-wide"><?= __('pricing.per') ?> <?= htmlspecialchars($plan['period']) ?></div>
                    <a href="/checkout?plan=<?= (int) $plan['id'] ?>" class="mt-6 block bg-slate-900 hover:bg-blue-600 text-white py-3 rounded-lg text-center font-semibold"><?= __('pricing.buy') ?></a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>