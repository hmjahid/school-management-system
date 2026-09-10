<?php $adminTitle = 'Post categories'; ?>

<div class="flex items-center justify-between mb-4">
    <span class="text-sm text-slate-400"><?= count($categories) ?> category(ies)</span>
    <a href="/admin/post-categories/create" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm">New category</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-400 text-xs uppercase">
            <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Slug</th><th class="px-4 py-3">Sort</th><th class="px-4 py-3">Active</th><th class="px-4 py-3"></th></tr>
        </thead>
        <tbody>
        <?php if (empty($categories)): ?>
            <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">No categories yet.</td></tr>
        <?php else: foreach ($categories as $cat): ?>
            <tr class="border-t border-slate-100">
                <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($cat['name']) ?></td>
                <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars($cat['slug']) ?></td>
                <td class="px-4 py-3"><?= (int) $cat['sort_order'] ?></td>
                <td class="px-4 py-3"><?= $cat['active'] ? 'yes' : 'no' ?></td>
                <td class="px-4 py-3 text-right space-x-3 text-sm">
                    <a href="/admin/post-categories/<?= (int) $cat['id'] ?>/edit" class="text-blue-600 hover:underline">Edit</a>
                    <form method="post" action="/admin/post-categories/<?= (int) $cat['id'] ?>/delete" class="inline" onsubmit="return confirm('Delete this category?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>