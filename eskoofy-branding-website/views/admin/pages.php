<?php $adminTitle = 'Pages'; ?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <span class="text-sm text-slate-400"><?= count($pages) ?> page(s) · SEO + content for the public site</span>
    <a href="/admin/pages/create" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm">New page</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm min-w-[820px]">
        <thead class="bg-slate-50 text-left text-slate-400 text-xs uppercase">
            <tr>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3">Route</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Noindex</th>
                <th class="px-4 py-3">Updated</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($pages)): ?>
            <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">No pages yet.</td></tr>
        <?php else: foreach ($pages as $page): ?>
            <tr class="border-t border-slate-100">
                <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($page['name']) ?></td>
                <td class="px-4 py-3">
                    <a href="<?= htmlspecialchars($page['route']) ?>" target="_blank" rel="noopener" class="font-mono text-xs text-blue-600 hover:underline">
                        <?= htmlspecialchars($page['route']) ?>
                    </a>
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-1 rounded-full <?= $page['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' ?>">
                        <?= htmlspecialchars($page['status']) ?>
                    </span>
                </td>
                <td class="px-4 py-3 text-slate-500 text-xs"><?= (int) $page['noindex'] === 1 ? 'yes' : 'no' ?></td>
                <td class="px-4 py-3 text-slate-500 text-xs"><?= $page['updated_at'] ? date('Y-m-d H:i', strtotime((string) $page['updated_at'])) : '—' ?></td>
                <td class="px-4 py-3 text-right space-x-3 text-sm">
                    <a href="/admin/pages/<?= (int) $page['id'] ?>/edit" class="text-blue-600 hover:underline">Edit</a>
                    <form method="post" action="/admin/pages/<?= (int) $page['id'] ?>/delete" class="inline" onsubmit="return confirm('Delete this page record?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>