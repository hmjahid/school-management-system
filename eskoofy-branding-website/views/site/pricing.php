<?php $title = __('pricing.title'); $siteTitle = $title; ?>

<?php
/**
 * Pricing is presented as one row per **product** with a market-build toggle,
 * not as three hand-written product sections. The BDT/INT figures both come from
 * the same USD-canonical `plans` prices via `ProductMatrix`, so the toggle can
 * never show two different price lists.
 */
$isBd = \App\Gateways\GatewayFactory::isBdCountry(\App\Core\Auth::user()['country'] ?? null);
$activeVariant = $isBd ? \App\Services\VariantResolver::BD : \App\Services\VariantResolver::INT;
$rate = \App\Services\VariantResolver::rate();
$cur  = \App\Services\VariantResolver::currencyCode($activeVariant);

/**
 * Format one USD-canonical plan price in a given market build's currency.
 * Taka gets no decimals, dollars get cents — the same rule everywhere.
 */
$fmtVariant = static function (float $usd, string $variant): string {
    $display = \App\Services\VariantResolver::displayAmount($usd, $variant);
    $places = $display['currency'] === 'BDT' ? 0 : 2;

    return (string) $display['symbol'] . number_format((float) $display['amount'], $places);
};

$planGroups = [
    'app'   => $appPlans   ?? [],
    'php'   => $phpPlans   ?? [],
    'theme' => $themePlans ?? [],
    'node'  => $nodePlans  ?? [],
];
$sectionMeta = [
    'app'   => ['heading' => __('pricing.app_section'),   'note' => __('pricing.app_note')],
    'php'   => ['heading' => __('pricing.php_section'),   'note' => __('pricing.php_note'), 'popular' => true],
    'theme' => ['heading' => __('pricing.theme_section'), 'note' => __('pricing.theme_note')],
    'node'  => ['heading' => __('pricing.node_section'),  'note' => __('pricing.node_note')],
];

// Group each product's plans (monthly + yearly) into one card.
$build = function (array $plans, string $popular = ''): array {
    $monthly = $yearly = null;
    foreach ($plans as $p) {
        if (($p['period'] ?? '') === 'yearly') {
            $yearly = $p;
        } else {
            $monthly = $monthly ?: $p;
        }
    }
    return ['monthly' => $monthly, 'yearly' => $yearly, 'popular' => $popular];
};

$sections = [];
foreach (\App\Services\Catalog::keys() as $productCode) {
    $meta = $sectionMeta[$productCode] ?? null;
    if ($meta === null) {
        continue;
    }
    $card = $build((array) ($planGroups[$productCode] ?? []), $meta['popular'] ?? '');
    if ($card['monthly'] === null && $card['yearly'] === null) {
        continue;
    }
    $sections[] = [
        'code'    => $productCode,
        'heading' => $meta['heading'],
        'note'    => $meta['note'],
        'card'    => $card,
        'color'   => \App\Services\Catalog::color($productCode),
    ];
}

$features = function (array $plan): array {
    $features = $plan['features'] ?? null;
    if (is_string($features) && $features !== '') {
        return (array) json_decode((string) $features, true);
    }
    return is_array($features) ? $features : [];
};

$faqItems = [];
for ($i = 1; $i <= 5; $i++) {
    $faqItems[] = ['q' => __('pricing.faq.' . $i . 'q'), 'a' => __('pricing.faq.' . $i . 'a')];
}
$seo = [
    'title'       => __('pricing.title'),
    'description' => __('pricing.sub'),
    'canonical'   => '/pricing',
    'type'        => 'website',
    'schema'      => [\App\Services\Seo::faq($faqItems)],
];
?>

<?php $cmsHero = !empty($cmsPage['heading']) || !empty($cmsPage['intro']); ?>

<?php if (! $cmsHero): ?>
<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-16 text-center">
        <span class="inline-block text-xs uppercase tracking-widest bg-blue-500/20 text-blue-200 px-3 py-1 rounded-full border border-blue-400/30 mb-6"><?= __('pricing.badge') ?></span>
        <h1 class="text-4xl md:text-5xl font-extrabold"><?= __('pricing.title') ?></h1>
        <p class="mt-4 text-slate-300 text-lg max-w-3xl mx-auto"><?= __('pricing.sub') ?></p>
        <p class="mt-3 text-sm text-blue-200"><?= $isBd ? __('pricing.bd_currency_note') . ' (1 USD = ' . number_format($rate, 2) . ' BDT)' : __('pricing.int_currency_note') ?></p>
        <p class="mt-1 text-sm text-blue-200/80 max-w-3xl mx-auto"><?= __('pricing.rows_note') ?></p>

        <div class="mt-6 inline-flex items-center gap-3 bg-white/10 border border-white/15 rounded-full p-1.5" data-variant-toggle>
            <span class="px-3 text-xs uppercase tracking-wide text-blue-200/80"><?= __('pricing.variant_toggle') ?></span>
            <?php foreach (\App\Services\VariantResolver::all() as $variantCode): ?>
                <button type="button" data-variant="<?= htmlspecialchars($variantCode) ?>" class="px-5 py-2 rounded-full text-sm font-semibold <?= $variantCode === $activeVariant ? 'bg-white text-slate-900' : 'text-slate-200 hover:text-white' ?>"><?= htmlspecialchars(\App\Services\VariantResolver::label($variantCode)) ?></button>
            <?php endforeach; ?>
        </div>

        <div class="mt-8 inline-flex items-center gap-3 bg-white/10 border border-white/15 rounded-full p-1.5" data-billing-toggle>
            <button type="button" data-billing="monthly" class="px-5 py-2 rounded-full text-sm font-semibold bg-white text-slate-900">Monthly</button>
            <button type="button" data-billing="yearly" class="px-5 py-2 rounded-full text-sm font-semibold text-slate-200 hover:text-white">Yearly <span class="text-emerald-300 font-bold">· 2 months free</span></button>
        </div>
    </div>
</section>
<?php endif; ?>

<?php \App\Core\View::partial('site.partials.cms_block', ['cmsPage' => $cmsPage ?? null]); ?>

<section class="max-w-7xl mx-auto px-4 py-16">
    <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-6 items-stretch">
        <?php foreach ($sections as $sec): $card = $sec['card']; $mp = $card['monthly']; $yp = $card['yearly']; ?>
            <?php if (!$mp && !$yp): continue; endif; ?>
            <?php
            $monthlyUsd = (float) ($mp['price'] ?? 0);
            $yearlyUsd  = (float) ($yp['price'] ?? 0);
            $saveUsd    = $monthlyUsd * 12 - $yearlyUsd;
            $accent     = (string) $sec['color'];
            ?>
            <div class="relative flex flex-col">
                <div class="bg-white rounded-3xl border <?= $card['popular'] === 'popular' ? 'border-2 border-blue-600 shadow-xl shadow-blue-600/10' : 'border-slate-200' ?> p-6 flex flex-col flex-1 esk-card-hover" data-plan-card>
                    <?php if ($card['popular'] === 'popular'): ?>
                        <span class="absolute -top-3 left-6 bg-blue-600 text-white text-xs font-semibold px-3 py-1 rounded-full"><?= __('pricing.most_popular') ?></span>
                    <?php endif; ?>

                    <span class="esk-product-pill text-xs" style="--pill:<?= htmlspecialchars($accent) ?>"><?= htmlspecialchars((string) $sec['code'] === 'node' ? __('pricing.node_section') : (string) $sec['heading']) ?></span>
                    <p class="text-xs text-slate-500 mt-2 flex-1"><?= htmlspecialchars((string) $sec['note']) ?></p>

                    <div class="mt-4 flex items-end gap-1">
                        <span class="text-3xl font-extrabold" style="color:<?= htmlspecialchars($accent) ?>"
                              data-price-monthly="<?= htmlspecialchars($fmtVariant($monthlyUsd, $activeVariant)) ?>"
                              data-price-monthly-int="<?= htmlspecialchars($fmtVariant($monthlyUsd, 'int')) ?>"
                              data-price-monthly-bd="<?= htmlspecialchars($fmtVariant($monthlyUsd, 'bd')) ?>"
                              data-price-yearly="<?= htmlspecialchars($fmtVariant($yearlyUsd, $activeVariant)) ?>"
                              data-price-yearly-int="<?= htmlspecialchars($fmtVariant($yearlyUsd, 'int')) ?>"
                              data-price-yearly-bd="<?= htmlspecialchars($fmtVariant($yearlyUsd, 'bd')) ?>"><?= htmlspecialchars($fmtVariant($monthlyUsd, $activeVariant)) ?></span>
                        <span class="text-xs text-slate-400 uppercase pb-1">
                            / <?= __('pricing.per') ?> <span data-period-label>month</span>
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-400 uppercase tracking-wide mt-1" data-currency-label><?= $cur ?> · <span data-variant-name><?= htmlspecialchars(\App\Services\VariantResolver::label($activeVariant)) ?></span></div>
                    <?php if ($yp): ?>
                        <div class="text-xs font-semibold text-emerald-600 mt-1 hidden" data-yearly-save
                             data-save-int="<?= htmlspecialchars($fmtVariant($saveUsd, 'int')) ?>"
                             data-save-bd="<?= htmlspecialchars($fmtVariant($saveUsd, 'bd')) ?>"></div>
                    <?php endif; ?>

                    <?php $featList = $features($mp ?: $yp); ?>
                    <?php if (!empty($featList)): ?>
                        <ul class="mt-4 space-y-2 text-sm text-slate-600">
                            <?php foreach (array_slice($featList, 0, 4) as $f): ?>
                                <li class="esk-check"><?= htmlspecialchars((string) $f) ?></li>
                            <?php endforeach; ?>
                            <li class="esk-check"><?= __('home.trust.4') ?></li>
                        </ul>
                    <?php endif; ?>

                    <?php $planId = $mp['id'] ?? $yp['id'] ?? null; ?>
                    <?php if ($planId): ?>
                        <a href="/checkout?plan=<?= (int) $planId ?>" class="mt-5 block bg-slate-900 hover:bg-blue-600 text-white py-2.5 rounded-xl text-center text-sm font-semibold"><?= __('pricing.buy') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="text-xs text-slate-400 text-center mt-6"><?= htmlspecialchars((string) __('matrix.footnote_bdt', ['rate' => number_format($rate, 2)])) ?></p>

    <div class="mt-16 bg-white rounded-2xl border border-slate-200 p-8 max-w-3xl mx-auto text-center">
        <h3 class="text-xl font-bold"><?= __('pricing.compare_title') ?></h3>
        <p class="text-slate-500 mt-2 text-sm"><?= __('pricing.compare_sub') ?></p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <?php foreach ($sections as $sec): ?>
                <a href="<?= htmlspecialchars(\App\Services\Catalog::page($sec['code'])) ?>" class="esk-chip border border-blue-300 text-blue-600 px-6 py-2.5 rounded-xl font-semibold"><?= htmlspecialchars((string) $sec['heading']) ?></a>
            <?php endforeach; ?>
            <a href="/products" class="esk-chip border border-blue-300 text-blue-600 px-6 py-2.5 rounded-xl font-semibold"><?= __('products_page.breadcrumb') ?></a>
    </div>

    <div class="mt-16">
        <?php \App\Core\View::partial('site.partials.services_addon'); ?>
    </div>

    <div class="mt-16 max-w-3xl mx-auto">
        <h3 class="text-2xl font-bold text-center"><?= __('pricing.faq_heading') ?></h3>
        <p class="text-slate-500 text-center mt-1 mb-8"><?= __('pricing.faq_sub') ?></p>
        <div class="space-y-3">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <div class="bg-white rounded-2xl border border-slate-200" data-faq-item data-faq-open="<?= $i === 1 ? '1' : '0' ?>">
                    <button type="button" data-faq-toggle class="w-full flex items-center justify-between px-6 py-4 text-left font-semibold">
                        <span><?= __('pricing.faq.' . $i . 'q') ?></span>
                        <span class="text-slate-400" data-faq-caret><?= $i === 1 ? '▴' : '▾' ?></span>
                    </button>
                    <div data-faq-body class="<?= $i === 1 ? '' : 'hidden' ?> px-6 pb-4 text-sm text-slate-600 leading-relaxed"><?= __('pricing.faq.' . $i . 'a') ?></div>
                </div>
            <?php endfor; ?>
        </div>
        <p class="text-center text-sm text-slate-500 mt-8"><?= __('pricing.help_any') ?> <a href="/contact" class="text-blue-600 font-semibold"><?= __('pricing.help_link') ?></a></p>
    </div>
</section>

<script>
/**
 * Two independent toggles over one price list.
 *
 * `data-price-monthly-int` / `-bd` (and the yearly pair) hold the same USD plan
 * price formatted in each market build's currency, so switching builds is a
 * pure attribute swap — no fetch, and no chance of the page showing a second
 * price list that disagrees with the plans table.
 */
(function () {
    var state = { billing: 'monthly', variant: <?= json_encode($activeVariant) ?> };

    var variantNames = <?= json_encode([
        \App\Services\VariantResolver::BD  => \App\Services\VariantResolver::label('bd'),
        \App\Services\VariantResolver::INT => \App\Services\VariantResolver::label('int'),
    ]) ?>;
    var currencyNames = <?= json_encode([
        \App\Services\VariantResolver::BD  => \App\Services\VariantResolver::currencyCode('bd'),
        \App\Services\VariantResolver::INT => \App\Services\VariantResolver::currencyCode('int'),
    ]) ?>;

    function bindToggle(selector, attr, apply) {
        var root = document.querySelector(selector);
        if (!root) return;
        var buttons = root.querySelectorAll('[data-' + attr + ']');
        buttons.forEach(function (b) {
            b.addEventListener('click', function () {
                apply(b.getAttribute('data-' + attr));
            });
        });
    }

    function paintButtons(selector, attr, value) {
        document.querySelectorAll(selector + ' [data-' + attr + ']').forEach(function (b) {
            var on = b.getAttribute('data-' + attr) === value;
            b.classList.toggle('bg-white', on);
            b.classList.toggle('text-slate-900', on);
            b.classList.toggle('text-slate-200', !on);
        });
    }

    function apply() {
        paintButtons('[data-billing-toggle]', 'billing', state.billing);
        paintButtons('[data-variant-toggle]', 'variant', state.variant);

        document.querySelectorAll('[data-price-monthly]').forEach(function (el) {
            var yearly = state.billing === 'yearly';
            var val = yearly
                ? el.getAttribute('data-price-yearly-' + state.variant)
                : el.getAttribute('data-price-monthly-' + state.variant);
            el.textContent = val !== null ? val : (yearly ? el.getAttribute('data-price-yearly') : el.getAttribute('data-price-monthly'));

            var period = el.parentElement.querySelector('[data-period-label]');
            if (period) period.textContent = yearly ? 'year' : 'month';

            var save = el.closest('[data-plan-card]').querySelector('[data-yearly-save]');
            if (save) {
                save.classList.toggle('hidden', !yearly);
                save.textContent = yearly
                    ? 'Yearly — save 2 months (' + (save.getAttribute('data-save-' + state.variant) || '') + '/yr)'
                    : '';
            }
        });

        document.querySelectorAll('[data-currency-label]').forEach(function (el) {
            el.textContent = (currencyNames[state.variant] || '') + ' · ' + (variantNames[state.variant] || '');
        });
        document.querySelectorAll('[data-variant-name]').forEach(function (el) {
            el.textContent = variantNames[state.variant] || '';
        });
    }

    bindToggle('[data-billing-toggle]', 'billing', function (mode) { state.billing = mode; apply(); });
    bindToggle('[data-variant-toggle]', 'variant', function (mode) { state.variant = mode; apply(); });

    apply();
})();
</script>
