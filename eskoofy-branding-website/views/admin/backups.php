<?php $adminTitle = 'Backups'; ?>

<div class="mb-4">
    <h2 class="font-bold text-lg">Create a backup</h2>
    <p class="text-sm text-slate-500 mt-1">Self-contained SQL (and a ZIP with site assets for full backups). Download and store these somewhere safe, off-server.</p>
</div>

<div class="grid md:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-center gap-2 text-blue-600">
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2M4 7v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7M4 7h16M9 12h6"/></svg>
            <h3 class="font-bold">Whole site</h3>
        </div>
        <p class="text-sm text-slate-500 mt-2 mb-4">All database tables plus the public site files (brand, icons, JS) in one ZIP.</p>
        <form method="post" action="/admin/backup/create/full" class="inline">
            <?= csrf_field() ?>
            <button class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2 rounded-lg">Create full backup</button>
        </form>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-center gap-2 text-indigo-600">
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 2-2 2m-7.6 7.6a5.5 5.5 0 1 1-7.8 7.8 5.5 5.5 0 1 1 7.8-7.8Zm0 0L21 2M15.5 6.5l3 3"/></svg>
            <h3 class="font-bold">Licenses</h3>
        </div>
        <p class="text-sm text-slate-500 mt-2 mb-4">Licenses, activations, subscriptions, plans and payments — the licensing data set.</p>
        <form method="post" action="/admin/backup/create/licenses" class="inline">
            <?= csrf_field() ?>
            <button class="bg-slate-900 hover:bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">Create license backup</button>
        </form>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-center gap-2 text-emerald-600">
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm14 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <h3 class="font-bold">Users</h3>
        </div>
        <p class="text-sm text-slate-500 mt-2 mb-4">All customer accounts (admin + customers) — emails, roles and profile data.</p>
        <form method="post" action="/admin/backup/create/users" class="inline">
            <?= csrf_field() ?>
            <button class="bg-slate-900 hover:bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">Create user backup</button>
        </form>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm min-w-[640px]">
        <thead class="bg-slate-50 text-left text-slate-400 text-xs uppercase">
            <tr>
                <th class="px-4 py-3">Backup</th>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3 text-right">Size</th>
                <th class="px-4 py-3">Created</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($backups)): ?>
            <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">No backups yet — create one above.</td></tr>
        <?php else: foreach ($backups as $b): ?>
            <tr class="border-t border-slate-100">
                <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars($b['file']) ?></td>
                <td class="px-4 py-3 capitalize"><?= htmlspecialchars($b['type']) ?><?= $b['full'] ? ' · site' : '' ?></td>
                <td class="px-4 py-3 text-right"><?= number_format((float) ($b['size'] / 1024), 1) ?> KB</td>
                <td class="px-4 py-3"><?= htmlspecialchars(date('Y-m-d H:i', (int) $b['created'])) ?></td>
                <td class="px-4 py-3">
                    <div class="flex justify-end gap-2">
                        <a href="/admin/backup/download?file=<?= urlencode($b['file']) ?>" class="text-xs bg-blue-600 hover:bg-blue-500 text-white px-3 py-1.5 rounded-lg font-semibold">Download</a>
                        <form method="post" action="/admin/backup/delete" onsubmit="return confirm('Delete this backup?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="file" value="<?= htmlspecialchars($b['file']) ?>">
                            <button class="text-xs border border-red-200 text-red-600 hover:bg-red-50 px-3 py-1.5 rounded-lg font-semibold">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>