<?php $title = __('compare.title'); $siteTitle = $title; $seo = ['title' => __('compare.title'), 'description' => __('compare.sub'), 'canonical' => '/compare']; ?>
<?php $cmsHero = !empty($cmsPage['heading']) || !empty($cmsPage['intro']); ?>

<?php if (! $cmsHero): ?>
<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-16 text-center">
        <span class="inline-block text-xs uppercase tracking-widest bg-blue-500/20 text-blue-200 px-3 py-1 rounded-full border border-blue-400/30 mb-6"><?= __('compare.badge') ?></span>
        <h1 class="text-4xl md:text-5xl font-extrabold"><?= __('compare.title') ?></h1>
        <p class="mt-4 text-slate-300 text-lg max-w-3xl mx-auto"><?= __('compare.sub') ?></p>
    </div>
</section>
<?php endif; ?>

<?php \App\Core\View::partial('site.partials.cms_block', ['cmsPage' => $cmsPage ?? null]); ?>

<div class="max-w-7xl mx-auto px-4 py-16">
    <div class="overflow-x-auto">
        <table class="w-full text-sm bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wide">
                <tr>
                    <th class="px-5 py-4 text-left font-semibold"><?= __('compare.col_feature') ?></th>
                    <th class="px-5 py-4 text-left font-semibold text-blue-700">Eskoofy</th>
                    <th class="px-5 py-4 text-left font-semibold">openSIS</th>
                    <th class="px-5 py-4 text-left font-semibold">Fedena</th>
                    <th class="px-5 py-4 text-left font-semibold">PowerSchool</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach (['deploy', 'pricing', 'modules', 'fees', 'portal', 'multi', 'data', 'support', 'api', 'license'] as $row): ?>
                    <tr>
                        <td class="px-5 py-4 font-medium text-slate-700"><?= __('compare.' . $row) ?></td>
                        <td class="px-5 py-4"><span class="text-green-700 font-semibold"><?= __('compare.yes') ?></span> — <?= __('compare.' . $row . '_eskoofy') ?></td>
                        <td class="px-5 py-4 text-slate-600"><?= __('compare.' . $row . '_opensis') ?></td>
                        <td class="px-5 py-4 text-slate-600"><?= __('compare.' . $row . '_fedena') ?></td>
                        <td class="px-5 py-4 text-slate-600"><?= __('compare.' . $row . '_power') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-16 bg-slate-50 border border-slate-200 rounded-3xl p-8 md:p-10">
        <h2 class="text-2xl font-bold text-center mb-3"><?= __('compare.switch_heading') ?></h2>
        <p class="text-slate-500 text-center max-w-2xl mx-auto mb-8"><?= __('compare.switch_sub') ?></p>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <div class="text-2xl mb-2">🚚</div>
                <div class="font-bold mb-1"><?= __('compare.switch.1') ?></div>
                <p class="text-sm text-slate-600"><?= __('compare.switch.1d') ?></p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <div class="text-2xl mb-2">📦</div>
                <div class="font-bold mb-1"><?= __('compare.switch.2') ?></div>
                <p class="text-sm text-slate-600"><?= __('compare.switch.2d') ?></p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <div class="text-2xl mb-2">🛟</div>
                <div class="font-bold mb-1"><?= __('compare.switch.3') ?></div>
                <p class="text-sm text-slate-600"><?= __('compare.switch.3d') ?></p>
            </div>
        </div>
        <div class="text-center mt-10">
            <a href="/contact" class="inline-block bg-blue-600 hover:bg-blue-500 text-white px-8 py-3.5 rounded-xl font-semibold"><?= __('compare.cta') ?></a>
        </div>
    </div>
</div>

<?php \App\Core\View::partial('site.partials.cta_band'); ?>