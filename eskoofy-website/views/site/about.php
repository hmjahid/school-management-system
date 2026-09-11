<?php $title = __('nav.about'); $siteTitle = $title; ?>

<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-16 text-center">
        <span class="inline-block text-xs uppercase tracking-widest bg-blue-500/20 text-blue-200 px-3 py-1 rounded-full border border-blue-400/30 mb-6"><?= __('about.badge') ?></span>
        <h1 class="text-4xl md:text-5xl font-extrabold"><?= __('about.title') ?></h1>
        <p class="mt-4 text-slate-300 text-lg max-w-3xl mx-auto"><?= __('about.p0') ?></p>
    </div>
</section>

<section class="max-w-3xl mx-auto px-4 py-16">
    <div class="space-y-6 text-slate-600 leading-relaxed">
        <p class="text-lg"><?= __('about.1') ?></p>
        <p><?= __('about.2') ?></p>
        <p class="bg-white rounded-2xl border border-slate-200 p-6"><?= __('about.mission') ?></p>
        <p><a href="/contact" class="text-blue-600 font-semibold"><?= __('about.contact_cta') ?></a> <?= __('about.3') ?></p>
    </div>
</section>