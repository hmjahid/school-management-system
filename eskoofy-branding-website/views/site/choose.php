<?php
$title = __('choose.title');
$siteTitle = $title;
$seo = ['title' => __('choose.title'), 'description' => __('choose.sub'), 'canonical' => '/choose'];
$cmsHero = !empty($cmsPage['heading']) || !empty($cmsPage['intro']);
?>

<?php if (! $cmsHero): ?>
<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-16 text-center">
        <span class="inline-block text-xs uppercase tracking-widest bg-blue-500/20 text-blue-200 px-3 py-1 rounded-full border border-blue-400/30 mb-6"><?= __('choose.badge') ?></span>
        <h1 class="text-4xl md:text-5xl font-extrabold"><?= __('choose.title') ?></h1>
        <p class="mt-4 text-slate-300 text-lg max-w-3xl mx-auto"><?= __('choose.sub') ?></p>
    </div>
</section>
<?php endif; ?>

<?php \App\Core\View::partial('site.partials.cms_block', ['cmsPage' => $cmsPage ?? null]); ?>

<section class="max-w-3xl mx-auto px-4 py-16">
    <form method="post" action="/choose" class="bg-white rounded-3xl border border-slate-200 p-8 md:p-10 space-y-8">
        <?= csrf_field() ?>

        <?php foreach ([
            ['size', __('choose.q_size'), $sizes],
            ['comfort', __('choose.q_comfort'), $comforts],
            ['hosting', __('choose.q_hosting'), $hostings],
            ['stack', __('choose.q_stack'), $stacks],
            ['priority', __('choose.q_priority'), $priorities],
        ] as $i => [$key, $question, $options]): ?>
            <fieldset>
                <legend class="text-sm font-bold text-slate-900 mb-3">
                    <span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-white text-xs font-extrabold"><?= $i + 1 ?></span>
                    <?= $question ?>
                </legend>
                <div class="grid sm:grid-cols-2 gap-2">
                    <?php foreach ($options as $option): ?>
                        <label class="flex items-center gap-3 border border-slate-200 rounded-xl px-4 py-3 text-sm cursor-pointer hover:border-blue-400">
                            <input type="radio" name="<?= $key ?>" value="<?= htmlspecialchars($option) ?>" required class="accent-blue-600 shrink-0">
                            <span><?= __('choose.opt.' . $key . '.' . $option) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
        <?php endforeach; ?>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
            <p class="text-xs text-slate-400"><?= __('choose.privacy_note') ?></p>
            <button class="bg-slate-900 hover:bg-blue-600 text-white px-8 py-3.5 rounded-xl font-semibold"><?= __('choose.submit') ?></button>
        </div>
    </form>
</section>

<?php \App\Core\View::partial('site.partials.cta-band'); ?>