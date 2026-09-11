<?php $title = __('features.title'); $siteTitle = $title; ?>

<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-16 text-center">
        <span class="inline-block text-xs uppercase tracking-widest bg-blue-500/20 text-blue-200 px-3 py-1 rounded-full border border-blue-400/30 mb-6"><?= __('features.badge') ?></span>
        <h1 class="text-4xl md:text-5xl font-extrabold"><?= __('features.title') ?></h1>
        <p class="mt-4 text-slate-300 text-lg max-w-3xl mx-auto"><?= __('features.sub') ?></p>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 py-16">

    <div class="grid md:grid-cols-3 gap-8">
        <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
            <div class="text-2xl mb-3">🎓</div>
            <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.academic') ?></div>
            <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.academic_desc') ?></p>
        </div>
        <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
            <div class="text-2xl mb-3">👥</div>
            <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.students') ?></div>
            <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.students_desc') ?></p>
        </div>
        <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
            <div class="text-2xl mb-3">📅</div>
            <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.attendance') ?></div>
            <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.attendance_desc') ?></p>
        </div>
        <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
            <div class="text-2xl mb-3">💰</div>
            <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.finance') ?></div>
            <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.finance_desc') ?></p>
        </div>
        <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
            <div class="text-2xl mb-3">📨</div>
            <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.comms') ?></div>
            <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.comms_desc') ?></p>
        </div>
        <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
            <div class="text-2xl mb-3">🔑</div>
            <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.api') ?></div>
            <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.api_desc') ?></p>
        </div>
    </div>

    <div class="mt-16 bg-slate-50 border border-slate-200 rounded-3xl p-8 md:p-10">
        <h2 class="text-2xl md:text-3xl font-extrabold text-center"><?= __('features.deploy_heading') ?></h2>
        <p class="text-slate-500 text-center mt-2 mb-8"><?= __('features.deploy_sub') ?></p>
        <div class="grid md:grid-cols-3 gap-6 text-center">
            <a href="/products/app" class="bg-white rounded-2xl border border-slate-200 p-7 esk-card-hover block">
                <div class="text-xl font-bold text-blue-700"><?= __('pricing.app_section') ?></div>
                <p class="text-sm text-slate-500 mt-2"><?= __('features.deploy_app') ?></p>
                <div class="mt-4 text-sm font-semibold text-blue-600"><?= __('features.deploy_cta') ?> →</div>
            </a>
            <a href="/products/php" class="bg-white rounded-2xl border border-slate-200 p-7 esk-card-hover block">
                <div class="text-xl font-bold text-blue-700"><?= __('pricing.php_section') ?></div>
                <p class="text-sm text-slate-500 mt-2"><?= __('features.deploy_php') ?></p>
                <div class="mt-4 text-sm font-semibold text-blue-600"><?= __('features.deploy_cta') ?> →</div>
            </a>
            <a href="/products/theme" class="bg-white rounded-2xl border border-slate-200 p-7 esk-card-hover block">
                <div class="text-xl font-bold text-blue-700"><?= __('pricing.theme_section') ?></div>
                <p class="text-sm text-slate-500 mt-2"><?= __('features.deploy_theme') ?></p>
                <div class="mt-4 text-sm font-semibold text-blue-600"><?= __('features.deploy_cta') ?> →</div>
            </a>
        </div>
    </div>
</div>