<?php $adminTitle = 'Email templates'; ?>

<div class="mb-4">
    <h2 class="font-bold text-lg">Email templates</h2>
    <p class="text-sm text-slate-500 mt-1">Override the subject and body of the emails the license server sends. Leave a field empty to keep the built-in default. Use <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">{name}</code>, <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">{licenseKey}</code>, <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">{amount}</code>, <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">{product}</code> style placeholders.</p>
</div>

<div class="space-y-4">
    <?php foreach ($templates as $t): $row = $t['row']; ?>
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <details class="group">
                <summary class="cursor-pointer list-none flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="font-semibold"><?= htmlspecialchars($t['label']) ?></span>
                        <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded text-slate-500"><?= htmlspecialchars($t['key']) ?></code>
                        <span class="text-xs <?= $row['is_active'] ? 'text-green-600' : 'text-slate-400' ?>"><?= $row['is_active'] ? '● active' : '○ off' ?></span>
                    </div>
                    <span class="text-slate-400 text-xs group-open:rotate-180">▾</span>
                </summary>
                <form method="post" action="/admin/email-templates/<?= (int) $row['id'] ?>" class="mt-4 space-y-3">
                    <?= csrf_field() ?>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Subject</label>
                        <input name="subject" value="<?= htmlspecialchars((string) ($row['subject'] ?? '')) ?>" placeholder="(use built-in default)" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Body (HTML)</label>
                        <textarea name="body" rows="8" placeholder="(use built-in default)" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono"><?= htmlspecialchars((string) ($row['body'] ?? '')) ?></textarea>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm font-medium">
                            <input type="checkbox" name="is_active" value="1" <?= $row['is_active'] ? 'checked' : '' ?> class="rounded border-slate-300">
                            Enabled
                        </label>
                        <button class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-5 py-2 rounded-lg">Save template</button>
                    </div>
                </form>
            </details>
        </div>
    <?php endforeach; ?>
</div>