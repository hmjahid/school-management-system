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