<?php $adminTitle = 'Product packages'; ?>

<div class="mb-6 grid md:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="font-bold mb-1">Upload a package</h3>
        <p class="text-sm text-slate-500 mb-4">Publish a product ZIP (app / php / theme) that licensed clients can download and that you can email to them.</p>
        <form method="post" action="/admin/packages" enctype="multipart/form-data" class="space-y-3">
            <?= csrf_field() ?>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">Product</label>
                    <select name="product" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="app">App</option>
                        <option value="php">Raw PHP</option>
                        <option value="theme">WordPress theme</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Version</label>
                    <input name="version" placeholder="e.g. 1.2.0" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">ZIP file</label>
                <input type="file" name="package" accept=".zip" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Release notes</label>
                <textarea name="notes" rows="2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>
            <button class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2 rounded-lg">Upload package</button>
        </form>
    </div>
    <div class="md:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="font-bold mb-4">Packages</h3>
        <?php if (empty($packages)): ?>
            <p class="text-sm text-slate-400">No packages yet — upload the first one.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead class="bg-slate-50 text-left text-slate-400 text-xs uppercase">
                        <tr>
                            <th class="px-3 py-2">Product</th>
                            <th class="px-3 py-2">Version</th>
                            <th class="px-3 py-2">File</th>
                            <th class="px-3 py-2 text-right">Size</th>
                            <th class="px-3 py-2 text-right">Licensed clients</th>
                            <th class="px-3 py-2">Active</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($packages as $p): ?>
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-2.5 capitalize font-semibold"><?= htmlspecialchars($p['product']) ?></td>
                            <td class="px-3 py-2.5"><?= htmlspecialchars($p['version']) ?></td>
                            <td class="px-3 py-2.5 font-mono text-xs"><?= htmlspecialchars($p['filename']) ?></td>
                            <td class="px-3 py-2.5 text-right"><?= number_format((float) ($p['size'] / 1048576), 2) ?> MB</td>
                            <td class="px-3 py-2.5 text-right"><?= (int) $p['active_licenses'] ?></td>
                            <td class="px-3 py-2.5"><?= $p['is_active'] ? '<span class="text-green-600 font-semibold">●</span>' : '<span class="text-slate-300 font-semibold">●</span>' ?></td>
                            <td class="px-3 py-2.5">
                                <div class="flex justify-end gap-1.5 flex-wrap">
                                    <form method="post" action="/admin/packages/<?= (int) $p['id'] ?>/send" class="inline">
                                        <?= csrf_field() ?>
                                        <button title="Email download link to licensed clients" class="text-xs bg-blue-600 hover:bg-blue-500 text-white px-2.5 py-1.5 rounded-lg font-semibold">Send</button>
                                    </form>
                                    <a href="/admin/packages/<?= (int) $p['id'] ?>/download" class="text-xs border border-slate-300 hover:bg-slate-50 px-2.5 py-1.5 rounded-lg font-semibold">↓</a>
                                    <form method="post" action="/admin/packages/<?= (int) $p['id'] ?>/toggle" class="inline">
                                        <?= csrf_field() ?>
                                        <button class="text-xs border border-slate-300 hover:bg-slate-50 px-2.5 py-1.5 rounded-lg font-semibold"><?= $p['is_active'] ? 'Disable' : 'Enable' ?></button>
                                    </form>
                                    <form method="post" action="/admin/packages/<?= (int) $p['id'] ?>/delete" onsubmit="return confirm('Delete this package?')" class="inline">
                                        <?= csrf_field() ?>
                                        <button class="text-xs border border-red-200 text-red-600 hover:bg-red-50 px-2.5 py-1.5 rounded-lg font-semibold">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>