<?php
/**
 * `/products` — the canonical Product x Variant explainer.
 *
 * The matrix is rendered by the shared component rather than hand-rolled here,
 * so `/pricing`, `/compare` and `/choose` show the identical mental model.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §4.2
 */
$title = __('products_page.title');
$siteTitle = $title;
$seo = [
    'title'       => $title,
    'description' => __('products_page.sub'),
    'canonical'   => '/products',
    'type'        => 'website',
];
$cmsHero = !empty($cmsPage['heading']) || !empty($cmsPage['intro']);

$rows = (array) ($matrix['rows'] ?? []);
$variantColumns = (array) ($matrix['variants'] ?? []);

$hostingLabels = [
    'vps'       => 'VPS / own server',
    'shared'    => 'Shared hosting',
    'wordpress' => 'WordPress host',
];
?>

<?php if (! $cmsHero): ?>
<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-16 text-center">
        <span class="inline-block text-xs uppercase tracking-widest bg-blue-500/20 text-blue-200 px-3 py-1 rounded-full border border-blue-400/30 mb-6"><?= __('products_page.badge') ?></span>
        <h1 class="text-4xl md:text-5xl font-extrabold"><?= __('products_page.title') ?></h1>
        <p class="mt-4 text-slate-300 text-lg max-w-3xl mx-auto"><?= __('products_page.sub') ?></p>
    </div>
</section>
<?php endif; ?>

<?php \App\Core\View::partial('site.partials.cms_block', ['cmsPage' => $cmsPage ?? null]); ?>

<div class="max-w-7xl mx-auto px-4 py-16">
    <nav class="text-sm text-slate-400 mb-6" aria-label="Breadcrumb">
        <a href="/" class="hover:text-blue-600"><?= __('nav.home') ?></a>
        <span aria-hidden="true">/</span>
        <span class="text-slate-600"><?= htmlspecialchars((string) __('products_page.breadcrumb')) ?></span>
    </nav>

    <?php
    \App\Core\View::partial('partials.product_variant_matrix', [
        'matrix' => $matrix ?? [],
        'matrixMode' => 'full',
    ]);
    ?>

    <p class="text-sm text-slate-500 mt-4"><?= htmlspecialchars((string) __('products_page.not_offered_note')) ?></p>

    <!-- Side-by-side comparison of the four products, independent of variant. -->
    <section class="mt-16" aria-labelledby="products-compare-title">
        <h2 id="products-compare-title" class="text-2xl font-extrabold text-slate-900"><?= htmlspecialchars((string) __('products_page.compare_heading')) ?></h2>
        <p class="text-slate-500 mt-2 max-w-3xl"><?= htmlspecialchars((string) __('products_page.compare_sub')) ?></p>

        <div class="overflow-x-auto mt-6">
            <table class="w-full text-sm bg-white rounded-2xl border border-slate-200 overflow-hidden esk-table">
                <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wide">
                    <tr>
                        <th scope="col" class="px-5 py-4 text-left font-semibold"><?= htmlspecialchars((string) __('products_page.col_stack')) ?></th>
                        <?php foreach ($rows as $row): ?>
                            <th scope="col" class="px-5 py-4 text-left font-semibold">
                                <span class="esk-product-pill" style="--pill:<?= htmlspecialchars((string) $row['color']) ?>"><?= htmlspecialchars((string) $row['label']) ?></span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <th scope="row" class="px-5 py-4 text-left font-medium text-slate-700"><?= htmlspecialchars((string) __('products_page.col_stack')) ?></th>
                        <?php foreach ($rows as $row): ?>
                            <td class="px-5 py-4 text-slate-600"><?= htmlspecialchars((string) $row['stack']) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <th scope="row" class="px-5 py-4 text-left font-medium text-slate-700"><?= htmlspecialchars((string) __('products_page.col_hosting')) ?></th>
                        <?php foreach ($rows as $row): ?>
                            <td class="px-5 py-4 text-slate-600"><?= htmlspecialchars((string) ($hostingLabels[(string) $row['requires']] ?? '—')) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <th scope="row" class="px-5 py-4 text-left font-medium text-slate-700"><?= htmlspecialchars((string) __('variants.label')) ?></th>
                        <?php foreach ($rows as $row): ?>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <?php foreach ($variantColumns as $column): ?>
                                        <?php $cell = (array) (($row['cells'] ?? [])[$column['code']] ?? []); ?>
                                        <?php if ((bool) ($cell['offered'] ?? false)): ?>
                                            <a href="<?= htmlspecialchars((string) ($cell['href'] ?? '')) ?>" class="esk-variant-chip esk-variant-chip--<?= htmlspecialchars((string) $column['code']) ?> border px-2 py-0.5 text-xs font-semibold text-slate-600"><?= htmlspecialchars((string) $column['short']) ?></a>
                                        <?php else: ?>
                                            <?php /* Not offered is a state, not a gap: name it and
                                                     offer the request path. A struck-through chip with
                                                     only a title tooltip is invisible on touch and
                                                     to screen readers. */ ?>
                                            <span class="inline-flex items-center gap-1 text-xs text-slate-400">
                                                <span class="esk-variant-chip esk-variant-chip--<?= htmlspecialchars((string) $column['code']) ?> border px-2 py-0.5 font-semibold line-through" aria-hidden="true"><?= htmlspecialchars((string) $column['short']) ?></span>
                                                <span><?= htmlspecialchars((string) __('matrix.cell_empty')) ?></span>
                                                <a href="<?= htmlspecialchars((string) ($cell['href'] ?? '')) ?>" class="text-blue-600 font-semibold hover:underline"><?= htmlspecialchars((string) __('matrix.cell_request')) ?></a>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <th scope="row" class="px-5 py-4 text-left font-medium text-slate-700"><?= htmlspecialchars((string) __('products_page.cta_all')) ?></th>
                        <?php foreach ($rows as $row): ?>
                            <td class="px-5 py-4">
                                <a href="<?= htmlspecialchars((string) $row['page']) ?>" class="text-blue-600 font-semibold hover:underline"><?= htmlspecialchars((string) __('matrix.cell_view')) ?> →</a>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-16 rounded-3xl bg-slate-50 border border-slate-200 p-8 md:p-10 text-center">
        <h2 class="text-2xl font-bold"><?= htmlspecialchars((string) __('products_page.help')) ?></h2>
        <p class="text-slate-600 mt-2 max-w-2xl mx-auto"><?= htmlspecialchars((string) __('products_page.help_text')) ?></p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="/choose" class="esk-btn-primary inline-block bg-blue-600 hover:bg-blue-500 text-white px-8 py-3.5 rounded-xl font-semibold"><?= htmlspecialchars((string) __('products_page.help_cta')) ?></a>
            <a href="/pricing" class="esk-chip border border-blue-300 text-blue-600 px-8 py-3.5 rounded-xl font-semibold inline-block"><?= htmlspecialchars((string) __('nav.pricing')) ?></a>
        </div>
    </div>
</div>

<?php \App\Core\View::partial('site.partials.cta_band'); ?>
