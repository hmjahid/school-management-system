<?php
// CMS block — renders the admin-managed hero (heading + intro) and/or the
// editable body content for a page row. `with_hero: false` renders only the
// content (used on pages whose bespoke hero should never be replaced).
if (!isset($cmsPage) || !is_array($cmsPage)) {
    return;
}

$withHero = $with_hero ?? true;
$heading = (string) ($cmsPage['heading'] ?? '');
$intro = (string) ($cmsPage['intro'] ?? '');
$content = (string) ($cmsPage['content'] ?? '');
$showHero = $withHero && ($heading !== '' || $intro !== '');
$showContent = $content !== '';

if (!$showHero && !$showContent) {
    return;
}
?>
<?php if ($showHero): ?>
<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-16 text-center">
        <?php if ($heading !== ''): ?>
            <h1 class="text-4xl md:text-5xl font-extrabold"><?= htmlspecialchars($heading) ?></h1>
        <?php endif; ?>
        <?php if ($intro !== ''): ?>
            <p class="mt-4 text-slate-300 text-lg max-w-3xl mx-auto"><?= htmlspecialchars($intro) ?></p>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
<?php if ($showContent): ?>
<section class="max-w-3xl mx-auto px-4 py-16">
    <div class="cms-content space-y-6 text-slate-600 leading-relaxed">
        <?= $content ?>
    </div>
</section>
<?php endif; ?>