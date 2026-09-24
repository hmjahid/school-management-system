<?php $title = __('pricing.title'); $siteTitle = $title; ?>

<?php
// Currency display: BD visitors get BDT (derived at rate); others get USD.
$isBd = \App\Gateways\GatewayFactory::isBdCountry(\App\Core\Auth::user()['country'] ?? null);
$rate = \App\Gateways\GatewayFactory::bdtRate();
$sym  = $isBd ? '৳' : '$';
$cur  = $isBd ? 'BDT' : 'USD';
$fmt  = function (float $usd) use ($isBd): float {
    return $isBd ? \App\Gateways\GatewayFactory::toBdt($usd) : $usd;
};
$num = function (float $usd) use ($isBd): string {
    return number_format($isBd ? \App\Gateways\GatewayFactory::toBdt($usd) : $usd, $isBd ? 0 : 2);
};

// Group each product’s plans (monthly + yearly) into one card.
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

$sections = [
    ['heading' => __('pricing.app_section'), 'note' => __('pricing.app_note'), 'card' => $build($appPlans)],
    ['heading' => __('pricing.php_section'), 'note' => __('pricing.php_note'), 'card' => $build($phpPlans, 'popular')],
    ['heading' => __('pricing.theme_section'), 'note' => __('pricing.theme_note'), 'card' => $build($themePlans)],
];

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
        <p class="mt-3 text-sm text-blue-200"><?= $isBd ? __('pricing.bd_currency_note') . ' (1 USD = ' . $rate . ' BDT)' : __('pricing.int_currency_note') ?></p>

        <div class="mt-8 inline-flex items-center gap-3 bg-white/10 border border-white/15 rounded-full p-1.5" data-billing-toggle>
            <button type="button" data-billing="monthly" class="px-5 py-2 rounded-full text-sm font-semibold bg-white text-slate-900">Monthly</button>
            <button type="button" data-billing="yearly" class="px-5 py-2 rounded-full text-sm font-semibold text-slate-200 hover:text-white">Yearly <span class="text-emerald-300 font-bold">· 2 months free</span></button>
        </div>
    </div>
</section>
<?php endif; ?>

<?php \App\Core\View::partial('site.partials.cms_block', ['cmsPage' => $cmsPage ?? null]); ?>

<section class="max-w-7xl mx-auto px-4 py-16">
    <div class="grid lg:grid-cols-3 gap-8 items-stretch">
        <?php foreach ($sections as $sec): $card = $sec['card']; $mp = $card['monthly']; $yp = $card['yearly']; ?>
            <?php if (!$mp && !$yp): continue; endif; ?>
            <div class="relative flex flex-col <?= $card['popular'] === 'popular' ? 'lg:-mt-4 lg:mb-0' : '' ?>">
                <div class="bg-white rounded-3xl border <?= $card['popular'] === 'popular' ? 'border-2 border-blue-600 shadow-xl shadow-blue-600/10' : 'border-slate-200' ?> p-8 flex flex-col flex-1 esk-card-hover" data-plan-card>
                    <?php if ($card['popular'] === 'popular'): ?>
                        <span class="absolute -top-3.5 left-8 bg-blue-600 text-white text-xs font-semibold px-3 py-1 rounded-full"><?= __('pricing.most_popular') ?></span>
                    <?php endif; ?>

                    <h2 class="text-xl font-bold text-slate-900"><?= htmlspecialchars($sec['heading']) ?></h2>
                    <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($sec['note']) ?></p>

                    <div class="mt-6 flex items-end gap-1">
                        <span class="text-4xl font-extrabold text-blue-700" data-symbol="<?= htmlspecialchars($sym) ?>" data-price-monthly="<?= $num((float) ($mp['price'] ?? 0)) ?>" data-price-yearly="<?= $num((float) ($yp['price'] ?? 0)) ?>"><?= $sym ?><?= $num((float) ($mp['price'] ?? 0)) ?></span>
                        <span class="text-xs text-slate-400 uppercase pb-1">
                            / <?= __('pricing.per') ?> <span data-period-label>month</span>
                        </span>
                    </div>
                    <div class="text-xs text-slate-400 uppercase tracking-wide mt-1" data-currency-label><?= $cur ?> · flat, one school site — no student or staff limits</div>
                    <?php if ($yp): ?>
                        <div class="text-xs font-semibold text-emerald-600 mt-1 hidden" data-yearly-save>Yearly — save 2 months (<?= $sym ?><?= $num((float) ($mp['price'] ?? 0) * 12 - (float) ($yp['price'] ?? 0)) ?>/yr)</div>
                    <?php endif; ?>

                    <?php $featList = $features($mp ?: $yp); ?>
                    <?php if (!empty($featList)): ?>
                        <ul class="mt-6 space-y-2.5 text-sm text-slate-600 flex-1">
                            <?php foreach ($featList as $f): ?>
                                <li class="esk-check"><?= htmlspecialchars((string) $f) ?></li>
                            <?php endforeach; ?>
                            <li class="esk-check"><?= __('pricing.faq.1a_short') ?></li>
                        </ul>
                    <?php endif; ?>

                    <?php $planId = $mp['id'] ?? $yp['id'] ?? null; ?>
                    <?php if ($planId): ?>
                        <a href="/checkout?plan=<?= (int) $planId ?>" class="mt-7 block <?= $card['popular'] === 'popular' ? 'bg-blue-600 hover:bg-blue-500' : 'bg-slate-900 hover:bg-blue-600' ?> text-white py-3 rounded-xl text-center font-semibold"><?= __('pricing.buy') ?></a>
                        <p class="text-xs text-slate-400 text-center mt-2"><?= __('home.trust.4') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-16 bg-white rounded-2xl border border-slate-200 p-8 max-w-3xl mx-auto text-center">
        <h3 class="text-xl font-bold"><?= __('pricing.compare_title') ?></h3>
        <p class="text-slate-500 mt-2 text-sm"><?= __('pricing.compare_sub') ?></p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="/products/app" class="esk-chip border border-blue-300 text-blue-600 px-6 py-2.5 rounded-xl font-semibold"><?= __('pricing.cta_app') ?></a>
            <a href="/products/php" class="esk-chip border border-blue-300 text-blue-600 px-6 py-2.5 rounded-xl font-semibold"><?= __('pricing.cta_php') ?></a>
            <a href="/products/theme" class="esk-chip border border-blue-300 text-blue-600 px-6 py-2.5 rounded-xl font-semibold"><?= __('pricing.cta_theme') ?></a>
        </div>
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
(function () {
    var toggle = document.querySelector('[data-billing-toggle]');
    if (!toggle) return;
    var buttons = toggle.querySelectorAll('[data-billing]');
    function apply(mode) {
        buttons.forEach(function (b) {
            var on = b.getAttribute('data-billing') === mode;
            b.classList.toggle('bg-white', on);
            b.classList.toggle('text-slate-900', on);
            b.classList.toggle('text-slate-200', !on);
        });
        document.querySelectorAll('[data-price-monthly]').forEach(function (el) {
            var sym = el.getAttribute('data-symbol') || '$';
            var val = mode === 'yearly' ? el.getAttribute('data-price-yearly') : el.getAttribute('data-price-monthly');
            var period = el.parentElement.querySelector('[data-period-label]');
            if (period) period.textContent = mode === 'yearly' ? 'year' : 'month';
            el.textContent = sym + val;
            var save = el.closest('[data-plan-card]').querySelector('[data-yearly-save]');
            if (save) save.classList.toggle('hidden', mode !== 'yearly');
        });
    }
    buttons.forEach(function (b) {
        b.addEventListener('click', function () { apply(b.getAttribute('data-billing')); });
    });
})();
</script>