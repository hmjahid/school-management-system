<?php
$title = __('choose.result_title');
$siteTitle = $title;
$seo = ['title' => __('choose.result_title'), 'description' => __('choose.sub'), 'canonical' => '/choose'];

$pick = static function (string $product) use ($candidates): ?array {
    foreach ($candidates as $c) {
        if ($c['key'] === $product) {
            return $c;
        }
    }

    return null;
};

$best = $pick($result['product']);
$second = $pick($result['runnerUp']);
?>

<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-16 text-center">
        <span class="inline-block text-xs uppercase tracking-widest bg-blue-500/20 text-blue-200 px-3 py-1 rounded-full border border-blue-400/30 mb-6"><?= __('choose.badge') ?></span>
        <h1 class="text-4xl md:text-5xl font-extrabold"><?= __('choose.result_title') ?></h1>
        <p class="mt-4 text-slate-300 text-lg max-w-3xl mx-auto"><?= __('choose.result_sub') ?></p>
    </div>
</section>

<section class="max-w-4xl mx-auto px-4 py-16">
    <div class="rounded-3xl border-2 border-blue-600 bg-white p-8 md:p-10 esk-card-hover">
        <div class="text-xs uppercase tracking-widest text-blue-600 font-bold"><?= __('choose.result_recommended') ?></div>
        <h2 class="text-3xl font-extrabold mt-2"><?= __($best['label_key']) ?></h2>
        <p class="mt-3 text-slate-600 leading-relaxed"><?= __($best['desc_key']) ?></p>
        <p class="mt-4 text-sm text-slate-500"><strong class="text-slate-700"><?= __('choose.why') ?></strong> <?= __($result['reason_key']) ?></p>
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="<?= htmlspecialchars($best['link']) ?>" class="esk-btn-primary bg-blue-600 hover:bg-blue-500 text-white px-7 py-3.5 rounded-xl font-semibold"><?= __('choose.cta_view') ?></a>
            <?php if ($second): ?>
                <a href="<?= htmlspecialchars($second['link']) ?>" class="border border-slate-300 text-slate-700 px-7 py-3.5 rounded-xl font-semibold"><?= __('choose.cta_runner') ?>: <?= __($second['label_key']) ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-10">
        <h3 class="text-lg font-bold text-slate-900 mb-4"><?= __('choose.all_variants') ?></h3>
        <div class="grid sm:grid-cols-2 gap-4">
            <?php foreach ($candidates as $c): ?>
                <div class="border border-slate-200 rounded-2xl p-6 bg-white">
                    <div class="font-bold text-slate-900"><?= __($c['label_key']) ?></div>
                    <p class="text-sm text-slate-600 mt-1.5"><?= __($c['desc_key']) ?></p>
                    <a href="<?= htmlspecialchars($c['link']) ?>" class="text-blue-600 text-sm font-semibold hover:underline inline-block mt-3"><?= $c['key'] === $result['product'] ? __('choose.recommended_label') : __('choose.cta_view') ?></a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mt-10 rounded-2xl bg-slate-50 border border-slate-200 p-6 text-center">
        <p class="text-slate-600 text-sm"><?= __('choose.not_sure') ?></p>
        <a href="/custom-order" class="text-blue-600 font-semibold hover:underline text-sm"><?= __('choose.custom_cta') ?></a>
    </div>
</section>

<?php \App\Core\View::partial('site.partials.cta-band'); ?>