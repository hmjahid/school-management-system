<?php
$title = __('legal.' . $page . '.title');
$siteTitle = $title;
$seo = [
    'title'       => $title,
    'description' => __('legal.' . $page . '.intro'),
    'canonical'   => $canonical,
];
?>

<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-16 text-center">
        <span class="inline-block text-xs uppercase tracking-widest bg-blue-500/20 text-blue-200 px-3 py-1 rounded-full border border-blue-400/30 mb-6"><?= __('legal.badge') ?></span>
        <h1 class="text-4xl md:text-5xl font-extrabold"><?= $title ?></h1>
        <p class="mt-4 text-slate-300 text-lg max-w-3xl mx-auto"><?= __('legal.' . $page . '.intro') ?></p>
    </div>
</section>

<section class="max-w-3xl mx-auto px-4 py-16">
    <div class="text-sm text-slate-400 mb-8"><?= __('legal.last_updated') ?>: <?= __('legal.' . $page . '.updated') ?></div>
    <div class="space-y-6 text-slate-600 leading-relaxed">
        <?php for ($i = 1; $i <= 6; $i++): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h2 class="text-lg font-bold text-slate-900 mb-2"><?= __('legal.' . $page . '.h' . $i) ?></h2>
                <p><?= __('legal.' . $page . '.p' . $i) ?></p>
            </div>
        <?php endfor; ?>
    </div>
    <p class="mt-10 text-sm text-slate-500"><?= __('legal.questions') ?> <a href="/contact" class="text-blue-600 font-semibold"><?= __('nav.contact') ?></a></p>
</section>