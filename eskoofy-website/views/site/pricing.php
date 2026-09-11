<?php $title = __('pricing.title'); $siteTitle = $title; ?>

<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-16 text-center">
        <span class="inline-block text-xs uppercase tracking-widest bg-blue-500/20 text-blue-200 px-3 py-1 rounded-full border border-blue-400/30 mb-6"><?= __('pricing.badge') ?></span>
        <h1 class="text-4xl md:text-5xl font-extrabold"><?= __('pricing.title') ?></h1>
        <p class="mt-4 text-slate-300 text-lg max-w-3xl mx-auto"><?= __('pricing.sub') ?></p>
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 py-16">

    <?php
    $sections = [
        ['heading' => __('pricing.app_section'), 'note' => __('pricing.app_note'), 'plans' => $appPlans, 'badge' => __('pricing.app_badge')],
        ['heading' => __('pricing.php_section'), 'note' => __('pricing.php_note'), 'plans' => $phpPlans, 'badge' => __('pricing.php_badge')],
        ['heading' => __('pricing.theme_section'), 'note' => __('pricing.theme_note'), 'plans' => $themePlans, 'badge' => __('pricing.theme_badge')],
    ];
    ?>
    <?php foreach ($sections as $idx => $sec): ?>
        <div class="mb-16 <?= $idx === 1 ? 'bg-slate-50 border border-slate-200 rounded-3xl p-6 md:p-8' : '' ?>">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold text-slate-900"><?= htmlspecialchars($sec['heading']) ?></h2>
                    <p class="text-slate-500 mt-1"><?= htmlspecialchars($sec['note']) ?></p>
                </div>
                <?php if (!empty($sec['badge'])): ?>
                    <span class="text-xs font-semibold bg-blue-600 text-white px-3 py-1 rounded-full"><?= htmlspecialchars($sec['badge']) ?></span>
                <?php endif; ?>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                <?php foreach ($sec['plans'] as $plan): ?>
                    <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover flex flex-col">
                        <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?= htmlspecialchars($plan['period']) ?> <?= __('product.license') ?></div>
                        <h3 class="text-xl font-bold mt-2"><?= htmlspecialchars($plan['name']) ?></h3>
                        <p class="text-slate-500 mt-2 text-sm flex-1"><?= htmlspecialchars((string) ($plan['description'] ?? '')) ?></p>
                        <div class="mt-5">
                            <span class="text-4xl font-extrabold text-blue-700">$<?= number_format((float) $plan['price'], 2) ?></span>
                            <span class="text-xs text-slate-400 uppercase"> <?= __('pricing.one_time_label') ?></span>
                        </div>
                        <div class="text-xs text-slate-400 uppercase tracking-wide mt-1"><?= __('pricing.per') ?> <?= htmlspecialchars($plan['period']) ?> · USD</div>

                        <?php $features = $plan['features'] ?? null; ?>
                        <?php if (is_string($features) && $features !== ''): $featList = json_decode((string) $features, true); ?>
                        <?php elseif (is_array($features)): $featList = $features; else: $featList = []; endif; ?>
                        <?php if (!empty($featList)): ?>
                            <ul class="mt-6 space-y-2 text-sm text-slate-600 flex-1">
                                <?php foreach ($featList as $f): ?>
                                    <li class="esk-check"><?= htmlspecialchars((string) $f) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <a href="/checkout?plan=<?= (int) $plan['id'] ?>" class="mt-7 block bg-slate-900 hover:bg-blue-600 text-white py-3 rounded-xl text-center font-semibold"><?= __('pricing.buy') ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="bg-white rounded-2xl border border-slate-200 p-8 max-w-3xl mx-auto text-center">
        <h3 class="text-xl font-bold"><?= __('pricing.compare_title') ?></h3>
        <p class="text-slate-500 mt-2 text-sm"><?= __('pricing.compare_sub') ?></p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="/products/app" class="esk-chip border border-blue-300 text-blue-600 px-6 py-2.5 rounded-xl font-semibold"><?= __('pricing.cta_app') ?></a>
            <a href="/products/php" class="esk-chip border border-blue-300 text-blue-600 px-6 py-2.5 rounded-xl font-semibold"><?= __('pricing.cta_php') ?></a>
            <a href="/products/theme" class="esk-chip border border-blue-300 text-blue-600 px-6 py-2.5 rounded-xl font-semibold"><?= __('pricing.cta_theme') ?></a>
        </div>
    </div>

    <div class="mt-16 max-w-3xl mx-auto">
        <h3 class="text-2xl font-bold text-center"><?= __('pricing.faq_heading') ?></h3>
        <p class="text-slate-500 text-center mt-1 mb-8"><?= __('pricing.faq_sub') ?></p>
        <div class="space-y-4">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-6">
                    <div class="font-semibold"><?= __('pricing.faq.' . $i . 'q') ?></div>
                    <p class="text-sm text-slate-600 mt-2 leading-relaxed"><?= __('pricing.faq.' . $i . 'a') ?></p>
                </div>
            <?php endfor; ?>
        </div>
        <p class="text-center text-sm text-slate-500 mt-8"><?= __('pricing.help_any') ?> <a href="/contact" class="text-blue-600 font-semibold"><?= __('pricing.help_link') ?></a></p>
    </div>
</section>