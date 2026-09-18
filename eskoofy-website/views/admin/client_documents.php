<?php $adminTitle = 'Client documents'; ?>

<div class="mb-6 grid md:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="font-bold mb-1">Upload a document</h3>
        <p class="text-sm text-slate-500 mb-4">Publish help files — user manuals and setup guides — that licensed clients can download and that you can email to them.</p>
        <form method="post" action="/admin/client-documents" enctype="multipart/form-data" class="space-y-3">
            <?= csrf_field() ?>
            <div>
                <label class="block text-xs font-semibold mb-1">Title</label>
                <input name="title" placeholder="e.g. User Manual v2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Type</label>
                <select name="kind" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <option value="user_manual">User manual</option>
                    <option value="setup_guide">Setup guide</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">File</label>
                <input type="file" name="document" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>
            <button class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2 rounded-lg">Upload document</button>
        </form>
    </div>
    <div class="md:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="font-bold mb-4">Documents</h3>
        <?php if (empty($documents)): ?>
            <p class="text-sm text-slate-400">No documents yet — upload the user manual and setup guide.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead class="bg-slate-50 text-left text-slate-400 text-xs uppercase">
                        <tr>
                            <th class="px-3 py-2">Title</th>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2">File</th>
                            <th class="px-3 py-2 text-right">Size</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($documents as $d): ?>
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-2.5 font-semibold"><?= htmlspecialchars($d['title']) ?></td>
                            <td class="px-3 py-2.5 capitalize"><?= htmlspecialchars(str_replace('_', ' ', (string) $d['kind'])) ?></td>
                            <td class="px-3 py-2.5 font-mono text-xs"><?= htmlspecialchars($d['filename']) ?></td>
                            <td class="px-3 py-2.5 text-right"><?= number_format((float) ($d['size'] / 1024), 1) ?> KB</td>
                            <td class="px-3 py-2.5">
                                <div class="flex justify-end gap-1.5 flex-wrap">
                                    <form method="post" action="/admin/client-documents/<?= (int) $d['id'] ?>/send" class="inline">
                                        <?= csrf_field() ?>
                                        <button title="Email download link to all licensed clients" class="text-xs bg-blue-600 hover:bg-blue-500 text-white px-2.5 py-1.5 rounded-lg font-semibold">Send</button>
                                    </form>
                                    <a href="/admin/client-documents/<?= (int) $d['id'] ?>/download" class="text-xs border border-slate-300 hover:bg-slate-50 px-2.5 py-1.5 rounded-lg font-semibold">↓</a>
                                    <form method="post" action="/admin/client-documents/<?= (int) $d['id'] ?>/delete" onsubmit="return confirm('Delete this document?')" class="inline">
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