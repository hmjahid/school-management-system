<?php $adminTitle = $category ? ('Edit category — ' . $category['name']) : 'New category'; ?>

<div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
    <form method="post" action="<?= $category ? "/admin/post-categories/{$category['id']}" : '/admin/post-categories' ?>" class="space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="block text-sm font-semibold mb-1">Name</label>
            <input name="name" value="<?= htmlspecialchars((string) ($category['name'] ?? '')) ?>" required maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Slug</label>
            <input name="slug" value="<?= htmlspecialchars((string) ($category['slug'] ?? '')) ?>" required maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2 font-mono text-sm">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Description</label>
            <textarea name="description" rows="2" maxlength="400" class="w-full border border-slate-300 rounded-lg px-4 py-2"><?= htmlspecialchars((string) ($category['description'] ?? '')) ?></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Sort order</label>
                <input type="number" name="sort_order" value="<?= (int) ($category['sort_order'] ?? 0) ?>" class="w-full border border-slate-300 rounded-lg px-4 py-2">
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm font-semibold cursor-pointer">
                    <input type="checkbox" name="active" value="1" <?= !isset($category) || !empty($category['active']) ? 'checked' : '' ?> class="w-4 h-4">
                    Active (visible publicly)
                </label>
            </div>
        </div>
        <button class="bg-slate-900 text-white px-6 py-3 rounded-lg font-semibold"><?= $category ? 'Save category' : 'Create category' ?></button>
    </form>
</div>