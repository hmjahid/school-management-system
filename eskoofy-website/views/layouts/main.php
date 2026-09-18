<!DOCTYPE html>
<?php
$eskSettings = \App\Models\Settings::all();
$eskBrandName = (string) ($eskSettings['site.name'] ?? '');
$eskBrandTag  = (string) ($eskSettings['site.tagline'] ?? '');
$eskColour    = (string) ($eskSettings['appearance.brand_color'] ?? '#2563eb');
if ($eskBrandName === '' || $eskBrandName === 'Eskoofy') {
    $eskBrandName = $eskBrandName;
}
if ($eskBrandTag === '' || $eskBrandTag === 'School management software & WordPress theme') {
    $eskBrandTag = __('brand.tagline');
}
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $eskColour)) {
    $eskColour = '#2563eb';
}
?>
<html lang="<?= \App\Services\I18n::current() ?>" style="--esk-brand: <?= $eskColour ?>;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? ($siteTitle ?? $eskBrandName)) ?> — <?= htmlspecialchars($eskBrandName) ?></title>
    <meta name="description" content="Eskoofy is an all-in-one school management system — admissions, attendance, fees, exams, results, transport, hostels, payroll and reporting. Runs on your own server as a full app, a raw PHP system, or a WordPress theme.">
    <meta name="theme-color" content="<?= $eskColour ?>" data-light-theme="<?= $eskColour ?>">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($eskBrandName) ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
    </script>
    <style>
        .esk-hero { background: linear-gradient(135deg, #0f172a 0%, var(--esk-brand) 55%, #2563eb 100%); position: relative; overflow: hidden; }
        .esk-hero::after { content: ''; position: absolute; inset: 0; background-image: radial-gradient(rgba(255,255,255,.08) 1px, transparent 1px); background-size: 28px 28px; pointer-events: none; }
        .esk-card-hover { transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease; }
        .esk-card-hover:hover { transform: translateY(-4px); box-shadow: 0 16px 36px rgba(37,99,235,.14); border-color: rgba(37,99,235,.35); }
        .esk-btn-primary { transition: all .15s ease; }
        .esk-btn-primary:hover { box-shadow: 0 10px 24px rgba(37,99,235,.35); transform: translateY(-1px); }
        .esk-chip { transition: all .15s ease; }
        .esk-chip:hover { border-color: #3b82f6; color: #2563eb; }
        .esk-chip.dark-mode-on:hover { color: #60a5fa; }
        .esk-fade-up { animation: eskFadeUp .5s ease both; }
        @keyframes eskFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .esk-table th, .esk-table td { vertical-align: middle; }
        .esk-check::before { content: '✓'; display: inline-block; color: #16a34a; font-weight: 700; margin-right: .5rem; }
        .esk-drawer { transition: transform .25s ease; }
        .esk-drawer.open { transform: translateX(0); }
        .esk-drop { opacity: 0; visibility: hidden; transform: translateY(8px); transition: opacity .15s ease, transform .15s ease, visibility .15s; pointer-events: none; }
        .esk-nav-group:hover .esk-drop, .esk-nav-group:focus-within .esk-drop { opacity: 1; visibility: visible; transform: none; pointer-events: auto; }

        /* Dark theme — a tasteful, consistent flip of the light shell. */
        .dark body { background-color: #0f172a; color: #e2e8f0; }
        .dark .bg-slate-50 { background-color: #0f172a; }
        .dark .bg-white { background-color: #1e293b; }
        .dark .border-slate-200, .dark .border-slate-100 { border-color: #334155; }
        .dark .text-slate-900 { color: #f1f5f9; }
        .dark .text-slate-700 { color: #e2e8f0; }
        .dark .text-slate-600, .dark .text-slate-500 { color: #94a3b8; }
        .dark .bg-slate-100 { background-color: #1e293b; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased font-sans">

<?php $__flashes = \App\Core\Session::getInstance()->flashAll(); ?>
<?php if (!empty($__flashes)): ?>
    <div class="fixed top-4 right-4 z-[60] w-full max-w-sm space-y-2 px-4">
        <?php foreach ((array) ($__flashes['success'] ?? []) as $__msg): ?>
            <div class="bg-green-600 text-white text-sm font-medium px-4 py-3 rounded-xl shadow-lg" role="alert"><?= htmlspecialchars((string) $__msg) ?></div>
        <?php endforeach; foreach ((array) ($__flashes['error'] ?? []) as $__msg): ?>
            <div class="bg-red-600 text-white text-sm font-medium px-4 py-3 rounded-xl shadow-lg" role="alert"><?= htmlspecialchars((string) $__msg) ?></div>
        <?php endforeach; if (!empty($__flashes['errors'])): ?>
            <div class="bg-red-600 text-white text-sm font-medium px-4 py-3 rounded-xl shadow-lg space-y-1" role="alert">
                <?php foreach ((array) $__flashes['errors'] as $__field => $__errs): ?>
                    <?php foreach ((array) $__errs as $__err): ?>
                        <div><?= htmlspecialchars((string) $__err) ?></div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<header class="bg-slate-900 text-white sticky top-0 z-40 shadow-lg shadow-slate-900/20">
    <nav class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <a href="/" class="flex items-center gap-2.5">
            <img src="/brand/eskofy-mark.svg" alt="<?= $eskBrandName ?>" class="h-11 w-11 drop-shadow-md">
            <span class="font-bold text-xl tracking-tight"><?= $eskBrandName ?></span>
        </a>

        <button type="button" data-drawer-open aria-label="Open menu" class="md:hidden inline-flex items-center justify-center rounded-lg border border-slate-600 p-2 text-slate-200 hover:text-white">
            <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <div class="hidden md:flex items-center gap-1 text-sm font-medium text-slate-200">
            <div class="relative esk-nav-group">
                <button type="button" aria-haspopup="true" class="flex items-center gap-1 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">
                    <?= __('nav.products') ?>
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 8 4 4 4-4"/></svg>
                </button>
                <div class="esk-drop absolute top-full left-0 mt-1 w-64 rounded-xl bg-white text-slate-700 shadow-xl border border-slate-100 p-2 z-50">
                    <a href="/products/app" class="block px-3 py-2.5 rounded-lg hover:bg-slate-50 hover:text-blue-600">
                        <div class="font-semibold text-slate-800"><?= __('nav.school_app') ?></div>
                        <div class="text-xs text-slate-500 mt-0.5"><?= __('nav.products_app_desc') ?></div>
                    </a>
                    <a href="/products/php" class="block px-3 py-2.5 rounded-lg hover:bg-slate-50 hover:text-blue-600">
                        <div class="font-semibold text-slate-800"><?= __('nav.raw_php') ?></div>
                        <div class="text-xs text-slate-500 mt-0.5"><?= __('nav.products_php_desc') ?></div>
                    </a>
                    <a href="/products/theme" class="block px-3 py-2.5 rounded-lg hover:bg-slate-50 hover:text-blue-600">
                        <div class="font-semibold text-slate-800"><?= __('nav.wp_theme') ?></div>
                        <div class="text-xs text-slate-500 mt-0.5"><?= __('nav.products_theme_desc') ?></div>
                    </a>
                    <div class="my-1 border-t border-slate-100"></div>
                    <a href="/compare" class="block px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-blue-600 text-xs">
                        <span class="font-semibold text-slate-800"><?= __('nav.compare') ?></span> — <?= __('nav.products_compare_desc') ?>
                    </a>
                </div>
            </div>
            <a href="/features" class="px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white"><?= __('nav.features') ?></a>
            <a href="/pricing" class="px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white"><?= __('nav.pricing') ?></a>
            <div class="relative esk-nav-group">
                <button type="button" aria-haspopup="true" class="flex items-center gap-1 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">
                    <?= __('nav.resources') ?>
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 8 4 4 4-4"/></svg>
                </button>
                <div class="esk-drop absolute top-full left-0 mt-1 w-56 rounded-xl bg-white text-slate-700 shadow-xl border border-slate-100 p-2 z-50">
                    <a href="/blog" class="block px-3 py-2.5 rounded-lg hover:bg-slate-50 hover:text-blue-600">
                        <div class="font-semibold text-slate-800"><?= __('nav.blog') ?></div>
                        <div class="text-xs text-slate-500 mt-0.5"><?= __('nav.resources_blog_desc') ?></div>
                    </a>
                    <a href="/about" class="block px-3 py-2.5 rounded-lg hover:bg-slate-50 hover:text-blue-600">
                        <div class="font-semibold text-slate-800"><?= __('nav.about') ?></div>
                        <div class="text-xs text-slate-500 mt-0.5"><?= __('nav.resources_about_desc') ?></div>
                    </a>
                    <a href="/contact" class="block px-3 py-2.5 rounded-lg hover:bg-slate-50 hover:text-blue-600">
                        <div class="font-semibold text-slate-800"><?= __('nav.contact') ?></div>
                        <div class="text-xs text-slate-500 mt-0.5"><?= __('nav.resources_contact_desc') ?></div>
                    </a>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3 text-sm">
            <button type="button" data-dark-toggle aria-label="Toggle dark mode" class="inline-flex items-center justify-center rounded-lg border border-slate-600 p-2 text-slate-200 hover:text-white">
                <svg data-icon-moon viewBox="0 0 24 24" class="h-5 w-5 hidden" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                <svg data-icon-sun viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            </button>
            <?php if (\App\Core\Auth::check()): ?>
                <a href="/account" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg font-semibold"><?= __('nav.my_account') ?></a>
                <?php if (\App\Core\Auth::hasRole('admin')): ?>
                    <a href="/admin" class="border border-slate-600 hover:border-slate-400 px-4 py-2 rounded-lg"><?= __('nav.admin') ?></a>
                <?php endif; ?>
                <a href="/logout" class="text-slate-300 hover:text-white px-2"><?= __('nav.logout') ?></a>
            <?php else: ?>
                <a href="/login" class="hover:text-white hidden sm:inline"><?= __('nav.login') ?></a>
                <a href="/contact" class="border border-blue-400/50 hover:border-blue-300 px-4 py-2 rounded-lg font-semibold text-blue-200 hover:text-white"><?= __('nav.get_demo') ?></a>
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
</header>

<!-- Mobile drawer -->
<div data-drawer class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-label="Menu">
    <div data-drawer-backdrop class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
    <nav class="esk-drawer absolute right-0 top-0 h-full w-72 bg-slate-900 text-slate-200 flex flex-col p-6 translate-x-full">
        <div class="flex items-center justify-between mb-8">
            <a href="/" class="flex items-center gap-2">
                <img src="/brand/eskofy-mark.svg" alt="<?= $eskBrandName ?>" class="h-9 w-9">
                <span class="font-bold text-lg text-white"><?= $eskBrandName ?></span>
            </a>
            <button type="button" data-drawer-close aria-label="Close menu" class="rounded-lg border border-slate-600 p-2 text-slate-200 hover:text-white">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="flex flex-col gap-1 text-sm font-medium">
            <div class="px-3 pt-2 pb-1 text-xs uppercase tracking-widest text-slate-500"><?= __('nav.products') ?></div>
            <a href="/products/app" class="rounded-lg px-3 py-2.5 hover:bg-slate-800 hover:text-white"><?= __('nav.school_app') ?></a>
            <a href="/products/php" class="rounded-lg px-3 py-2.5 hover:bg-slate-800 hover:text-white"><?= __('nav.raw_php') ?></a>
            <a href="/products/theme" class="rounded-lg px-3 py-2.5 hover:bg-slate-800 hover:text-white"><?= __('nav.wp_theme') ?></a>
            <a href="/compare" class="rounded-lg px-3 py-2.5 hover:bg-slate-800 hover:text-white"><?= __('nav.compare') ?></a>
            <div class="px-3 pt-3 pb-1 text-xs uppercase tracking-widest text-slate-500"><?= __('nav.explore') ?></div>
            <a href="/features" class="rounded-lg px-3 py-2.5 hover:bg-slate-800 hover:text-white"><?= __('nav.features') ?></a>
            <a href="/pricing" class="rounded-lg px-3 py-2.5 hover:bg-slate-800 hover:text-white"><?= __('nav.pricing') ?></a>
            <div class="px-3 pt-3 pb-1 text-xs uppercase tracking-widest text-slate-500"><?= __('nav.company') ?></div>
            <a href="/blog" class="rounded-lg px-3 py-2.5 hover:bg-slate-800 hover:text-white"><?= __('nav.blog') ?></a>
            <a href="/about" class="rounded-lg px-3 py-2.5 hover:bg-slate-800 hover:text-white"><?= __('nav.about') ?></a>
            <a href="/contact" class="rounded-lg px-3 py-2.5 hover:bg-slate-800 hover:text-white"><?= __('nav.contact') ?></a>
        </div>
        <div class="mt-auto border-t border-slate-800 pt-4 flex flex-col gap-3 text-sm">
            <?php if (\App\Core\Auth::check()): ?>
                <a href="/account" class="bg-blue-600 hover:bg-blue-500 text-white rounded-lg px-4 py-2.5 text-center font-semibold"><?= __('nav.my_account') ?></a>
                <a href="/logout" class="text-slate-300 hover:text-white text-center"><?= __('nav.logout') ?></a>
            <?php else: ?>
                <a href="/register" class="bg-blue-600 hover:bg-blue-500 text-white rounded-lg px-4 py-2.5 text-center font-semibold"><?= __('nav.register') ?></a>
                <a href="/login" class="text-slate-300 hover:text-white text-center"><?= __('nav.login') ?></a>
            <?php endif; ?>
        </div>
    </nav>
</div>

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
            <div class="flex items-center gap-2.5 mb-2">
                <img src="/brand/eskofy-mark.svg" alt="" class="h-8 w-8">
                <span class="text-white font-bold text-lg"><?= $eskBrandName ?></span>
            </div>
            <p class="text-slate-500"><?= __('brand.tagline_full') ?></p>
            <p class="text-slate-600 mt-3 text-xs">© <?= date('Y') ?> <?= $eskBrandName ?>. <?= __('footer.rights') ?></p>
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
<script src="/js/site.js" defer></script>
<script>
(function () {
    function wireDrawer() {
        var drawer = document.querySelector('[data-drawer]');
        if (!drawer) return;
        var nav = drawer.querySelector('.esk-drawer');
        var openBtn = document.querySelector('[data-drawer-open]');
        var closeBtn = document.querySelector('[data-drawer-close]');
        var backdrop = document.querySelector('[data-drawer-backdrop]');
        function openDrawer() {
            drawer.classList.remove('hidden');
            requestAnimationFrame(function () { nav.classList.add('open'); });
            document.body.style.overflow = 'hidden';
        }
        function closeDrawer() {
            nav.classList.remove('open');
            setTimeout(function () { drawer.classList.add('hidden'); }, 250);
            document.body.style.overflow = '';
        }
        if (openBtn) openBtn.addEventListener('click', openDrawer);
        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
        if (backdrop) backdrop.addEventListener('click', closeDrawer);
        nav.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', closeDrawer); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDrawer(); });
    }

    function wireDarkMode() {
        var root = document.documentElement;
        var stored = null;
        try { stored = localStorage.getItem('esk-dark'); } catch (e) {}
        var dark;
        if (stored === '1') dark = true;
        else if (stored === '0') dark = false;
        else dark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        root.classList.toggle('dark', dark);

        function sync() {
            var isDark = root.classList.contains('dark');
            var meta = document.querySelector('meta[name="theme-color"]');
            if (meta) meta.setAttribute('content', isDark ? '#0f172a' : (meta.getAttribute('data-light-theme') || '#2563eb'));
            var moon = document.querySelector('[data-icon-moon]');
            var sun = document.querySelector('[data-icon-sun]');
            if (moon) moon.classList.toggle('hidden', !isDark);
            if (sun) sun.classList.toggle('hidden', isDark);
        }
        sync();
        var btn = document.querySelector('[data-dark-toggle]');
        if (btn) btn.addEventListener('click', function () {
            root.classList.toggle('dark');
            try { localStorage.setItem('esk-dark', root.classList.contains('dark') ? '1' : '0'); } catch (e) {}
            sync();
        });
    }

    wireDrawer();
    wireDarkMode();
})();
</script>
</body>
</html>