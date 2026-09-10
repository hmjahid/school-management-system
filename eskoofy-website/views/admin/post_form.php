<?php $adminTitle = $post ? ('Edit post — ' . $post['title']) : 'New post'; ?>

<div class="max-w-3xl bg-white rounded-xl border border-slate-200 p-6">
    <form method="post" action="<?= $post ? "/admin/posts/{$post['id']}" : '/admin/posts' ?>" class="space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="block text-sm font-semibold mb-1">Title</label>
            <input name="title" value="<?= htmlspecialchars((string) ($post['title'] ?? '')) ?>" required maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Slug</label>
                <input name="slug" value="<?= htmlspecialchars((string) ($post['slug'] ?? '')) ?>" required maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2 font-mono text-sm">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Category</label>
                <select name="category_id" class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm">
                    <option value="">— none —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>" <?= (int) ($post['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Excerpt</label>
            <textarea name="excerpt" rows="2" maxlength="400" class="w-full border border-slate-300 rounded-lg px-4 py-2"><?= htmlspecialchars((string) ($post['excerpt'] ?? '')) ?></textarea>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Content (HTML allowed)</label>
            <textarea name="content" rows="14" required class="w-full border border-slate-300 rounded-lg px-4 py-2 font-mono text-sm"><?= htmlspecialchars((string) ($post['content'] ?? '')) ?></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Featured image URL</label>
                <input name="featured_image" value="<?= htmlspecialchars((string) ($post['featured_image'] ?? '')) ?>" class="w-full border border-slate-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Status</label>
                <select name="status" class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm">
                    <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>draft</option>
                    <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>published</option>
                </select>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Meta title</label>
                <input name="meta_title" value="<?= htmlspecialchars((string) ($post['meta_title'] ?? '')) ?>" maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Published at (empty = now on publish)</label>
                <input type="datetime-local" name="published_at" value="<?= $post['published_at'] ? date('Y-m-d\TH:i', strtotime((string) $post['published_at'])) : '' ?>" class="w-full border border-slate-300 rounded-lg px-4 py-2">
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Meta description</label>
            <input name="meta_description" value="<?= htmlspecialchars((string) ($post['meta_description'] ?? '')) ?>" maxlength="255" class="w-full border border-slate-300 rounded-lg px-4 py-2">
        </div>
        <button class="bg-slate-900 text-white px-6 py-3 rounded-lg font-semibold"><?= $post ? 'Save post' : 'Create post' ?></button>
    </form>
</div>