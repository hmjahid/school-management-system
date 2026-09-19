<?php $adminTitle = 'Push notifications'; ?>

<div class="mb-6 grid md:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="font-bold mb-1">Send a notification</h3>
        <p class="text-sm text-slate-500 mb-4">Broadcast a notice to every customer — it appears on their account dashboard (and optionally by email).</p>
        <form method="post" action="/admin/push-notifications" class="space-y-3">
            <?= csrf_field() ?>
            <div>
                <label class="block text-xs font-semibold mb-1">Title</label>
                <input name="title" placeholder="e.g. New version 1.3 released" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Message</label>
                <textarea name="message" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Link (optional)</label>
                <input name="link" placeholder="/account/downloads" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="checkbox" name="email_too" value="1" class="rounded border-slate-300">
                Also email all customers
            </label>
            <button class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2 rounded-lg">Send notification</button>
        </form>
    </div>
    <div class="md:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="font-bold mb-4">Sent notifications</h3>
        <?php if (empty($notifications)): ?>
            <p class="text-sm text-slate-400">Nothing sent yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[600px]">
                    <thead class="bg-slate-50 text-left text-slate-400 text-xs uppercase">
                        <tr>
                            <th class="px-3 py-2">Title</th>
                            <th class="px-3 py-2">Message</th>
                            <th class="px-3 py-2 text-right">Reads</th>
                            <th class="px-3 py-2">Sent</th>
                            <th class="px-3 py-2 text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($notifications as $n): ?>
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-2.5 font-semibold"><?= htmlspecialchars($n['title']) ?></td>
                            <td class="px-3 py-2.5 text-slate-500 max-w-xs truncate"><?= htmlspecialchars((string) ($n['message'] ?? '')) ?></td>
                            <td class="px-3 py-2.5 text-right"><?= (int) $n['read_count'] ?></td>
                            <td class="px-3 py-2.5 text-xs text-slate-400"><?= htmlspecialchars(substr((string) $n['created_at'], 0, 16)) ?></td>
                            <td class="px-3 py-2.5 text-right">
                                <form method="post" action="/admin/push-notifications/<?= (int) $n['id'] ?>/delete" onsubmit="return confirm('Delete this notification?')">
                                    <?= csrf_field() ?>
                                    <button class="text-xs border border-red-200 text-red-600 hover:bg-red-50 px-2.5 py-1.5 rounded-lg font-semibold">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>