<?php $adminTitle = 'Posts'; ?>

<div class="flex items-center justify-between mb-4">
    <span class="text-sm text-slate-400"><?= count($posts) ?> post(s)</span>
    <a href="/admin/posts/create" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm">New post</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-400 text-xs uppercase">
            <tr>
                <th class="px-4 py-3">Title</th>
                <th class="px-4 py-3">Category</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Author</th>
                <th class="px-4 py-3">Published</th>
                <th class="px-4 py-3">Views</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($posts)): ?>
            <tr><td colspan="7" class="px-4 py-6 text-center text-slate-400">No posts yet.</td></tr>
        <?php else: foreach ($posts as $post): ?>
            <tr class="border-t border-slate-100">
                <td class="px-4 py-3 font-semibold">
                    <a href="/blog/<?= htmlspecialchars($post['slug']) ?>" class="hover:underline"><?= htmlspecialchars($post['title']) ?></a>
                </td>
                <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars((string) ($post['category_name'] ?? '—')) ?></td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-1 rounded-full <?= $post['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' ?>">
                        <?= htmlspecialchars($post['status']) ?>
                    </span>
                </td>
                <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars((string) ($post['author_name'] ?? '—')) ?></td>
                <td class="px-4 py-3 text-slate-500 text-xs"><?= $post['published_at'] ? date('Y-m-d H:i', strtotime((string) $post['published_at'])) : '—' ?></td>
                <td class="px-4 py-3 text-slate-500 text-xs"><?= (int) $post['views'] ?></td>
                <td class="px-4 py-3 text-right space-x-3 text-sm">
                    <a href="/admin/posts/<?= (int) $post['id'] ?>/edit" class="text-blue-600 hover:underline">Edit</a>
                    <form method="post" action="/admin/posts/<?= (int) $post['id'] ?>/delete" class="inline" onsubmit="return confirm('Delete this post?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>