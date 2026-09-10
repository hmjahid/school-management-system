<?php $siteTitle = __('brand.name') . ' — ' . __('brand.tagline'); ?>

<section class="esk-hero text-white">
    <div class="max-w-7xl mx-auto px-4 py-20 md:py-28 grid md:grid-cols-2 gap-12 items-center">
        <div>
            <span class="inline-block text-xs uppercase tracking-widest bg-blue-500/20 text-blue-200 px-3 py-1 rounded-full mb-4"><?= __('home.badge') ?></span>
            <h1 class="text-4xl md:text-5xl font-extrabold leading-tight"><?= __('home.title') ?></h1>
            <p class="mt-4 text-slate-300 text-lg"><?= __('home.subtitle') ?></p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="/pricing" class="bg-blue-600 hover:bg-blue-500 px-6 py-3 rounded-lg font-semibold"><?= __('home.cta_pricing') ?></a>
                <a href="/register" class="border border-slate-500 hover:border-slate-300 px-6 py-3 rounded-lg font-semibold"><?= __('home.cta_register') ?></a>
            </div>
        </div>
        <div class="bg-white/5 border border-white/10 rounded-2xl p-8 text-slate-200">
            <div class="font-bold text-white mb-4"><?= __('home.why_title') ?></div>
            <ul class="space-y-3 text-sm">
                <li>• <?= __('home.why.1') ?></li>
                <li>• <?= __('home.why.2') ?></li>
                <li>• <?= __('home.why.3') ?></li>
                <li>• <?= __('home.why.4') ?></li>
                <li>• <?= __('home.why.5') ?></li>
            </ul>
        </div>
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 py-16">
    <div class="text-center mb-10">
        <h2 class="text-3xl font-bold"><?= __('home.products_heading') ?></h2>
        <p class="text-slate-500 mt-2"><?= __('home.products_sub') ?></p>
    </div>
    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl p-8 border border-slate-200 esk-card-hover">
            <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?= __('home.products.app_tag') ?></div>
            <h3 class="text-2xl font-bold mt-2"><?= __('home.products.app_name') ?></h3>
            <p class="text-slate-500 mt-2"><?= __('home.products.app_desc') ?></p>
            <a href="/products/app" class="mt-6 inline-block text-blue-600 font-semibold"><?= __('home.products.app_cta') ?> →</a>
        </div>
        <div class="bg-white rounded-2xl p-8 border border-slate-200 esk-card-hover">
            <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?= __('home.products.theme_tag') ?></div>
            <h3 class="text-2xl font-bold mt-2"><?= __('home.products.theme_name') ?></h3>
            <p class="text-slate-500 mt-2"><?= __('home.products.theme_desc') ?></p>
            <a href="/products/theme" class="mt-6 inline-block text-blue-600 font-semibold"><?= __('home.products.theme_cta') ?> →</a>
        </div>
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 py-16">
    <div class="text-center mb-10">
        <h2 class="text-3xl font-bold"><?= __('home.pricing_heading') ?></h2>
        <p class="text-slate-500 mt-2"><?= __('home.pricing_sub') ?></p>
    </div>
    <div class="grid md:grid-cols-3 gap-6">
        <?php $displayed = 0; ?>
        <?php foreach (array_merge($appPlans ?? [], $themePlans ?? []) as $plan): ?>
            <?php if ($displayed >= 3) continue; $displayed++; ?>
            <div class="bg-white rounded-2xl p-8 border border-slate-200 esk-card-hover text-center">
                <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?= htmlspecialchars(ucfirst($plan['product'])) ?></div>
                <h3 class="text-2xl font-bold mt-2"><?= htmlspecialchars($plan['name']) ?></h3>
                <p class="mt-1 text-slate-500 text-sm"><?= htmlspecialchars((string) ($plan['description'] ?? '')) ?></p>
                <div class="mt-4 text-4xl font-extrabold text-blue-700">$<?= number_format((float) $plan['price'], 2) ?></div>
                <div class="text-xs text-slate-400 uppercase tracking-wide mt-1"><?= htmlspecialchars($plan['period']) ?></div>
                <a href="/checkout?plan=<?= (int) $plan['id'] ?>" class="mt-6 block bg-slate-900 hover:bg-blue-600 text-white py-3 rounded-lg font-semibold"><?= __('home.pricing_buy') ?></a>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="text-center text-slate-400 text-sm mt-6"><a href="/pricing" class="text-blue-600"><?= __('home.pricing_all') ?> →</a></p>
</section>

<?php if (!empty($recentPosts)): ?>
<section class="max-w-7xl mx-auto px-4 py-16 border-t border-slate-200">
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

<section class="bg-white border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 py-16 grid md:grid-cols-3 gap-8 text-center text-sm">
        <div>
            <div class="text-3xl font-extrabold text-blue-700">50+</div>
            <div class="text-slate-500 mt-1"><?= __('home.stats.1') ?></div>
        </div>
        <div>
            <div class="text-3xl font-extrabold text-blue-700">3-in-1</div>
            <div class="text-slate-500 mt-1"><?= __('home.stats.2') ?></div>
        </div>
        <div>
            <div class="text-3xl font-extrabold text-blue-700">24/7</div>
            <div class="text-slate-500 mt-1"><?= __('home.stats.3') ?></div>
        </div>
    </div>
</section>