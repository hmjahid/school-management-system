<?php $title = __('node_page.title'); $siteTitle = $title; ?>

<?php
$seo = [
    'title'       => __('node_page.title'),
    'description' => __('node_page.sub'),
    'canonical'   => '/products/node',
    'type'        => 'product',
    'breadcrumbs' => [['name' => __('nav.home'), 'url' => '/'], ['name' => __('node_page.title'), 'url' => '/products/node']],
];
?>

<?php $cmsHero = !empty($cmsPage['heading']) || !empty($cmsPage['intro']); ?>

<!-- Hero -->
<?php if (! $cmsHero): ?>
<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-16 md:py-24">
        <div class="max-w-3xl">
            <span class="inline-block text-xs uppercase tracking-widest bg-blue-500/20 text-blue-200 px-3 py-1 rounded-full border border-blue-400/30"><?= __('node_page.badge') ?></span>
            <h1 class="text-4xl md:text-5xl font-extrabold mt-4 leading-tight"><?= __('node_page.title') ?></h1>
            <p class="mt-5 text-slate-300 text-lg leading-relaxed"><?= __('node_page.sub') ?></p>
            <p class="mt-4 text-sm text-blue-200/90 max-w-xl"><?= __('node_page.audience') ?></p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="/custom-order?product=node" class="esk-btn-primary bg-blue-600 hover:bg-blue-500 px-7 py-3.5 rounded-xl font-semibold"><?= __('node_page.cta') ?></a>
                <a href="/compare" class="border border-white/30 hover:border-white/60 bg-white/5 px-7 py-3.5 rounded-xl font-semibold"><?= __('nav.compare') ?></a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php \App\Core\View::partial('site.partials.cms_block', ['cmsPage' => $cmsPage ?? null]); ?>

<!-- When it fits -->
<section class="max-w-7xl mx-auto px-4 py-16 md:py-20">
    <div class="text-center mb-12">
        <span class="text-xs uppercase tracking-widest text-blue-600 font-semibold"><?= __('page.run_tag') ?></span>
        <h2 class="text-3xl md:text-4xl font-extrabold mt-3"><?= __('node_page.fit_title') ?></h2>
    </div>
    <div class="grid md:grid-cols-3 gap-6">
        <?php foreach ([['f1', '#0ea5e9'], ['f2', '#7c3aed'], ['f3', '#16a34a']] as [$key, $color]): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-7 esk-card-hover">
                <div class="h-11 w-11 rounded-xl flex items-center justify-center" style="background:<?= $color ?>15;color:<?= $color ?>">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <p class="mt-4 text-slate-700 font-medium leading-snug"><?= __('node_page.' . $key) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- How ordering works -->
<section class="bg-white border-y border-slate-200 py-16">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
            <span class="text-xs uppercase tracking-widest text-blue-600 font-semibold"><?= __('node_page.how_tag') ?></span>
            <h2 class="text-3xl font-extrabold mt-3"><?= __('node_page.how_title') ?></h2>
        </div>
        <div class="grid md:grid-cols-3 gap-6 max-w-4xl mx-auto">
            <?php foreach (['how_1', 'how_2', 'how_3'] as $i => $k): ?>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-7 text-center">
                    <div class="h-10 w-10 mx-auto rounded-full bg-blue-50 text-blue-600 font-extrabold flex items-center justify-center"><?= $i + 1 ?></div>
                    <p class="mt-4 font-semibold text-slate-800"><?= __('node_page.' . $k) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-10 text-center">
            <a href="/custom-order?product=node" class="inline-block bg-slate-900 hover:bg-blue-600 text-white px-8 py-3.5 rounded-xl font-semibold"><?= __('node_page.cta') ?></a>
            <p class="mt-3 text-sm text-slate-500"><?= __('node_page.sales_note') ?></p>
        </div>
    </div>
</section>

<?php \App\Core\View::partial('site.partials.services_addon'); ?>

<?php \App\Core\View::partial('site.partials.cta-band'); ?>