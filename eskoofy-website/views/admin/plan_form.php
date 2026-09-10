<?php $adminTitle = $plan ? ('Edit plan — ' . $plan['name']) : 'New plan'; ?>

<div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
    <form method="post" action="<?= $plan ? "/admin/plans/{$plan['id']}" : '/admin/plans' ?>
            <?= csrf_field() ?>" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Product</label>
                <select name="product" class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm">
                    <option value="app" <?= ($plan['product'] ?? '') === 'app' ? 'selected' : '' ?>>app</option>
                    <option value="theme" <?= ($plan['product'] ?? '') === 'theme' ? 'selected' : '' ?>>theme</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Period</label>
                <select name="period" class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm">
                    <?php foreach (['monthly', 'yearly', 'one-time'] as $period): ?>
                        <option value="<?= $period ?>" <?= ($plan['period'] ?? '') === $period ? 'selected' : '' ?>><?= $period ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Name</label>
            <input name="name" value="<?= htmlspecialchars((string) ($plan['name'] ?? '')) ?>" required maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Slug</label>
            <input name="slug" value="<?= htmlspecialchars((string) ($plan['slug'] ?? '')) ?>" required maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Price (USD)</label>
                <input type="number" name="price" value="<?= htmlspecialchars((string) ($plan['price'] ?? '')) ?>" required step="0.01" min="0" class="w-full border border-slate-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Max activations (0 = unlimited)</label>
                <input type="number" name="max_activations" value="<?= (int) ($plan['max_activations'] ?? 3) ?>" min="0" class="w-full border border-slate-300 rounded-lg px-4 py-2">
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Description</label>
            <textarea name="description" rows="2" maxlength="500" class="w-full border border-slate-300 rounded-lg px-4 py-2"><?= htmlspecialchars((string) ($plan['description'] ?? '')) ?></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Sort order</label>
                <input type="number" name="sort_order" value="<?= (int) ($plan['sort_order'] ?? 0) ?>" class="w-full border border-slate-300 rounded-lg px-4 py-2">
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm font-semibold cursor-pointer">
                    <input type="checkbox" name="active" value="1" <?= !empty($plan['active']) ? 'checked' : '' ?> class="w-4 h-4">
                    Active (visible to customers)
                </label>
            </div>
        </div>
        <button class="bg-slate-900 text-white px-6 py-3 rounded-lg font-semibold"><?= $plan ? 'Save plan' : 'Create plan' ?></button>
    </form>
</div>