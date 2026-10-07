<?php
declare(strict_types=1);

/**
 * The admin app shell.
 *
 * Loaded by every `/admin/*` page. Wires the design system
 * (`public/css/admin.css` + `public/js/admin.js` + `public/js/charts.js`), the
 * sidebar (from {@see \App\Services\Nav}), the topbar (search, range, theme,
 * notifications) and the Ctrl+K command palette.
 *
 * `data-theme` is applied before first paint by the inline snippet in <head>,
 * so a dark-mode user never sees a flash of the light theme.
 *
 * @var string $contentHtml rendered page body (injected by App\Core\Controller)
 * @var string|null $adminTitle
 * @var array<string, mixed>|null $admin
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §6.1
 */

$eskSettings = \App\Models\Settings::all();
$eskBrandName = trim((string) ($eskSettings['site.name'] ?? ''));
if ($eskBrandName === '' || $eskBrandName === 'Eskoofy') {
    $eskBrandName = 'Eskoofy';
}
$eskColour = (string) ($eskSettings['appearance.brand_color'] ?? '#2563eb');
if (!preg_match('/^[0-9a-fA-F]{6}$/', ltrim($eskColour, '#'))) {
    $eskColour = '#2563eb';
}
if ($eskColour[0] !== '#') {
    $eskColour = '#' . $eskColour;
}
$eskDarkDefault = match ((string) ($eskSettings['appearance.dark_default'] ?? '0')) {
    '1'          => 'dark',
    'light'      => 'light',
    default      => 'system',
};

$eskIcons = [
    'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
    'key' => '<path d="m21 2-2 2m-7.6 7.6a5.5 5.5 0 1 1-7.8 7.8 5.5 5.5 0 1 1 7.8-7.8Zm0 0L21 2M15.5 6.5l3 3"/>',
    'layers' => '<path d="m12 2 10 6-10 6L2 8l10-6Zm10 12-10 6L2 14m20 0-10 6L2 14"/>',
    'card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
    'repeat' => '<path d="m17 2 4 4-4 4M3 11v-1a4 4 0 0 1 4-4h14M7 22l-4-4 4-4M21 13v1a4 4 0 0 1-4 4H3"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm14 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>',
    'tag' => '<path d="M20.6 13.4 12 22 2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
    'layout' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
    'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2M5.5 5h13l3.5 7v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6l3.5-7Z"/>',
    'activity' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
    'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
    'sliders' => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
    'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>',
    'tool' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76Z"/>',
    'archive' => '<rect x="2" y="3" width="20" height="5" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8M10 12h4"/>',
    'plus' => '<path d="M12 5v14M5 12h14"/>',
    'globe' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/>',
    'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
    'chart' => '<path d="M3 3v18h18"/><path d="m7 14 3-3 3 3 5-6"/>',
    'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/>',
    'search' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
    'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
    'rail' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/>',
    'moon' => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
    'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
    'monitor' => '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>',
];

$eskSvg = static function (string $name, int $size = 20, string $class = '') use ($eskIcons): string {
    $path = $eskIcons[$name] ?? $eskIcons['activity'];
    return '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" class="' . $class . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
};

$eskUnread = (int) \App\Models\ContactMessage::countUnread();
$eskCustomUnread = (int) \App\Models\CustomRequest::countUnread();
$eskExpiring = \App\Models\License::countExpiringSoon(30);

$eskNavGroups = \App\Services\Nav::groups(null, [
    'messages'          => $eskUnread,
    'custom_requests'    => $eskCustomUnread,
    'expiring_licenses' => $eskExpiring,
]);
$eskGroupLabels = [
    'admin.nav.group_overview'  => 'Overview',
    'admin.nav.group_sales'     => 'Sales & licensing',
    'admin.nav.group_customers' => 'Customers & inbox',
    'admin.nav.group_analytics' => 'Analytics',
    'admin.nav.group_content'   => 'Content',
    'admin.nav.group_system'    => 'System',
];

// One glanceable "needs attention" roll-up: unread inbox + custom orders +
// licenses expiring within 30 days. Rendered at the very top of the nav and
// linking to the dashboard, whose alert strip explains each item.
$eskAttention = $eskUnread + $eskCustomUnread + $eskExpiring;

$eskCurrent = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/admin'), PHP_URL_PATH) ?: '/admin';
$eskQuery = $_GET;
unset($eskQuery['range'], $eskQuery['from'], $eskQuery['to']);
$eskCarry = http_build_query(array_filter($eskQuery, static fn ($v): bool => $v !== null && $v !== ''));
$eskRangePresets = [
    'today' => 'Today', '7d' => '7d', '30d' => '30d', '90d' => '90d',
    '12mo' => '12mo', 'ytd' => 'YTD', 'all' => 'All',
];
$eskActiveRange = (string) ($_GET['range'] ?? '30d');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(\App\Services\I18n::current()) ?>" data-theme-default="<?= htmlspecialchars($eskDarkDefault) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars(($adminTitle ?? 'Dashboard') . ' — ' . $eskBrandName . ' Admin') ?></title>
    <meta name="theme-color" content="<?= htmlspecialchars($eskColour) ?>">
    <meta name="robots" content="noindex, nofollow">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($eskBrandName) ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        surface: 'var(--surface)', 'surface-2': 'var(--surface-2)', 'surface-3': 'var(--surface-3)',
                        ink: 'var(--text)', 'ink-muted': 'var(--text-muted)', 'ink-subtle': 'var(--text-subtle)',
                        edge: 'var(--border)', 'edge-strong': 'var(--border-strong)',
                    },
                    borderRadius: { card: 'var(--radius)', 'card-sm': 'var(--radius-sm)' },
                    boxShadow: { card: 'var(--shadow-1)', 'card-lg': 'var(--shadow-2)' },
                },
            },
        };
    </script>

    <link rel="stylesheet" href="/css/admin.css?v=8">

    <script>
        // Applied before first paint so dark-mode users never see a light flash.
        (function () {
            try {
                var stored = localStorage.getItem('esk_admin_theme');
                var pref = stored || <?= json_encode($eskDarkDefault) ?>;
                var resolved = pref === 'system' || !pref
                    ? (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                    : pref;
                document.documentElement.setAttribute('data-theme', resolved);
            } catch (e) {}
        })();
    </script>

    <script defer src="/vendor/charts/chart.umd.min.js"></script>
    <script defer src="/vendor/charts/apexcharts.min.js"></script>
    <script defer src="/js/charts.js?v=3"></script>
    <script defer src="/js/admin.js?v=3"></script>
</head>
<body class="antialiased">

<a href="#esk-main" class="esk-sr-only">Skip to content</a>

<?php $__flashes = \App\Core\Session::getInstance()->flashAll(); ?>
<?php if (!empty($__flashes)): ?>
    <div class="fixed top-4 right-4 z-[70] w-full max-w-sm space-y-2 px-4" aria-live="polite">
        <?php foreach ((array) ($__flashes['success'] ?? []) as $__msg): ?>
            <div class="esk-alert esk-alert--success shadow-lg" role="status"><?= htmlspecialchars((string) $__msg) ?></div>
        <?php endforeach; foreach ((array) ($__flashes['error'] ?? []) as $__msg): ?>
            <div class="esk-alert esk-alert--danger shadow-lg" role="alert"><?= htmlspecialchars((string) $__msg) ?></div>
        <?php endforeach; if (!empty($__flashes['errors'])): ?>
            <div class="esk-alert esk-alert--danger shadow-lg" role="alert">
                <div>
                    <?php foreach ((array) $__flashes['errors'] as $__errs): ?>
                        <?php foreach ((array) $__errs as $__err): ?>
                            <div><?= htmlspecialchars((string) $__err) ?></div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div data-admin-backdrop class="esk-backdrop" hidden></div>

<div class="esk-shell">

    <aside data-admin-sidebar data-open="false" class="esk-sidebar" aria-label="Admin navigation">
        <a href="/admin" class="esk-sidebar-brand">
            <img src="/brand/eskofy-mark.svg" alt="" class="h-9 w-9 flex-shrink-0 drop-shadow-md">
            <span class="esk-sidebar-label leading-tight">
                <span class="block font-bold text-base text-white"><?= htmlspecialchars($eskBrandName) ?></span>
                <span class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400">Admin Console</span>
            </span>
        </a>

        <div class="esk-sidebar-scrollwrap">
            <nav class="esk-sidebar-nav">
                <?php if ($eskAttention > 0): ?>
                    <a href="/admin" class="esk-nav-attention" data-nav-label="<?= $eskAttention ?> things need attention">
                        <?= $eskSvg('bell', 15) ?>
                        <span class="esk-sidebar-label">
                            <strong class="esk-tabular"><?= $eskAttention ?></strong> thing<?= $eskAttention === 1 ? '' : 's' ?> need<?= $eskAttention === 1 ? 's' : '' ?> attention
                        </span>
                        <span class="esk-nav-attention-arrow" aria-hidden="true">→</span>
                    </a>
                <?php endif; ?>

                <?php foreach ($eskNavGroups as $groupKey => $items): ?>
                    <div class="esk-nav-group-label"><?= htmlspecialchars($eskGroupLabels[$groupKey] ?? 'Menu') ?></div>
                    <ul class="space-y-0.5 mb-1">
                        <?php foreach ($items as $it): ?>
                            <?php
                            $eskTooltip = (string) $it['label'];
                            if (!empty($it['badge'])) {
                                $eskTooltip .= ' · ' . $it['badge'] . ' unread';
                            }
                            ?>
                            <li>
                                <a href="<?= htmlspecialchars((string) $it['href']) ?>"
                                   class="esk-nav-link"
                                   title="<?= htmlspecialchars((string) $it['label']) ?>"
                                   data-nav-label="<?= htmlspecialchars($eskTooltip) ?>"
                                   <?= !empty($it['active']) ? 'aria-current="page"' : '' ?>>
                                    <?= $eskSvg((string) $it['icon']) ?>
                                    <span class="esk-sidebar-label flex-1"><?= htmlspecialchars((string) $it['label']) ?></span>
                                    <?php if (!empty($it['dot'])): ?>
                                        <span class="esk-nav-dot" title="Licenses expiring soon" aria-hidden="true"></span>
                                        <span class="esk-sr-only">licenses expiring soon</span>
                                    <?php endif; ?>
                                    <?php if (!empty($it['badge'])): ?>
                                        <span class="esk-nav-badge"><?= htmlspecialchars((string) $it['badge']) ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($groupKey === 'admin.nav.group_sales'): ?>
                        <div class="esk-nav-group-label">Quick create</div>
                        <ul class="space-y-0.5 mb-1">
                            <?php foreach (\App\Services\Nav::createActions() as $q): ?>
                                <li>
                                    <a href="<?= htmlspecialchars((string) $q['href']) ?>" class="esk-nav-link" title="<?= htmlspecialchars((string) $q['label']) ?>" data-nav-label="<?= htmlspecialchars((string) $q['label']) ?>">
                                        <?= $eskSvg('plus', 16) ?>
                                        <span class="esk-sidebar-label"><?= htmlspecialchars((string) $q['label']) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endforeach; ?>

                <a href="/" class="esk-nav-link mt-1" title="View website" data-nav-label="View website">
                    <?= $eskSvg('globe') ?>
                    <span class="esk-sidebar-label">View website</span>
                </a>
            </nav>

            <?php /*
                Custom scrollbar indicator. Purely decorative (aria-hidden):
                the nav above keeps native scrolling, keyboard and all. JS in
                public/js/admin.js sizes the thumb, drags it, and auto-reveals
                it on scroll/hover. `hidden` until JS proves the nav actually
                scrolls; on coarse pointers it never appears at all.
                The wrapper exists so the track can sit beside the scrollport
                without scrolling away with the content.
             */ ?>
            <div class="esk-scrollbar" data-sidebar-scrollbar aria-hidden="true" hidden>
                <div class="esk-scrollbar-thumb" data-sidebar-thumb></div>
            </div>
        </div>

        <div class="esk-sidebar-foot">
            <div class="esk-sidebar-meta text-slate-400 truncate"><?= htmlspecialchars((string) ($admin['name'] ?? $admin['email'] ?? 'Admin')) ?></div>
            <a href="/logout" class="esk-nav-link" title="Logout" data-nav-label="Logout">
                <?= $eskSvg('logout') ?>
                <span class="esk-sidebar-label">Logout</span>
            </a>
        </div>
    </aside>

    <div class="esk-main">
        <header class="esk-topbar">
            <button type="button" data-admin-open aria-label="Open navigation" class="esk-icon-btn md:hidden">
                <?= $eskSvg('menu') ?>
            </button>
            <button type="button" data-admin-rail aria-label="Collapse sidebar" class="esk-icon-btn esk-hide-sm hidden md:inline-grid">
                <?= $eskSvg('rail') ?>
            </button>

            <h1 class="esk-topbar-title"><?= htmlspecialchars((string) ($adminTitle ?? 'Dashboard')) ?></h1>

            <button type="button" data-palette-open class="esk-search-trigger">
                <?= $eskSvg('search', 16) ?>
                <span class="esk-hide-sm">Search…</span>
                <span class="esk-kbd">⌘K</span>
            </button>

            <span class="esk-topbar-spacer"></span>

            <form method="get" action="<?= htmlspecialchars($eskCurrent) ?>" class="flex items-center gap-2">
                <?php if ($eskCarry !== ''): ?>
                    <?php foreach ($eskQuery as $k => $v): ?>
                        <?php if (is_scalar($v) && $v !== null && $v !== ''): ?>
                            <input type="hidden" name="<?= htmlspecialchars((string) $k) ?>" value="<?= htmlspecialchars((string) $v) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <label class="esk-sr-only" for="esk-global-range">Date range</label>
                <select id="esk-global-range" name="range" data-range-input class="esk-select">
                    <?php foreach ($eskRangePresets as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= $eskActiveRange === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <div class="esk-seg" role="group" aria-label="Colour theme">
                <button type="button" data-theme-set="system" class="esk-seg-item" aria-pressed="false" title="System theme"><?= $eskSvg('monitor', 15) ?><span class="esk-sr-only">System</span></button>
                <button type="button" data-theme-set="light" class="esk-seg-item" aria-pressed="false" title="Light theme"><?= $eskSvg('sun', 15) ?><span class="esk-sr-only">Light</span></button>
                <button type="button" data-theme-set="dark" class="esk-seg-item" aria-pressed="false" title="Dark theme"><?= $eskSvg('moon', 15) ?><span class="esk-sr-only">Dark</span></button>
            </div>

            <details class="esk-menu">
                <summary class="esk-icon-btn relative" aria-label="Notifications">
                    <?= $eskSvg('bell') ?>
                    <?php if ($eskUnread + $eskCustomUnread > 0): ?>
                        <span class="esk-bell-dot" aria-hidden="true"></span>
                    <?php endif; ?>
                </summary>
                <div class="esk-menu-panel">
                    <div class="esk-menu-title">Inbox</div>
                    <a class="esk-menu-row" href="/admin/messages"><?= $eskSvg('inbox', 16) ?> Unread messages <span class="esk-menu-count"><?= $eskUnread ?></span></a>
                    <a class="esk-menu-row" href="/admin/custom-requests"><?= $eskSvg('inbox', 16) ?> Custom orders <span class="esk-menu-count"><?= $eskCustomUnread ?></span></a>
                    <a class="esk-menu-row" href="/admin/licenses?status=expiring"><?= $eskSvg('key', 16) ?> Expiring licenses <span class="esk-menu-count"><?= $eskExpiring ?></span></a>
                </div>
            </details>

            <details class="esk-menu">
                <summary class="esk-icon-btn" aria-label="Account menu">
                    <?= $eskSvg('user') ?>
                </summary>
                <div class="esk-menu-panel">
                    <div class="esk-menu-title"><?= htmlspecialchars((string) ($admin['email'] ?? 'Admin')) ?></div>
                    <a class="esk-menu-row" href="/admin/account"><?= $eskSvg('user', 16) ?> My account</a>
                    <a class="esk-menu-row" href="/admin/settings"><?= $eskSvg('sliders', 16) ?> Settings</a>
                    <a class="esk-menu-row" href="/"><?= $eskSvg('globe', 16) ?> View website</a>
                    <a class="esk-menu-row" href="/logout"><?= $eskSvg('logout', 16) ?> Logout</a>
                </div>
            </details>
        </header>

        <main id="esk-main" class="esk-content">
            <?= $contentHtml ?>
        </main>
    </div>
</div>

<?php \App\Core\View::partial('admin.partials.command_palette'); ?>

</body>
</html>
