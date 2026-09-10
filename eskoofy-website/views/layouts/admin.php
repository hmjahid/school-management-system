<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($adminTitle ?? 'Eskoofy Admin') ?> — Eskoofy Admin</title>
    <meta name="theme-color" content="#2563eb">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Eskoofy">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
<div class="min-h-screen flex">

    <aside class="w-60 bg-slate-900 text-slate-300 flex-shrink-0 hidden md:flex flex-col">
        <a href="/admin" class="px-6 py-5 font-bold text-white text-lg flex items-center gap-2">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 font-black">E</span>
            Eskoofy Admin
        </a>
        <nav class="flex-1 px-3 space-y-1 text-sm font-medium">
            <a href="/admin" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/admin/dashboard') || ($_SERVER['REQUEST_URI'] ?? '') === '/admin' ? 'bg-slate-800 text-white' : '' ?>">Dashboard</a>
            <a href="/admin/customers" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Customers</a>
            <a href="/admin/licenses" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Licenses</a>
            <a href="/admin/plans" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Plans</a>
            <a href="/admin/payments" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Payments</a>
            <a href="/admin/posts" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Posts</a>
            <a href="/admin/post-categories" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Post categories</a>
            <a href="/admin/messages" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Messages</a>
            <a href="/admin/activities" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white">Activity log</a>
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