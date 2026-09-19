<?php
declare(strict_types=1);

$fSettings = \App\Models\Settings::all();
$fBrand = (string) ($fSettings['site.name'] ?? 'Eskoofy');
$fTagline = (string) ($fSettings['site.tagline_full'] ?? __('brand.tagline_full'));
$fSupportEmail = (string) ($fSettings['site.support_email'] ?? $fSettings['site.contact_email'] ?? '');
$fSalesEmail = (string) ($fSettings['site.sales_email'] ?? '');
$fPhone = (string) ($fSettings['site.support_phone'] ?? '');
$fWhatsapp = preg_replace('/[^0-9]/', '', (string) ($fSettings['site.whatsapp'] ?? ''));
$fAddress = (string) ($fSettings['site.address'] ?? '');
$fHours = (string) ($fSettings['site.support_hours'] ?? '');

$fSocials = [
    'facebook'  => ['url' => (string) ($fSettings['site.social_facebook'] ?? ''),  'label' => 'Facebook',  'd' => 'M14 9h3l.5-3H14V4.5c0-.9.3-1.5 1.6-1.5H18V.3C17.6.2 16.4 0 15.1 0 12.1 0 10 1.8 10 5.2V6H7v3h3v9h4V9Z'],
    'instagram' => ['url' => (string) ($fSettings['site.social_instagram'] ?? ''), 'label' => 'Instagram', 'd' => 'M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 1.8.2 2.2.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.2.4.4 1 .4 2.2.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.2 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.4.2-1 .4-2.2.4-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2-.1-1.8-.2-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.8-.9-1.4-.2-.4-.4-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.9c.1-1.2.2-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.4-.2 1-.4 2.2-.4C8.4 2.2 8.8 2.2 12 2.2Zm0 5.1a4.7 4.7 0 1 0 0 9.4 4.7 4.7 0 0 0 0-9.4Zm0 7.7a3 3 0 1 1 0-6 3 3 0 0 1 0 6Zm6-7.9a1.1 1.1 0 1 1-2.2 0 1.1 1.1 0 0 1 2.2 0Z'],
    'twitter'   => ['url' => (string) ($fSettings['site.social_twitter'] ?? ''),   'label' => 'X',         'd' => 'M18.2 2h3.3l-7.2 8.2L23 22h-6.6l-5.2-6.8L5.2 22H1.9l7.7-8.8L1 2h6.8l4.7 6.2L18.2 2Zm-1.2 18h1.8L7.1 3.9H5.1L17 20Z'],
    'linkedin'  => ['url' => (string) ($fSettings['site.social_linkedin'] ?? ''),  'label' => 'LinkedIn',  'd' => 'M6.9 21H3V9h3.9v12ZM5 7.5A2.3 2.3 0 1 1 5 3a2.3 2.3 0 0 1 0 4.5ZM21 21h-3.9v-5.8c0-1.4-.5-2.3-1.7-2.3-1 0-1.5.6-1.8 1.3-.1.2-.1.5-.1.9V21H9.6s.1-10.9 0-12h3.9v1.7c.5-.8 1.4-1.9 3.5-1.9 2.5 0 4.4 1.7 4.4 5.2V21Z'],
    'youtube'   => ['url' => (string) ($fSettings['site.social_youtube'] ?? ''),   'label' => 'YouTube',   'd' => 'M23 7.5a3 3 0 0 0-2.1-2.1C19 4.9 12 4.9 12 4.9s-7 0-8.9.5A3 3 0 0 0 1 7.5C.5 9.4.5 12 .5 12s0 2.6.5 4.5a3 3 0 0 0 2.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 0 0 2.1-2.1c.5-1.9.5-4.5.5-4.5s0-2.6-.5-4.5ZM9.8 15.3V8.7l5.7 3.3-5.7 3.3Z'],
];
$fSocials = array_filter($fSocials, fn ($s) => $s['url'] !== '');
?>
<footer class="bg-slate-900 text-slate-400 border-t border-slate-800" aria-labelledby="site-footer-heading">
    <h2 id="site-footer-heading" class="sr-only"><?= __('footer.heading') ?></h2>

    <div class="max-w-7xl mx-auto px-4 pt-14 pb-10">
        <div class="grid gap-10 lg:grid-cols-12">
            <!-- Brand -->
            <div class="lg:col-span-4">
                <a href="/" class="flex items-center gap-2.5 mb-4">
                    <img src="/brand/eskofy-mark.svg" alt="<?= htmlspecialchars($fBrand) ?>" class="h-10 w-10 drop-shadow-md">
                    <span class="text-white font-bold text-xl tracking-tight"><?= htmlspecialchars($fBrand) ?></span>
                </a>
                <p class="text-sm text-slate-500 leading-relaxed max-w-sm"><?= htmlspecialchars($fTagline) ?></p>

                <ul class="mt-5 space-y-2 text-xs text-slate-500">
                    <?php foreach ([__('footer.trust_1'), __('footer.trust_2'), __('footer.trust_3')] as $fTrust): ?>
                        <li class="flex items-start gap-2">
                            <svg viewBox="0 0 24 24" class="h-4 w-4 flex-shrink-0 text-green-500 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            <span><?= htmlspecialchars($fTrust) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if (!empty($fSocials)): ?>
                    <div class="mt-6">
                        <div class="text-xs uppercase tracking-widest text-slate-600 font-semibold mb-2"><?= __('footer.follow') ?></div>
                        <div class="flex items-center gap-2">
                            <?php foreach ($fSocials as $fName => $fS): ?>
                                <a href="<?= htmlspecialchars($fS['url']) ?>" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-700 text-slate-400 hover:text-white hover:border-slate-500 transition"
                                   aria-label="<?= htmlspecialchars($fS['label']) ?>">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true"><path d="<?= $fS['d'] ?>"/></svg>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Link columns -->
            <nav class="lg:col-span-2" aria-label="<?= __('footer.products') ?>">
                <div class="text-white font-semibold mb-3 uppercase text-xs tracking-widest"><?= __('footer.products') ?></div>
                <ul class="space-y-2 text-sm">
                    <li><a href="/products/app" class="hover:text-white transition"><?= __('nav.school_app') ?></a></li>
                    <li><a href="/products/php" class="hover:text-white transition"><?= __('nav.raw_php') ?></a></li>
                    <li><a href="/products/theme" class="hover:text-white transition"><?= __('nav.wp_theme') ?></a></li>
                    <li><a href="/pricing" class="hover:text-white transition"><?= __('nav.pricing') ?></a></li>
                    <li><a href="/compare" class="hover:text-white transition"><?= __('nav.compare') ?></a></li>
                </ul>
            </nav>

            <nav class="lg:col-span-2" aria-label="<?= __('footer.solutions') ?>">
                <div class="text-white font-semibold mb-3 uppercase text-xs tracking-widest"><?= __('footer.solutions') ?></div>
                <ul class="space-y-2 text-sm">
                    <li><a href="/features" class="hover:text-white transition"><?= __('footer.sol_modules') ?></a></li>
                    <li><a href="/features" class="hover:text-white transition"><?= __('footer.sol_fees') ?></a></li>
                    <li><a href="/features" class="hover:text-white transition"><?= __('footer.sol_portal') ?></a></li>
                    <li><a href="/features" class="hover:text-white transition"><?= __('footer.sol_api') ?></a></li>
                    <li><a href="/contact" class="hover:text-white transition"><?= __('footer.sol_migration') ?></a></li>
                </ul>
            </nav>

            <nav class="lg:col-span-2" aria-label="<?= __('footer.company') ?>">
                <div class="text-white font-semibold mb-3 uppercase text-xs tracking-widest"><?= __('footer.company') ?></div>
                <ul class="space-y-2 text-sm">
                    <li><a href="/about" class="hover:text-white transition"><?= __('nav.about') ?></a></li>
                    <li><a href="/blog" class="hover:text-white transition"><?= __('nav.blog') ?></a></li>
                    <li><a href="/contact" class="hover:text-white transition"><?= __('nav.contact') ?></a></li>
                    <li><a href="/account" class="hover:text-white transition"><?= __('footer.license_portal') ?></a></li>
                    <li><a href="/register" class="hover:text-white transition"><?= __('footer.create_account') ?></a></li>
                </ul>
            </nav>

            <!-- Contact -->
            <div class="lg:col-span-2" aria-label="<?= __('footer.contact_heading') ?>">
                <div class="text-white font-semibold mb-3 uppercase text-xs tracking-widest"><?= __('footer.contact_heading') ?></div>
                <ul class="space-y-2 text-sm">
                    <?php if ($fSupportEmail !== ''): ?>
                        <li><a href="mailto:<?= htmlspecialchars($fSupportEmail) ?>" class="hover:text-white transition break-all"><?= htmlspecialchars($fSupportEmail) ?></a></li>
                    <?php endif; ?>
                    <?php if ($fSalesEmail !== ''): ?>
                        <li><a href="mailto:<?= htmlspecialchars($fSalesEmail) ?>" class="hover:text-white transition break-all"><?= htmlspecialchars($fSalesEmail) ?></a></li>
                    <?php endif; ?>
                    <?php if ($fPhone !== ''): ?>
                        <li><a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $fPhone)) ?>" class="hover:text-white transition"><?= htmlspecialchars($fPhone) ?></a></li>
                    <?php endif; ?>
                    <?php if ($fWhatsapp !== ''): ?>
                        <li><a href="https://wa.me/<?= htmlspecialchars($fWhatsapp) ?>" target="_blank" rel="noopener noreferrer" class="hover:text-white transition"><?= __('footer.whatsapp') ?></a></li>
                    <?php endif; ?>
                    <?php if ($fAddress !== ''): ?>
                        <li class="text-slate-500"><?= htmlspecialchars($fAddress) ?></li>
                    <?php endif; ?>
                    <?php if ($fHours !== ''): ?>
                        <li class="text-slate-500"><?= htmlspecialchars($fHours) ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Legal & resources row -->
        <div class="mt-12 pt-8 border-t border-slate-800 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <div class="text-white font-semibold mb-3 uppercase text-xs tracking-widest"><?= __('footer.resources') ?></div>
                <ul class="space-y-2 text-sm">
                    <li><a href="/features" class="hover:text-white transition"><?= __('nav.features') ?></a></li>
                    <li><a href="/blog" class="hover:text-white transition"><?= __('footer.res_guides') ?></a></li>
                    <li><a href="/compare" class="hover:text-white transition"><?= __('nav.compare') ?></a></li>
                    <li><a href="/contact" class="hover:text-white transition"><?= __('nav.get_demo') ?></a></li>
                </ul>
            </div>
            <div>
                <div class="text-white font-semibold mb-3 uppercase text-xs tracking-widest"><?= __('footer.legal') ?></div>
                <ul class="space-y-2 text-sm">
                    <li><a href="/terms" class="hover:text-white transition"><?= __('legal.terms.title') ?></a></li>
                    <li><a href="/privacy" class="hover:text-white transition"><?= __('legal.privacy.title') ?></a></li>
                    <li><a href="/refund-policy" class="hover:text-white transition"><?= __('legal.refund.title') ?></a></li>
                </ul>
            </div>
            <div class="lg:col-span-2">
                <div class="text-white font-semibold mb-3 uppercase text-xs tracking-widest"><?= __('footer.get_started') ?></div>
                <p class="text-sm text-slate-500 mb-3"><?= __('footer.get_started_sub') ?></p>
                <div class="flex flex-wrap gap-2">
                    <a href="/register" class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition"><?= __('footer.create_account') ?></a>
                    <a href="/contact" class="border border-slate-600 hover:border-slate-400 text-slate-200 text-sm font-semibold px-4 py-2.5 rounded-lg transition"><?= __('nav.get_demo') ?></a>
                </div>
                <div class="mt-4 inline-flex items-center gap-2 text-xs text-slate-500">
                    <span class="inline-block h-2 w-2 rounded-full bg-green-500" aria-hidden="true"></span>
                    <?= __('footer.status_operational') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom bar -->
    <div class="border-t border-slate-800 bg-slate-950/60">
        <div class="max-w-7xl mx-auto px-4 py-5 flex flex-col md:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <div class="text-center md:text-left">© <?= date('Y') ?> <?= htmlspecialchars($fBrand) ?>. <?= __('footer.rights') ?></div>
            <div class="flex items-center gap-4">
                <span><?= __('footer.currency_note') ?></span>
                <span class="hidden md:inline text-slate-700">•</span>
                <span><?= __('footer.built_for') ?></span>
            </div>
        </div>
    </div>
</footer>
