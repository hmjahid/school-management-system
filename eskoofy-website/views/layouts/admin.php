<!DOCTYPE html>
<?php
$eskSettings = \App\Models\Settings::all();
$eskBrandName = (string) ($eskSettings['site.name'] ?? '');
$eskBrandName = ($eskBrandName === '' || $eskBrandName === 'Eskoofy') ? 'Eskoofy' : $eskBrandName;
$eskColour = (string) ($eskSettings['appearance.brand_color'] ?? '#2563eb');
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $eskColour)) {
    $eskColour = '#2563eb';
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($adminTitle ?? $eskBrandName . ' Admin') ?> — <?= $eskBrandName ?> Admin</title>
    <meta name="theme-color" content="<?= $eskColour ?>">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($eskBrandName) ?>">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 antialiased">

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

<div class="min-h-screen flex">

    <aside class="w-60 bg-slate-900 text-slate-300 flex-shrink-0 hidden md:flex flex-col">
        <a href="/admin" class="px-6 py-5 text-white flex items-center gap-2.5">
            <img src="/brand/eskofy-mark.svg" alt="<?= $eskBrandName ?>" class="h-10 w-10 drop-shadow-md">
            <span class="font-bold text-lg leading-tight"><?= $eskBrandName ?><br><span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Admin Console</span></span>
        </a>
        <nav class="flex-1 px-3 space-y-1 text-sm font-medium">
            <a href="/admin" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/admin/dashboard') || ($_SERVER['REQUEST_URI'] ?? '') === '/admin' ? 'bg-slate-800 text-white' : '' ?>">Dashboard</a>
            <a href="/admin/customers" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Customers</a>
            <a href="/admin/licenses" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Licenses</a>
            <a href="/admin/plans" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Plans</a>
            <a href="/admin/payments" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Payments</a>
            <a href="/admin/subscriptions" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Subscriptions</a>
            <a href="/admin/posts" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Posts</a>
            <a href="/admin/post-categories" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Post categories</a>
            <a href="/admin/messages" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Messages</a>
            <a href="/admin/activities" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Activity log</a>
            <a href="/admin/settings" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Settings</a>
            <a href="/admin/account" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white <?= ($_SERVER['REQUEST_URI'] ?? '') === '/admin/account' ? 'bg-slate-800 text-white' : '' ?>">My account</a>
        </nav>
        <div class="px-6 py-4 border-t border-slate-800 text-sm space-y-1">
            <div class="text-slate-400 truncate"><?= htmlspecialchars((string) ($admin['name'] ?? 'Admin')) ?></div>
            <a href="/logout" class="text-slate-500 hover:text-white">Logout</a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col">
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h1 class="font-bold text-lg"><?= htmlspecialchars($adminTitle ?? 'Dashboard') ?></h1>
            <a href="/" class="text-sm text-blue-600 hover:underline">← Back to site</a>
        </header>

        <main class="flex-1 p-6">
            <?php
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
            <?php if (isset($flashMsg) && $flashMsg !== ''): ?>
                <div class="mb-4 rounded-lg px-4 py-3 text-sm font-medium <?= $flashType === 'success' ? 'bg-green-100 text-green-800' : ($flashType === 'error' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800') ?>">
                    <?= htmlspecialchars($flashMsg) ?>
                </div>
            <?php endif; ?>
            <?= $contentHtml ?>
        </main>
    </div>

</div>
</body>
</html>