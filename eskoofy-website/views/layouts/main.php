<!DOCTYPE html>
<html lang="<?= \App\Services\I18n::current() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? ($siteTitle ?? __('brand.name'))) ?> — <?= __('brand.name') ?></title>
    <meta name="description" content="Eskoofy is an all-in-one school management system — admissions, attendance, fees, exams, results, transport, hostels, payroll and reporting. Deploy as a cloud or self-hosted app, a raw PHP system, or a WordPress theme.">
    <meta name="theme-color" content="#2563eb">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Eskoofy">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .esk-hero { background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #2563eb 100%); position: relative; overflow: hidden; }
        .esk-hero::after { content: ''; position: absolute; inset: 0; background-image: radial-gradient(rgba(255,255,255,.08) 1px, transparent 1px); background-size: 28px 28px; pointer-events: none; }
        .esk-card-hover { transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease; }
        .esk-card-hover:hover { transform: translateY(-4px); box-shadow: 0 16px 36px rgba(37,99,235,.14); border-color: rgba(37,99,235,.35); }
        .esk-btn-primary { transition: all .15s ease; }
        .esk-btn-primary:hover { box-shadow: 0 10px 24px rgba(37,99,235,.35); transform: translateY(-1px); }
        .esk-chip { transition: all .15s ease; }
        .esk-chip:hover { border-color: #3b82f6; color: #2563eb; }
        .esk-fade-up { animation: eskFadeUp .5s ease both; }
        @keyframes eskFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .esk-table th, .esk-table td { vertical-align: middle; }
        .esk-check::before { content: '✓'; display: inline-block; color: #16a34a; font-weight: 700; margin-right: .5rem; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased font-sans">

<header class="bg-slate-900 text-white sticky top-0 z-40 shadow-lg shadow-slate-900/20">
    <nav class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <a href="/" class="flex items-center gap-2 font-bold text-xl">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 font-black">E</span>
            <?= __('brand.name') ?>
        </a>
        <div class="hidden md:flex items-center gap-6 text-sm font-medium text-slate-200">
            <a href="/products/app" class="hover:text-white"><?= __('nav.school_app') ?></a>
            <a href="/products/theme" class="hover:text-white"><?= __('nav.wp_theme') ?></a>
            <a href="/products/php" class="hover:text-white"><?= __('nav.raw_php') ?></a>
            <a href="/pricing" class="hover:text-white"><?= __('nav.pricing') ?></a>
            <a href="/features" class="hover:text-white"><?= __('nav.features') ?></a>
            <a href="/blog" class="hover:text-white"><?= __('nav.blog') ?></a>
            <a href="/about" class="hover:text-white"><?= __('nav.about') ?></a>
            <a href="/contact" class="hover:text-white"><?= __('nav.contact') ?></a>
        </div>
        <div class="flex items-center gap-3 text-sm">
            <?php if (\App\Core\Auth::check()): ?>
                <a href="/account" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg font-semibold"><?= __('nav.my_account') ?></a>
                <?php if (\App\Core\Auth::hasRole('admin')): ?>
                    <a href="/admin" class="border border-slate-600 hover:border-slate-400 px-4 py-2 rounded-lg"><?= __('nav.admin') ?></a>
                <?php endif; ?>
                <a href="/logout" class="text-slate-300 hover:text-white px-2"><?= __('nav.logout') ?></a>
            <?php else: ?>
                <a href="/login" class="hover:text-white hidden sm:inline"><?= __('nav.login') ?></a>
                <a href="/register" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg font-semibold"><?= __('nav.register') ?></a>
            <?php endif; ?>
            <span class="relative inline-flex">
                <form method="get" action="/language/<?= \App\Services\I18n::current() === 'en' ? 'bn' : 'en' ?>">
                    <button type="submit" class="border border-slate-600 hover:border-slate-400 px-3 py-1.5 rounded-lg text-xs text-slate-200">
                        <?= \App\Services\I18n::current() === 'en' ? 'বাংলা' : 'English' ?>
                    </button>
                </form>
            </span>
        </div>
    </nav>
    <nav class="md:hidden border-t border-slate-800">
        <details class="group">
            <summary class="flex items-center justify-between px-4 py-3 text-sm font-semibold text-slate-200 cursor-pointer">
                <span>Menu</span>
                <span class="transition-transform group-open:rotate-180">▾</span>
            </summary>
            <div class="px-4 pb-4 flex flex-col gap-3 text-sm font-medium text-slate-200">
                <a href="/products/app" class="hover:text-white"><?= __('nav.school_app') ?></a>
                <a href="/products/theme" class="hover:text-white"><?= __('nav.wp_theme') ?></a>
                <a href="/products/php" class="hover:text-white"><?= __('nav.raw_php') ?></a>
                <a href="/pricing" class="hover:text-white"><?= __('nav.pricing') ?></a>
                <a href="/features" class="hover:text-white"><?= __('nav.features') ?></a>
                <a href="/blog" class="hover:text-white"><?= __('nav.blog') ?></a>
                <a href="/about" class="hover:text-white"><?= __('nav.about') ?></a>
                <a href="/contact" class="hover:text-white"><?= __('nav.contact') ?></a>
            </div>
        </details>
    </nav>
</header>

<?php
$flashMsg = '';
$flashType = '';
$eskSession = \App\Core\Session::getInstance();
foreach (['success', 'error', 'info'] as $fType) {
    $maybe = $eskSession->getFlash($fType);
    if ($maybe !== null && $maybe !== '') {
        $flashMsg = is_scalar($maybe) ? (string) $maybe : (string) json_encode($maybe);
        $flashType = $fType;
        break;
    }
}
?>
<?php if ($flashMsg !== ''): ?>
    <div class="max-w-7xl mx-auto px-4 mt-4">
        <div class="rounded-lg px-4 py-3 text-sm font-medium <?= $flashType === 'success' ? 'bg-green-100 text-green-800' : ($flashType === 'error' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800') ?>">
            <?= htmlspecialchars((string) $flashMsg) ?>
        </div>
    </div>
<?php endif; ?>

<main>
    <?= $contentHtml ?>
</main>

<footer class="bg-slate-900 text-slate-400">
    <div class="max-w-7xl mx-auto px-4 py-14 grid md:grid-cols-4 gap-8 text-sm">
        <div>
            <div class="text-white font-bold text-lg mb-2"><?= __('brand.name') ?></div>
            <p class="text-slate-500"><?= __('brand.tagline_full') ?></p>
            <p class="text-slate-600 mt-3 text-xs">© <?= date('Y') ?> <?= __('brand.name') ?>. <?= __('footer.rights') ?></p>
        </div>
        <div>
            <div class="text-white font-semibold mb-3 uppercase text-xs tracking-widest"><?= __('footer.products') ?></div>
            <ul class="space-y-2">
                <li><a href="/products/app" class="hover:text-white"><?= __('nav.school_app') ?></a></li>
                <li><a href="/products/php" class="hover:text-white"><?= __('nav.raw_php') ?></a></li>
                <li><a href="/products/theme" class="hover:text-white"><?= __('nav.wp_theme') ?></a></li>
                <li><a href="/pricing" class="hover:text-white"><?= __('nav.pricing') ?></a></li>
            </ul>
        </div>
        <div>
            <div class="text-white font-semibold mb-3 uppercase text-xs tracking-widest"><?= __('footer.company') ?></div>
            <ul class="space-y-2">
                <li><a href="/features" class="hover:text-white"><?= __('nav.features') ?></a></li>
                <li><a href="/about" class="hover:text-white"><?= __('nav.about') ?></a></li>
                <li><a href="/blog" class="hover:text-white"><?= __('nav.blog') ?></a></li>
                <li><a href="/contact" class="hover:text-white"><?= __('nav.contact') ?></a></li>
            </ul>
        </div>
        <div>
            <div class="text-white font-semibold mb-3 uppercase text-xs tracking-widest"><?= __('footer.support') ?></div>
            <ul class="space-y-2">
                <li><a href="/account" class="hover:text-white"><?= __('footer.license_portal') ?></a></li>
                <li><a href="/register" class="hover:text-white"><?= __('footer.create_account') ?></a></li>
                <li><a href="/login" class="hover:text-white"><?= __('footer.sign_in') ?></a></li>
            </ul>
            <div class="mt-4 text-xs text-slate-500"><?= __('footer.currency_note') ?></div>
        </div>
    </div>
</footer>

<?php if (\App\Services\I18n::wantsTimezoneHint()): ?>
<script>
window.addEventListener('load', function () {
    try {
        var tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
        if (!tz) return;
        fetch('/language/geo?tz=' + encodeURIComponent(tz), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (d && d.reload) window.location.reload();
        }).catch(function () {});
    } catch (e) {}
});
</script>
<?php endif; ?>

<script src="/js/register-sw.js" defer></script>
</body>
</html>