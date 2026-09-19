<!DOCTYPE html>
<?php
$eskSettings = \App\Models\Settings::all();
$eskBrandName = (string) ($eskSettings['site.name'] ?? '');
$eskBrandName = ($eskBrandName === '' || $eskBrandName === 'Eskoofy') ? 'Eskoofy' : $eskBrandName;
$eskColour = (string) ($eskSettings['appearance.brand_color'] ?? '#2563eb');
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $eskColour)) {
    $eskColour = '#2563eb';
}

$eskPath = $_SERVER['REQUEST_URI'] ?? '';
$eskActive = function (array $prefixes) use ($eskPath): bool {
    foreach ($prefixes as $p) {
        if ($eskPath === $p || str_starts_with($eskPath, rtrim($p, '/') . '/') || ($p === '/admin/dashboard' && ($eskPath === '/admin' || $eskPath === '/admin/dashboard'))) {
            return true;
        }
    }
    return false;
};
$eskIcons = [
    'dashboard' => '<svg viewBox="0 0 24 24" width="20" height="20" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>',
    'key' => '<svg viewBox="0 0 24 24" width="20" height="20" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 2-2 2m-7.6 7.6a5.5 5.5 0 1 1-7.8 7.8 5.5 5.5 0 1 1 7.8-7.8Zm0 0L21 2M15.5 6.5l3 3"/></svg>',
    'layers' => '<svg viewBox="0 0 24 24" width="20" height="20" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2 10 6-10 6L2 8l10-6Zm10 12-10 6L2 14m20 0-10 6L2 14"/></svg>',
    'card' => '<svg viewBox="0 0 24 24" width="20" height="20" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>',
    'repeat' => '<svg viewBox="0 0 24 24" width="20" height="20" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m17 2 4 4-4 4M3 11v-1a4 4 0 0 1 4-4h14M7 22l-4-4 4-4M21 13v1a4 4 0 0 1-4 4H3"/></svg>',
    'users' => '<svg viewBox="0 0 24 24" width="20" height="20" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm14 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'file' => '<svg viewBox="0 0 24 24" width="20" height="20" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>',
    'tag' => '<svg viewBox="0 0 24 24" width="20" height="20" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.6 13.4 12 22 2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg>',
    'inbox' => '<svg viewBox="0 0 24 24" width="20" height="20" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-6l-2 3h-4l-2-3H2M5.5 5h13l3.5 7v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6l3.5-7Z"/></svg>',
    'activity' => '<svg viewBox="0 0 24 24" width="20" height="20" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
    'eye' => '<svg viewBox="0 0 24 24" width="20" height="20" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>',
    'sliders' => '<svg viewBox="0 0 24 24" width="20" height="20" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/></svg>',
    'user' => '<svg viewBox="0 0 24 24" width="20" height="20" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>',
    'tool' => '<svg viewBox="0 0 24 24" width="20" height="20" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76Z"/></svg>',
    'archive' => '<svg viewBox="0 0 24 24" width="20" height="20" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="5" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8M10 12h4"/></svg>',
    'plus' => '<svg viewBox="0 0 24 24" width="14" height="14" class="h-3.5 w-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>',
    'globe' => '<svg viewBox="0 0 24 24" width="20" height="20" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/></svg>',
    'logout' => '<svg viewBox="0 0 24 24" width="20" height="20" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>',
];
$eskNavGroups = [
    'Overview' => [
        ['label' => 'Dashboard', 'href' => '/admin', 'icon' => 'dashboard', 'active' => $eskActive(['/admin', '/admin/dashboard'])],
    ],
    'Sales & licensing' => [
        ['label' => 'Licenses', 'href' => '/admin/licenses', 'icon' => 'key', 'active' => $eskActive(['/admin/licenses'])],
        ['label' => 'Plans', 'href' => '/admin/plans', 'icon' => 'layers', 'active' => $eskActive(['/admin/plans'])],
        ['label' => 'Payments', 'href' => '/admin/payments', 'icon' => 'card', 'active' => $eskActive(['/admin/payments'])],
        ['label' => 'Subscriptions', 'href' => '/admin/subscriptions', 'icon' => 'repeat', 'active' => $eskActive(['/admin/subscriptions'])],
        ['label' => 'Packages', 'href' => '/admin/packages', 'icon' => 'archive', 'active' => $eskActive(['/admin/packages'])],
        ['label' => 'Payment gateways', 'href' => '/admin/gateways', 'icon' => 'card', 'active' => $eskActive(['/admin/gateways'])],
        ['label' => 'Deployment & maintenance', 'href' => '/admin/services', 'icon' => 'tool', 'active' => $eskActive(['/admin/services'])],
    ],
    'Customers' => [
        ['label' => 'All customers', 'href' => '/admin/customers', 'icon' => 'users', 'active' => $eskActive(['/admin/customers'])],
    ],
    'Content & inbox' => [
        ['label' => 'Blog posts', 'href' => '/admin/posts', 'icon' => 'file', 'active' => $eskActive(['/admin/posts'])],
        ['label' => 'Post categories', 'href' => '/admin/post-categories', 'icon' => 'tag', 'active' => $eskActive(['/admin/post-categories'])],
        ['label' => 'Messages', 'href' => '/admin/messages', 'icon' => 'inbox', 'active' => $eskActive(['/admin/messages']), 'badge' => $eskUnread ?? 0],
    ],
    'System' => [
        ['label' => 'Visitor log', 'href' => '/admin/visitors', 'icon' => 'eye', 'active' => $eskActive(['/admin/visitors'])],
        ['label' => 'Activity log', 'href' => '/admin/activities', 'icon' => 'activity', 'active' => $eskActive(['/admin/activities'])],
        ['label' => 'Email templates', 'href' => '/admin/email-templates', 'icon' => 'file', 'active' => $eskActive(['/admin/email-templates'])],
        ['label' => 'Client documents', 'href' => '/admin/client-documents', 'icon' => 'file', 'active' => $eskActive(['/admin/client-documents'])],
        ['label' => 'Push notifications', 'href' => '/admin/push-notifications', 'icon' => 'inbox', 'active' => $eskActive(['/admin/push-notifications'])],
        ['label' => 'Clear cache', 'href' => '/admin/cache', 'icon' => 'sliders', 'active' => $eskActive(['/admin/cache'])],
        ['label' => 'Backups', 'href' => '/admin/backup', 'icon' => 'archive', 'active' => $eskActive(['/admin/backup'])],
        ['label' => 'Settings', 'href' => '/admin/settings', 'icon' => 'sliders', 'active' => $eskActive(['/admin/settings'])],
        ['label' => 'My account', 'href' => '/admin/account', 'icon' => 'user', 'active' => $eskActive(['/admin/account'])],
    ],
];
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($adminTitle ?? $eskBrandName . ' Admin') ?> — <?= $eskBrandName ?> Admin</title>
    <meta name="theme-color" content="<?= $eskColour ?>">
    <meta name="robots" content="noindex, nofollow">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($eskBrandName) ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .esk-sidebar-link { transition: background .12s ease, color .12s ease; }
        .admin-sidebar { transition: transform .22s ease; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased">

<?php $__flashes = \App\Core\Session::getInstance()->flashAll(); ?>
<?php if (!empty($__flashes)): ?>
    <div class="fixed top-4 right-4 z-[70] w-full max-w-sm space-y-2 px-4">
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

<div data-admin-backdrop class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm hidden md:hidden"></div>

<div class="min-h-screen flex">

    <aside data-admin-sidebar class="admin-sidebar fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 flex-shrink-0 flex flex-col -translate-x-full md:translate-x-0 md:static">
        <a href="/admin" class="px-6 py-5 text-white flex items-center gap-2.5">
            <img src="/brand/eskofy-mark.svg" alt="<?= $eskBrandName ?>" class="h-10 w-10 drop-shadow-md">
            <span class="font-bold text-lg leading-tight"><?= $eskBrandName ?><br><span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Admin Console</span></span>
        </a>
        <nav class="flex-1 overflow-y-auto px-3 py-2 text-sm font-medium space-y-4">
            <?php foreach ($eskNavGroups as $group => $items): ?>
                <div>
                    <div class="px-3 pb-1.5 text-[10px] uppercase tracking-widest text-slate-500 font-semibold"><?= htmlspecialchars($group) ?></div>
                    <ul class="space-y-0.5">
                        <?php foreach ($items as $it): ?>
                            <li>
                                <a href="<?= htmlspecialchars($it['href']) ?>" class="esk-sidebar-link block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white flex items-center gap-2.5 <?= $it['active'] ? 'bg-slate-800 text-white' : '' ?>">
                                    <?= $eskIcons[$it['icon']] ?? '' ?>
                                    <span class="flex-1"><?= htmlspecialchars($it['label']) ?></span>
                                    <?php if (!empty($it['badge'])): ?>
                                        <span class="bg-red-500 text-white text-[10px] font-bold min-w-[1.25rem] h-5 px-1.5 rounded-full flex items-center justify-center"><?= (int) $it['badge'] ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php if ($group === 'Sales & licensing'): ?>
                    <div class="pt-1">
                        <div class="px-3 pb-1.5 text-[10px] uppercase tracking-widest text-slate-600 font-semibold">Quick create</div>
                        <ul class="space-y-0.5">
                            <?php foreach ([
                                ['label' => 'Issue license', 'href' => '/admin/licenses/create'],
                                ['label' => 'Add plan', 'href' => '/admin/plans/create'],
                                ['label' => 'New post', 'href' => '/admin/posts/create'],
                                ['label' => 'New category', 'href' => '/admin/post-categories/create'],
                            ] as $q): ?>
                                <li>
                                    <a href="<?= htmlspecialchars($q['href']) ?>" class="esk-sidebar-link block px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 flex items-center gap-2">
                                        <?= $eskIcons['plus'] ?>
                                        <?= htmlspecialchars($q['label']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <a href="/" class="esk-sidebar-link block px-3 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 flex items-center gap-2.5">
                <?= $eskIcons['globe'] ?>
                <span>View website</span>
            </a>
        </nav>
        <div class="px-6 py-4 border-t border-slate-800 text-sm space-y-2">
            <div class="text-slate-400 truncate"><?= htmlspecialchars((string) ($admin['name'] ?? 'Admin')) ?></div>
            <a href="/logout" class="flex items-center gap-2 text-slate-500 hover:text-white">
                <?= $eskIcons['logout'] ?>
                Logout
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="bg-white border-b border-slate-200 px-4 md:px-6 py-3 md:py-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <button type="button" data-admin-open aria-label="Open menu" class="md:hidden inline-flex items-center justify-center rounded-lg border border-slate-300 p-2 text-slate-600 hover:text-slate-900">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="font-bold text-base md:text-lg truncate"><?= htmlspecialchars($adminTitle ?? 'Dashboard') ?></h1>
            </div>
            <a href="/" class="text-xs md:text-sm text-blue-600 hover:underline whitespace-nowrap">← Back to site</a>
        </header>

        <main class="flex-1 p-4 md:p-6">
            <?= $contentHtml ?>
        </main>
    </div>

</div>
<script>
(function () {
    var sidebar = document.querySelector('[data-admin-sidebar]');
    var backdrop = document.querySelector('[data-admin-backdrop]');
    var openBtn = document.querySelector('[data-admin-open]');
    if (!sidebar) return;
    function open() {
        sidebar.classList.remove('-translate-x-full');
        if (backdrop) backdrop.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function close() {
        sidebar.classList.add('-translate-x-full');
        if (backdrop) backdrop.classList.add('hidden');
        document.body.style.overflow = '';
    }
    if (openBtn) openBtn.addEventListener('click', open);
    if (backdrop) backdrop.addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    sidebar.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', close); });
})();
</script>
</body>
</html>