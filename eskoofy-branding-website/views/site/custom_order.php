<?php
$title = __('custom_order.title');
$siteTitle = $title;
$seo = ['title' => __('custom_order.title'), 'description' => __('custom_order.sub'), 'canonical' => '/custom-order'];
$cmsHero = !empty($cmsPage['heading']) || !empty($cmsPage['intro']);
?>

<?php if (! $cmsHero): ?>
<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-16 text-center">
        <span class="inline-block text-xs uppercase tracking-widest bg-blue-500/20 text-blue-200 px-3 py-1 rounded-full border border-blue-400/30 mb-6"><?= __('custom_order.badge') ?></span>
        <h1 class="text-4xl md:text-5xl font-extrabold"><?= __('custom_order.title') ?></h1>
        <p class="mt-4 text-slate-300 text-lg max-w-3xl mx-auto"><?= __('custom_order.sub') ?></p>
    </div>
</section>
<?php endif; ?>

<?php \App\Core\View::partial('site.partials.cms_block', ['cmsPage' => $cmsPage ?? null]); ?>

<section class="max-w-3xl mx-auto px-4 py-16">
    <form method="post" action="/custom-order" class="bg-white rounded-3xl border border-slate-200 p-8 md:p-10 space-y-6">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1"><?= __('custom_order.your_name') ?></label>
                <input name="name" value="<?= old('name') ?>" required maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2.5">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1"><?= __('custom_order.email') ?></label>
                <input type="email" name="email" value="<?= old('email') ?>" required maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2.5">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1"><?= __('custom_order.phone') ?> <span class="text-xs text-slate-400">(<?= __('custom_order.optional') ?>)</span></label>
                <input name="phone" value="<?= old('phone') ?>" maxlength="64" class="w-full border border-slate-300 rounded-lg px-4 py-2.5">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1"><?= __('custom_order.product') ?></label>
                <select name="product" required class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm">
                    <?php foreach ($products as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= ($_GET['product'] ?? $product ?? '') === $p ? 'selected' : '' ?>><?= __('custom_order.product_' . $p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1"><?= __('custom_order.request_type') ?></label>
                <select name="request_type" required class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm">
                    <?php foreach ($types as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= old('request_type') === $t || $t === 'custom_development' ? 'selected' : '' ?>><?= __('custom_order.type_' . $t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1"><?= __('custom_order.budget') ?> <span class="text-xs text-slate-400">(<?= __('custom_order.optional') ?>)</span></label>
                <select name="budget" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm">
                    <option value="">—</option>
                    <?php foreach ($ranges as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>" <?= old('budget') === $r ? 'selected' : '' ?>><?= __('custom_order.range_' . $r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><?= __('custom_order.subject') ?> <span class="text-xs text-slate-400">(<?= __('custom_order.optional') ?>)</span></label>
            <input name="subject" value="<?= old('subject') ?>" maxlength="191" placeholder="<?= __('custom_order.subject_ph') ?>" class="w-full border border-slate-300 rounded-lg px-4 py-2.5">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><?= __('custom_order.details') ?></label>
            <textarea name="details" rows="7" required maxlength="4000" placeholder="<?= __('custom_order.details_ph') ?>" class="w-full border border-slate-300 rounded-lg px-4 py-2.5"><?= old('details') ?></textarea>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><?= __('custom_order.timeline') ?> <span class="text-xs text-slate-400">(<?= __('custom_order.optional') ?>)</span></label>
            <select name="timeline" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm">
                <option value="">—</option>
                <?php foreach ($timelines as $t): ?>
                    <option value="<?= htmlspecialchars($t) ?>" <?= old('timeline') === $t ? 'selected' : '' ?>><?= __('custom_order.timeline_' . $t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-slate-400"><?= __('custom_order.privacy_note') ?></p>
            <button class="bg-slate-900 hover:bg-blue-600 text-white px-8 py-3.5 rounded-xl font-semibold"><?= __('custom_order.submit') ?></button>
        </div>
    </form>
</section>

<?php \App\Core\View::partial('site.partials.cta-band'); ?>