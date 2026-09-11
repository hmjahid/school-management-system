<?php $siteTitle = __('brand.name') . ' — ' . __('brand.tagline'); ?>

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
            </div>
            <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-300">
                <li>▲ <?= __('home.trust.1') ?></li>
                <li>⚡ <?= __('home.trust.2') ?></li>
                <li>✓ <?= __('home.trust.3') ?></li>
            </ul>
        </div>

        <div class="mt-12 lg:mt-0 bg-white/10 border border-white/15 rounded-2xl p-8 text-slate-100 backdrop-blur">
            <div class="flex items-center justify-between mb-6">
                <div class="font-bold text-white text-lg"><?= __('home.why_title') ?></div>
                <span class="text-xs text-blue-200 bg-blue-500/20 px-2.5 py-1 rounded-full"><?= __('home.why_pro_head') ?></span>
            </div>
            <ul class="space-y-4 text-sm">
                <?php foreach (['1','2','3','4','5'] as $i): ?>
                    <li class="flex gap-3"><span class="text-blue-300 shrink-0 mt-0.5">✓</span><span><?= __('home.why.' . $i) ?></span></li>
                <?php endforeach; ?>
            </ul>
            <div class="mt-8 grid grid-cols-3 gap-4 border-t border-white/10 pt-6 text-center">
                <div><div class="text-3xl font-extrabold text-white">50+</div><div class="text-xs text-slate-300 mt-1"><?= __('home.stats.1') ?></div></div>
                <div><div class="text-3xl font-extrabold text-white">3-in-1</div><div class="text-xs text-slate-300 mt-1"><?= __('home.stats.2') ?></div></div>
                <div><div class="text-3xl font-extrabold text-white">24/7</div><div class="text-xs text-slate-300 mt-1"><?= __('home.stats.3') ?></div></div>
            </div>
        </div>
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
                <div class="mt-5 text-sm text-slate-400">— <?= __('home.testi.1a') ?></div>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-2xl p-7">
                <div class="text-blue-300">★★★★★</div>
                <p class="mt-4 text-slate-200 leading-relaxed">"<?= __('home.testi.2') ?>"</p>
                <div class="mt-5 text-sm text-slate-400">— <?= __('home.testi.2a') ?></div>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-2xl p-7">
                <div class="text-blue-300">★★★★★</div>
                <p class="mt-4 text-slate-200 leading-relaxed">"<?= __('home.testi.3') ?>"</p>
                <div class="mt-5 text-sm text-slate-400">— <?= __('home.testi.3a') ?></div>
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