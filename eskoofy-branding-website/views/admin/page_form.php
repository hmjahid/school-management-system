<?php $adminTitle = $page ? ('Edit page — ' . $page['name']) : 'New page'; ?>

<div class="max-w-4xl bg-white rounded-xl border border-slate-200 p-6">
    <form method="post" action="<?= $page ? "/admin/pages/{$page['id']}" : '/admin/pages' ?>" class="space-y-5">
        <?= csrf_field() ?>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Name</label>
                <input name="name" value="<?= htmlspecialchars((string) ($page['name'] ?? '')) ?>" required maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Route</label>
                <input name="route" value="<?= htmlspecialchars((string) ($page['route'] ?? '')) ?>" required maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2 font-mono text-sm">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Sort order</label>
                <input type="number" name="sort_order" value="<?= (int) ($page['sort_order'] ?? 0) ?>" class="w-full border border-slate-300 rounded-lg px-4 py-2">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Status</label>
                <select name="status" class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm">
                    <option value="active" <?= ($page['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>active</option>
                    <option value="draft" <?= ($page['status'] ?? '') === 'draft' ? 'selected' : '' ?>>draft</option>
                </select>
            </div>
            <div class="sm:col-span-2 flex items-end gap-2 pb-1">
                <label class="flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="noindex" value="1" <?= (int) ($page['noindex'] ?? 0) === 1 ? 'checked' : '' ?> class="h-4 w-4">
                    Add noindex / nofollow robots meta
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php foreach (['en' => 'English', 'bn' => 'বাংলা'] as $code => $label): ?>
            <fieldset class="border border-slate-200 rounded-lg p-4 space-y-3">
                <legend class="text-xs font-bold uppercase tracking-wide text-slate-500 px-1"><?= $label ?></legend>
                <div>
                    <label class="block text-sm font-semibold mb-1">Hero heading <span class="text-xs text-slate-400">(blank = default)</span></label>
                    <input name="heading_<?= $code ?>" value="<?= htmlspecialchars((string) ($page['heading_' . $code] ?? '')) ?>" maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Title tag</label>
                    <input name="title_<?= $code ?>" value="<?= htmlspecialchars((string) ($page['title_' . $code] ?? '')) ?>" maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2 font-mono text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Intro</label>
                    <textarea name="intro_<?= $code ?>" rows="3" class="w-full border border-slate-300 rounded-lg px-4 py-2"><?= htmlspecialchars((string) ($page['intro_' . $code] ?? '')) ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Page content (HTML allowed)</label>
                    <textarea name="content_<?= $code ?>" rows="8" class="w-full border border-slate-300 rounded-lg px-4 py-2 font-mono text-sm"><?= htmlspecialchars((string) ($page['content_' . $code] ?? '')) ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Meta title</label>
                    <input name="meta_title_<?= $code ?>" value="<?= htmlspecialchars((string) ($page['meta_title_' . $code] ?? '')) ?>" maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Meta description</label>
                    <textarea name="meta_description_<?= $code ?>" rows="2" maxlength="255" class="w-full border border-slate-300 rounded-lg px-4 py-2"><?= htmlspecialchars((string) ($page['meta_description_' . $code] ?? '')) ?></textarea>
                </div>
            </fieldset>
            <?php endforeach; ?>
        </div>

        <fieldset class="border border-slate-200 rounded-lg p-4 space-y-3">
            <legend class="text-xs font-bold uppercase tracking-wide text-slate-500 px-1">Shared SEO</legend>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-1">
                    <label class="block text-sm font-semibold mb-1">Canonical URL</label>
                    <input name="canonical" value="<?= htmlspecialchars((string) ($page['canonical'] ?? '')) ?>" maxlength="255" placeholder="https://eskoofy.com/..." class="w-full border border-slate-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Hreflang en URL</label>
                    <input name="hreflang_en" value="<?= htmlspecialchars((string) ($page['hreflang_en'] ?? '')) ?>" maxlength="255" placeholder=".../en/about" class="w-full border border-slate-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Hreflang bn URL</label>
                    <input name="hreflang_bn" value="<?= htmlspecialchars((string) ($page['hreflang_bn'] ?? '')) ?>" maxlength="255" placeholder=".../bn/about" class="w-full border border-slate-300 rounded-lg px-4 py-2">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">JSON-LD schema <span class="text-xs text-slate-400">(raw JSON object; appended to the defaults)</span></label>
                <textarea name="json_schema" rows="6" placeholder='{"@type":"WebPage","name":"About Eskoofy"}' class="w-full border border-slate-300 rounded-lg px-4 py-2 font-mono text-sm"><?= htmlspecialchars((string) ($page['json_schema'] ?? '')) ?></textarea>
            </div>
        </fieldset>

        <button class="bg-slate-900 text-white px-6 py-3 rounded-lg font-semibold"><?= $page ? 'Save page' : 'Create page' ?></button>
    </form>
</div>