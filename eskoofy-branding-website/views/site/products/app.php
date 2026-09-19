<?php $title = __('product.app_title'); $siteTitle = $title; ?>

<?php
$isBd = \App\Gateways\GatewayFactory::isBdCountry(\App\Core\Auth::user()['country'] ?? null);
$sym  = $isBd ? '৳' : '$';
$cur  = $isBd ? 'BDT' : 'USD';
$fmt  = fn (float $usd): float => $isBd ? \App\Gateways\GatewayFactory::toBdt($usd) : $usd;
$minPrice = 0.0;
foreach ($plans as $p) {
    $minPrice = $minPrice === 0.0 || (float) $p['price'] < $minPrice ? (float) $p['price'] : $minPrice;
}
$faqItems = [];
for ($i = 1; $i <= 5; $i++) {
    $faqItems[] = ['q' => __('product.faq.' . $i . 'q'), 'a' => __('product.faq.' . $i . 'a')];
}
$seo = [
    'title'       => __('app_page.title'),
    'description' => __('app_page.sub'),
    'canonical'   => '/products/app',
    'type'        => 'product',
    'schema'      => [
        \App\Services\Seo::product(['name' => __('product.app_title'), 'description' => __('product.app_sub'), 'url' => '/products/app', 'price' => number_format($minPrice, 2), 'currency' => 'USD']),
        \App\Services\Seo::faq($faqItems),
    ],
    'breadcrumbs' => [['name' => __('nav.home'), 'url' => '/'], ['name' => __('product.app_title'), 'url' => '/products/app']],
];
?>

<!-- Hero -->
<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-16 md:py-24">
        <div class="max-w-3xl">
            <span class="inline-block text-xs uppercase tracking-widest bg-blue-500/20 text-blue-200 px-3 py-1 rounded-full border border-blue-400/30"><?= __('pricing.app_section') ?></span>
            <h1 class="text-4xl md:text-5xl font-extrabold mt-4 leading-tight"><?= __('app_page.title') ?></h1>
            <p class="mt-5 text-slate-300 text-lg leading-relaxed"><?= __('app_page.sub') ?></p>
            <p class="mt-4 text-sm text-blue-200/90 max-w-xl"><?= __('app_page.audience') ?></p>
<div class="mt-8 flex flex-wrap gap-3">
                <a href="#pricing" class="esk-btn-primary bg-blue-600 hover:bg-blue-500 px-7 py-3.5 rounded-xl font-semibold"><?= __('pricing.most_popular') ?></a>
                <a href="/contact" class="border border-white/30 hover:border-white/60 bg-white/5 px-7 py-3.5 rounded-xl font-semibold"><?= __('page.cta_contact') ?></a>
            </div>
        </div>
    </div>
</section>

<!-- Benefits -->
<section class="max-w-7xl mx-auto px-4 py-16 md:py-20">
    <div class="text-center mb-12">
        <span class="text-xs uppercase tracking-widest text-blue-600 font-semibold"><?= __('page.benefits_tag') ?></span>
        <h2 class="text-3xl md:text-4xl font-extrabold mt-3"><?= __('app_page.benefits_title') ?></h2>
    </div>
    <div class="grid md:grid-cols-3 gap-6">
        <?php foreach ([['b1', '#2563eb'], ['b2', '#16a34a'], ['b3', '#7c3aed'], ['b4', '#ea580c'], ['b5', '#0ea5e9'], ['b6', '#db2777']] as [$key, $color]): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-7 esk-card-hover">
                <div class="h-11 w-11 rounded-xl flex items-center justify-center" style="background:<?= $color ?>15;color:<?= $color ?>">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <p class="mt-4 text-slate-700 font-medium leading-snug"><?= __('app_page.' . $key) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- How it runs -->
<section class="bg-white border-y border-slate-200 py-16">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
            <span class="text-xs uppercase tracking-widest text-blue-600 font-semibold"><?= __('page.run_tag') ?></span>
            <h2 class="text-3xl md:text-4xl font-extrabold mt-3"><?= __('app_page.run_title') ?></h2>
            <p class="text-slate-500 mt-3 max-w-2xl mx-auto"><?= __('app_page.run_sub') ?></p>
        </div>
        <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-8">
                <div class="text-blue-600 font-bold text-lg"><?= __('app_page.run_1t') ?></div>
                <p class="mt-3 text-slate-600 leading-relaxed text-sm"><?= __('app_page.run_1d') ?></p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-8">
                <div class="text-blue-600 font-bold text-lg"><?= __('app_page.run_2t') ?></div>
                <p class="mt-3 text-slate-600 leading-relaxed text-sm"><?= __('app_page.run_2d') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Pricing -->
<section id="pricing" class="max-w-7xl mx-auto px-4 py-16 grid md:grid-cols-2 gap-8 max-w-4xl">
    <?php foreach ($plans as $plan): ?>
        <?php
        $isYearly = ($plan['period'] ?? '') === 'yearly';
        $price = $fmt((float) $plan['price']);
        ?>
        <div class="bg-white rounded-3xl p-8 border <?= $isYearly ? 'border-2 border-blue-600 shadow-xl shadow-blue-600/10' : 'border-slate-200' ?> esk-card-hover flex flex-col relative">
            <?php if ($isYearly): ?>
                <span class="absolute -top-3 left-8 bg-blue-600 text-white text-xs font-semibold px-3 py-1 rounded-full"><?= __('pricing.most_popular') ?></span>
            <?php endif; ?>
            <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?= htmlspecialchars($plan['name']) ?> <?= __('product.license') ?></div>
            <h3 class="text-2xl font-bold mt-2"><?= htmlspecialchars($plan['description']) ?></h3>
            <div class="mt-5">
                <span class="text-4xl font-extrabold text-blue-700"><?= $sym ?><?= number_format($price, $isBd ? 0 : 2) ?></span>
                <span class="text-xs text-slate-400 uppercase"> / <?= __('pricing.per') ?> <?= htmlspecialchars($plan['period']) ?></span>
            </div>
            <div class="text-xs text-slate-400 uppercase tracking-wide mt-1"><?= $cur ?> · <?= $isYearly ? __('pricing.yearly_badge') : __('pricing.monthly_label') ?></div>
            <a href="/checkout?plan=<?= (int) $plan['id'] ?>" class="mt-6 block <?= $isYearly ? 'bg-blue-600 hover:bg-blue-500' : 'bg-slate-900 hover:bg-blue-600' ?> text-white py-3 rounded-xl text-center font-semibold"><?= __('pricing.buy') ?></a>
        </div>
    <?php endforeach; ?>
</section>

<!-- Getting started -->
<section class="max-w-7xl mx-auto px-4 pb-16">
    <div class="text-center mb-10">
        <span class="text-xs uppercase tracking-widest text-blue-600 font-semibold"><?= __('home.how_tag') ?></span>
        <h2 class="text-3xl font-extrabold mt-2"><?= __('app_page.start_title') ?></h2>
    </div>
    <div class="grid md:grid-cols-3 gap-6">
        <?php foreach (['start_1', 'start_2', 'start_3'] as $i => $k): ?>
            <div class="rounded-2xl border border-slate-200 bg-white p-7 text-center">
                <div class="h-10 w-10 mx-auto rounded-full bg-blue-50 text-blue-600 font-extrabold flex items-center justify-center"><?= $i + 1 ?></div>
                <p class="mt-4 font-semibold text-slate-800"><?= __('app_page.' . $k) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- FAQ -->
<section class="max-w-7xl mx-auto px-4 pb-16">
    <div class="rounded-3xl bg-slate-50 border border-slate-200 p-8 md:p-10">
        <h2 class="text-2xl font-bold mb-6"><?= __('product.faq_heading') ?></h2>
        <div class="space-y-4">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-6">
                    <div class="font-semibold"><?= __('product.faq.' . $i . 'q') ?></div>
                    <p class="text-sm text-slate-600 mt-2 leading-relaxed"><?= __('product.faq.' . $i . 'a') ?></p>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<?php \App\Core\View::partial('site.partials.services_addon'); ?>

<?php \App\Core\View::partial('site.partials.cta-band'); ?>