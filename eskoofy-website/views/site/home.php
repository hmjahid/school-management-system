<?php
$eskSettings = \App\Models\Settings::all();
$eskName = (string) ($eskSettings['site.name'] ?? '');
$eskTag = (string) ($eskSettings['site.tagline'] ?? '');
$eskName = ($eskName === '' || $eskName === 'Eskoofy') ? __('brand.name') : $eskName;
$eskTag = ($eskTag === '' || $eskTag === 'School management software & WordPress theme') ? __('brand.tagline') : $eskTag;
$siteTitle = $eskName . ' — ' . $eskTag;
?>

<!-- Hero -->
<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-20 md:py-28 lg:grid lg:grid-cols-2 lg:gap-16 items-center">
        <div class="esk-fade-up">
            <span class="inline-block text-xs uppercase tracking-widest bg-blue-500/20 text-blue-200 px-3 py-1 rounded-full border border-blue-400/30 mb-6"><?= __('home.badge') ?></span>
            <h1 class="text-4xl md:text-5xl font-extrabold leading-tight"><?= __('home.title') ?></h1>
            <p class="mt-5 text-slate-300 text-lg leading-relaxed"><?= __('home.subtitle') ?></p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="/pricing" class="esk-btn-primary bg-blue-600 hover:bg-blue-500 px-7 py-3.5 rounded-xl font-semibold"><?= __('home.cta_pricing') ?></a>
                <a href="/register" class="border border-white/30 hover:border-white/60 bg-white/5 backdrop-blur px-7 py-3.5 rounded-xl font-semibold"><?= __('home.cta_register') ?></a>
                <a href="/compare" class="border border-white/30 hover:border-white/60 bg-white/5 backdrop-blur px-7 py-3.5 rounded-xl font-semibold"><?= __('nav.compare') ?></a>
            </div>
        </div>

        <div class="mt-12 lg:mt-0 relative">
            <!-- Dashboard mockup -->
            <div class="bg-white/10 border border-white/15 rounded-2xl overflow-hidden backdrop-blur shadow-2xl shadow-blue-900/30">
                <div class="flex items-center gap-2 border-b border-white/10 px-5 py-3">
                    <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-green-400"></span>
                    <span class="ml-3 text-xs text-slate-300"><?= __('home.mock_browser') ?></span>
                </div>
                <div class="grid grid-cols-3 gap-3 p-5">
                    <div class="col-span-2 space-y-3">
                        <div class="h-5 w-2/3 rounded bg-white/15"></div>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-lg bg-white/10 p-3"><div class="text-xl font-extrabold text-white">1,240</div><div class="text-[10px] text-slate-300"><?= __('home.mock_students') ?></div></div>
                            <div class="rounded-lg bg-white/10 p-3"><div class="text-xl font-extrabold text-white">98%</div><div class="text-[10px] text-slate-300"><?= __('home.mock_attendance') ?></div></div>
                            <div class="rounded-lg bg-white/10 p-3"><div class="text-xl font-extrabold text-white">$18k</div><div class="text-[10px] text-slate-300"><?= __('home.mock_fees') ?></div></div>
                        </div>
                        <div class="rounded-lg bg-white/10 p-4">
                            <div class="h-2 w-full rounded bg-white/15"></div>
                            <div class="mt-2 h-2 w-5/6 rounded bg-white/15"></div>
                            <div class="mt-2 h-2 w-4/6 rounded bg-white/15"></div>
                            <div class="mt-3 flex gap-2">
                                <span class="rounded bg-blue-500/40 px-2 py-0.5 text-[10px] text-blue-100"><?= __('home.mock_online') ?></span>
                                <span class="rounded bg-emerald-500/30 px-2 py-0.5 text-[10px] text-emerald-100"><?= __('home.mock_portal') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="rounded-lg bg-white/10 p-3"><div class="text-[10px] text-slate-300"><?= __('home.mock_attendance_today') ?></div><div class="mt-1 h-2 w-4/5 rounded bg-white/15"></div><div class="mt-1 h-2 w-3/5 rounded bg-white/15"></div></div>
                        <div class="rounded-lg bg-white/10 p-3"><div class="text-[10px] text-slate-300"><?= __('home.mock_events') ?></div><div class="mt-1 h-2 w-4/5 rounded bg-white/15"></div><div class="mt-1 h-2 w-3/5 rounded bg-white/15"></div></div>
                        <div class="rounded-lg bg-white/10 p-3"><div class="text-[10px] text-slate-300"><?= __('home.mock_messages') ?></div><div class="mt-1 h-2 w-4/5 rounded bg-white/15"></div><div class="mt-1 h-2 w-3/5 rounded bg-white/15"></div></div>
                    </div>
                </div>
            </div>
            <!-- Stats counters -->
            <div class="mt-5 grid grid-cols-3 gap-4 text-center">
                <div><div class="text-3xl font-extrabold text-white"><span class="esk-count" data-count="50" data-suffix="+">0</span></div><div class="text-xs text-slate-300 mt-1"><?= __('home.stats.1') ?></div></div>
                <div><div class="text-3xl font-extrabold text-white"><span class="esk-count" data-count="3" data-suffix="-in-1">0</span></div><div class="text-xs text-slate-300 mt-1"><?= __('home.stats.2') ?></div></div>
                <div><div class="text-3xl font-extrabold text-white"><span class="esk-count" data-count="24" data-suffix="/7">0</span></div><div class="text-xs text-slate-300 mt-1"><?= __('home.stats.3') ?></div></div>
            </div>
        </div>
    </div>
</section>

<!-- Trust strip -->
<section class="border-b border-slate-200 bg-white">
    <div class="max-w-7xl mx-auto px-4 py-6 flex flex-wrap items-center justify-center gap-x-10 gap-y-3 text-sm text-slate-500">
        <span class="inline-flex items-center gap-2"><span class="text-green-600 font-bold">✓</span> <?= __('home.trust.1') ?></span>
        <span class="inline-flex items-center gap-2"><span class="text-green-600 font-bold">✓</span> <?= __('home.trust.2') ?></span>
        <span class="inline-flex items-center gap-2"><span class="text-green-600 font-bold">✓</span> <?= __('home.trust.3') ?></span>
        <span class="inline-flex items-center gap-2"><span class="text-green-600 font-bold">✓</span> <?= __('home.trust.4') ?></span>
    </div>
</section>

<!-- Products -->
<section class="max-w-7xl mx-auto px-4 py-20">
    <div class="text-center mb-12">
        <span class="text-xs uppercase tracking-widest text-blue-600 font-semibold"><?= __('home.products_tag') ?></span>
        <h2 class="text-3xl md:text-4xl font-extrabold mt-3"><?= __('home.products_heading') ?></h2>
        <p class="text-slate-500 mt-3 max-w-2xl mx-auto"><?= __('home.products_sub') ?></p>
    </div>
    <div class="grid md:grid-cols-3 gap-8">
        <div class="bg-white rounded-3xl border border-slate-200 p-8 esk-card-hover flex flex-col">
            <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?= __('home.products.app_tag') ?></div>
            <h3 class="text-2xl font-bold mt-2"><?= __('home.products.app_name') ?></h3>
            <p class="text-slate-500 mt-3 text-sm leading-relaxed flex-1"><?= __('home.products.app_desc') ?></p>
            <div class="mt-5 space-y-2 text-sm text-slate-600">
                <p class="esk-check"><?= __('product.app_f1') ?></p>
                <p class="esk-check"><?= __('product.app_f3') ?></p>
                <p class="esk-check"><?= __('product.app_f4') ?></p>
                <p class="esk-check"><?= __('product.app_f8') ?></p>
            </div>
            <div class="mt-6"><a href="/products/app" class="inline-block text-blue-600 font-semibold"><?= __('home.products.app_cta') ?> →</a></div>
        </div>
        <div class="bg-white rounded-3xl border-2 border-blue-600 p-8 esk-card-hover flex flex-col shadow-xl shadow-blue-600/10 relative">
            <span class="absolute -top-3 left-8 bg-blue-600 text-white text-xs font-semibold px-3 py-1 rounded-full"><?= __('home.products.recommended') ?></span>
            <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?= __('home.products.php_tag') ?></div>
            <h3 class="text-2xl font-bold mt-2"><?= __('home.products.php_name') ?></h3>
            <p class="text-slate-500 mt-3 text-sm leading-relaxed flex-1"><?= __('home.products.php_desc') ?></p>
            <div class="mt-5 space-y-2 text-sm text-slate-600">
                <p class="esk-check"><?= __('product.php_f1') ?></p>
                <p class="esk-check"><?= __('product.php_f2') ?></p>
                <p class="esk-check"><?= __('product.php_f3') ?></p>
                <p class="esk-check"><?= __('product.php_f6') ?></p>
            </div>
            <div class="mt-6"><a href="/products/php" class="inline-block text-blue-600 font-semibold"><?= __('home.products.php_cta') ?> →</a></div>
        </div>
        <div class="bg-white rounded-3xl border border-slate-200 p-8 esk-card-hover flex flex-col">
            <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?= __('home.products.theme_tag') ?></div>
            <h3 class="text-2xl font-bold mt-2"><?= __('home.products.theme_name') ?></h3>
            <p class="text-slate-500 mt-3 text-sm leading-relaxed flex-1"><?= __('home.products.theme_desc') ?></p>
            <div class="mt-5 space-y-2 text-sm text-slate-600">
                <p class="esk-check"><?= __('product.theme_f1') ?></p>
                <p class="esk-check"><?= __('product.theme_f2') ?></p>
                <p class="esk-check"><?= __('product.theme_f4') ?></p>
                <p class="esk-check"><?= __('product.theme_f5') ?></p>
            </div>
            <div class="mt-6"><a href="/products/theme" class="inline-block text-blue-600 font-semibold"><?= __('home.products.theme_cta') ?> →</a></div>
        </div>
    </div>
</section>

<!-- How it works -->
<section class="bg-slate-50 border-y border-slate-200 py-20">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
            <span class="text-xs uppercase tracking-widest text-blue-600 font-semibold"><?= __('home.how_tag') ?></span>
            <h2 class="text-3xl md:text-4xl font-extrabold mt-3"><?= __('home.how_title') ?></h2>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-7 text-center esk-card-hover">
                <div class="h-12 w-12 mx-auto rounded-full bg-blue-600 text-white font-extrabold flex items-center justify-center text-lg">1</div>
                <h3 class="text-lg font-bold mt-4"><?= __('home.how_1t') ?></h3>
                <p class="text-sm text-slate-500 mt-2 leading-relaxed"><?= __('home.how_1d') ?></p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-7 text-center esk-card-hover">
                <div class="h-12 w-12 mx-auto rounded-full bg-blue-600 text-white font-extrabold flex items-center justify-center text-lg">2</div>
                <h3 class="text-lg font-bold mt-4"><?= __('home.how_2t') ?></h3>
                <p class="text-sm text-slate-500 mt-2 leading-relaxed"><?= __('home.how_2d') ?></p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-7 text-center esk-card-hover">
                <div class="h-12 w-12 mx-auto rounded-full bg-blue-600 text-white font-extrabold flex items-center justify-center text-lg">3</div>
                <h3 class="text-lg font-bold mt-4"><?= __('home.how_3t') ?></h3>
                <p class="text-sm text-slate-500 mt-2 leading-relaxed"><?= __('home.how_3d') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Features / why -->
<section class="bg-white border-y border-slate-200 py-20">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
            <span class="text-xs uppercase tracking-widest text-blue-600 font-semibold"><?= __('home.features_tag') ?></span>
            <h2 class="text-3xl md:text-4xl font-extrabold mt-3"><?= __('home.features_heading') ?></h2>
            <p class="text-slate-500 mt-3 max-w-2xl mx-auto"><?= __('home.features_sub') ?></p>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="rounded-2xl border border-slate-200 p-7">
                <div class="text-blue-600 font-bold mb-2"><?= __('features.academic') ?></div>
                <p class="text-sm text-slate-600"><?= __('features.academic_desc') ?></p>
            </div>
            <div class="rounded-2xl border border-slate-200 p-7">
                <div class="text-blue-600 font-bold mb-2"><?= __('features.students') ?></div>
                <p class="text-sm text-slate-600"><?= __('features.students_desc') ?></p>
            </div>
            <div class="rounded-2xl border border-slate-200 p-7">
                <div class="text-blue-600 font-bold mb-2"><?= __('features.attendance') ?></div>
                <p class="text-sm text-slate-600"><?= __('features.attendance_desc') ?></p>
            </div>
            <div class="rounded-2xl border border-slate-200 p-7">
                <div class="text-blue-600 font-bold mb-2"><?= __('features.finance') ?></div>
                <p class="text-sm text-slate-600"><?= __('features.finance_desc') ?></p>
            </div>
            <div class="rounded-2xl border border-slate-200 p-7">
                <div class="text-blue-600 font-bold mb-2"><?= __('features.comms') ?></div>
                <p class="text-sm text-slate-600"><?= __('features.comms_desc') ?></p>
            </div>
            <div class="rounded-2xl border border-slate-200 p-7">
                <div class="text-blue-600 font-bold mb-2"><?= __('features.api') ?></div>
                <p class="text-sm text-slate-600"><?= __('features.api_desc') ?></p>
            </div>
        </div>
        <div class="text-center mt-10"><a href="/features" class="esk-chip inline-block border border-blue-300 text-blue-600 px-6 py-2.5 rounded-xl font-semibold"><?= __('home.features_all') ?> →</a></div>
    </div>
</section>

<!-- Who it's for -->
<section class="max-w-7xl mx-auto px-4 py-20">
    <div class="text-center mb-12">
        <span class="text-xs uppercase tracking-widest text-blue-600 font-semibold"><?= __('home.roles_tag') ?></span>
        <h2 class="text-3xl md:text-4xl font-extrabold mt-3"><?= __('home.roles_title') ?></h2>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-7 esk-card-hover">
            <div class="h-11 w-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            </div>
            <h3 class="font-bold mt-4"><?= __('home.role_leader_t') ?></h3>
            <p class="text-sm text-slate-500 mt-2 leading-relaxed"><?= __('home.role_leader_d') ?></p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-7 esk-card-hover">
            <div class="h-11 w-11 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1A2 2 0 1 1 7.1 4l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.6V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.6 1Z"/></svg>
            </div>
            <h3 class="font-bold mt-4"><?= __('home.role_admin_t') ?></h3>
            <p class="text-sm text-slate-500 mt-2 leading-relaxed"><?= __('home.role_admin_d') ?></p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-7 esk-card-hover">
            <div class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>
            </div>
            <h3 class="font-bold mt-4"><?= __('home.role_teacher_t') ?></h3>
            <p class="text-sm text-slate-500 mt-2 leading-relaxed"><?= __('home.role_teacher_d') ?></p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-7 esk-card-hover">
            <div class="h-11 w-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.5-1.5 3-3.2 3-5.5A4.5 4.5 0 1 0 15.5 4 4.5 4.5 0 1 0 8.5 8.5c0 2.3 1.5 4 3 5.5-1.5 1.5-3 3.2-3 5.5A4.5 4.5 0 1 0 14 24a4.5 4.5 0 0 0 4.5-4.5c0-2.3-1.5-4-3-5.5-1.5 1.5-3 3.2-3 5.5A4.5 4.5 0 1 0 15.8 24"/></svg>
            </div>
            <h3 class="font-bold mt-4"><?= __('home.role_parent_t') ?></h3>
            <p class="text-sm text-slate-500 mt-2 leading-relaxed"><?= __('home.role_parent_d') ?></p>
        </div>
    </div>
</section>

<!-- Pricing preview -->
<section class="max-w-7xl mx-auto px-4 py-20">
    <div class="text-center mb-12">
        <span class="text-xs uppercase tracking-widest text-blue-600 font-semibold"><?= __('home.pricing_tag') ?></span>
        <h2 class="text-3xl md:text-4xl font-extrabold mt-3"><?= __('home.pricing_heading') ?></h2>
        <p class="text-slate-500 mt-3 max-w-2xl mx-auto"><?= __('home.pricing_sub') ?></p>
    </div>
    <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
        <?php $examples = [
            ['plans' => $appPlans ?? [], 'product' => __('pricing.app_section'), 'key' => 'app'],
            ['plans' => $phpPlans ?? [], 'product' => __('pricing.php_section'), 'key' => 'php'],
            ['plans' => $themePlans ?? [], 'product' => __('pricing.theme_section'), 'key' => 'theme'],
        ]; ?>
        <?php foreach ($examples as $ex): ?>
            <?php if (empty($ex['plans'])) continue; ?>
            <?php $best = null; foreach ($ex['plans'] as $p) { if ($best === null || (float) $p['price'] > (float) $best['price']) $best = $p; } ?>
            <div class="bg-white rounded-3xl border border-slate-200 p-8 esk-card-hover flex flex-col text-center">
                <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?= htmlspecialchars($ex['product']) ?></div>
                <div class="mt-3 text-5xl font-extrabold text-blue-700">$<?= number_format((float) $best['price'], 2) ?></div>
                <div class="text-xs text-slate-400 uppercase tracking-wide mt-1"><?= htmlspecialchars($best['period']) ?></div>
                <p class="mt-4 text-sm text-slate-500 flex-1"><?= htmlspecialchars((string) ($best['description'] ?? '')) ?></p>
                <a href="/checkout?plan=<?= (int) $best['id'] ?>" class="mt-6 block bg-slate-900 hover:bg-blue-600 text-white py-3 rounded-xl font-semibold"><?= __('home.pricing_buy') ?></a>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="text-center text-slate-400 text-sm mt-8"><a href="/pricing" class="text-blue-600 font-semibold"><?= __('home.pricing_all') ?> →</a></p>
</section>

<!-- Payments & integrations -->
<section class="bg-white border-y border-slate-200 py-20">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
            <span class="text-xs uppercase tracking-widest text-blue-600 font-semibold"><?= __('home.pay_tag') ?></span>
            <h2 class="text-3xl md:text-4xl font-extrabold mt-3"><?= __('home.pay_title') ?></h2>
            <p class="text-slate-500 mt-3 max-w-2xl mx-auto"><?= __('home.pay_sub') ?></p>
        </div>
        <div class="flex flex-wrap justify-center gap-3 max-w-3xl mx-auto">
            <?php foreach (['pay_1', 'pay_2', 'pay_3', 'pay_4', 'pay_5', 'pay_6'] as $k): ?>
                <span class="inline-flex items-center gap-2 border border-slate-200 bg-slate-50 rounded-full px-5 py-2.5 text-sm font-semibold text-slate-700">
                    <svg viewBox="0 0 24 24" class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    <?= __('home.' . $k) ?>
                </span>
            <?php endforeach; ?>
        </div>
        <p class="text-center text-slate-500 text-sm mt-8 max-w-2xl mx-auto"><?= __('home.pay_sms') ?></p>
    </div>
</section>

<!-- Testimonials -->
<section class="bg-slate-900 text-white py-20">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
            <span class="text-xs uppercase tracking-widest text-blue-300 font-semibold"><?= __('home.testi_tag') ?></span>
            <h2 class="text-3xl md:text-4xl font-extrabold mt-3"><?= __('home.testi_heading') ?></h2>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white/5 border border-white/10 rounded-2xl p-7">
                <div class="text-blue-300">★★★★★</div>
                <p class="mt-4 text-slate-200 leading-relaxed">"<?= __('home.testi.1') ?>"</p>
                <div class="mt-5 text-sm text-slate-400">
                    <div class="text-white font-semibold"><?= __('home.testi.1a') ?></div>
                    <div class="text-xs text-slate-500 mt-0.5"><?= __('home.testi.1role') ?></div>
                </div>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-2xl p-7">
                <div class="text-blue-300">★★★★★</div>
                <p class="mt-4 text-slate-200 leading-relaxed">"<?= __('home.testi.2') ?>"</p>
                <div class="mt-5 text-sm text-slate-400">
                    <div class="text-white font-semibold"><?= __('home.testi.2a') ?></div>
                    <div class="text-xs text-slate-500 mt-0.5"><?= __('home.testi.2role') ?></div>
                </div>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-2xl p-7">
                <div class="text-blue-300">★★★★★</div>
                <p class="mt-4 text-slate-200 leading-relaxed">"<?= __('home.testi.3') ?>"</p>
                <div class="mt-5 text-sm text-slate-400">
                    <div class="text-white font-semibold"><?= __('home.testi.3a') ?></div>
                    <div class="text-xs text-slate-500 mt-0.5"><?= __('home.testi.3role') ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($recentPosts)): ?>
<section class="max-w-7xl mx-auto px-4 py-20 border-t border-slate-200">
    <div class="flex items-center justify-between mb-10">
        <div>
            <h2 class="text-3xl font-bold"><?= __('blog.title') ?></h2>
            <p class="text-slate-500 mt-2"><?= __('blog.sub') ?></p>
        </div>
        <a href="/blog" class="text-blue-600 font-semibold text-sm"><?= __('blog.all') ?> →</a>
    </div>
    <div class="grid md:grid-cols-3 gap-6">
        <?php foreach ($recentPosts as $post): ?>
            <a href="/blog/<?= htmlspecialchars($post['slug']) ?>" class="bg-white rounded-2xl border border-slate-200 esk-card-hover flex flex-col overflow-hidden">
                <?php if (!empty($post['featured_image'])): ?>
                    <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="" class="h-40 w-full object-cover">
                <?php else: ?>
                    <div class="h-40 bg-gradient-to-r from-slate-900 to-blue-700"></div>
                <?php endif; ?>
                <div class="p-6 flex-1">
                    <div class="text-xs text-slate-500 mb-2">
                        <?php if (!empty($post['category_name'])): ?><span class="text-blue-600 font-semibold"><?= htmlspecialchars($post['category_name']) ?></span> · <?php endif; ?>
                        <?= date('M j, Y', strtotime((string) $post['published_at'])) ?>
                    </div>
                    <h3 class="font-bold mb-2"><?= htmlspecialchars($post['title']) ?></h3>
                    <p class="text-sm text-slate-600"><?= htmlspecialchars((string) $post['excerpt']) ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Final CTA -->
<section class="esk-hero text-white">
    <div class="relative z-10 max-w-4xl mx-auto px-4 py-16 text-center">
        <h2 class="text-3xl md:text-4xl font-extrabold"><?= __('home.cta_heading') ?></h2>
        <p class="mt-4 text-slate-300 text-lg"><?= __('home.cta_sub') ?></p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="/register" class="esk-btn-primary bg-blue-600 hover:bg-blue-500 px-7 py-3.5 rounded-xl font-semibold"><?= __('home.cta_register') ?></a>
            <a href="/contact" class="border border-white/30 hover:border-white/60 bg-white/5 px-7 py-3.5 rounded-xl font-semibold"><?= __('home.cta_contact') ?></a>
        </div>
    </div>
</section>

<!-- Sticky mobile CTA -->
<div class="lg:hidden fixed bottom-0 inset-x-0 z-40 border-t border-slate-800 bg-slate-900/95 backdrop-blur px-4 py-3 flex items-center justify-between gap-3">
    <div class="text-white">
        <div class="text-sm font-bold"><?= $eskName ?></div>
        <div class="text-xs text-slate-400"><?= __('home.sticky_sub') ?></div>
    </div>
    <div class="flex gap-2 shrink-0">
        <a href="/pricing" class="border border-slate-600 text-slate-100 px-4 py-2 rounded-lg text-sm font-semibold"><?= __('home.cta_pricing') ?></a>
        <a href="/register" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-semibold"><?= __('home.cta_register') ?></a>
    </div>
</div>