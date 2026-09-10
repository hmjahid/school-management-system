<?php $adminTitle = 'Activity log'; ?>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-400 text-xs uppercase">
            <tr><th class="px-4 py-3">ID</th><th class="px-4 py-3">Event</th><th class="px-4 py-3">Actor</th><th class="px-4 py-3">Actor id</th><th class="px-4 py-3">Meta</th><th class="px-4 py-3">Created</th></tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $log): ?>
            <tr class="border-t border-slate-100">
                <td class="px-4 py-3 text-xs"><?= (int) $log['id'] ?></td>
                <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars($log['event']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($log['actor_type']) ?></td>
                <td class="px-4 py-3"><?= (int) $log['actor_id'] ?></td>
                <td class="px-4 py-3 text-xs text-slate-500"><?= htmlspecialchars((string) ($log['metadata'] ?? '')) ?></td>
                <td class="px-4 py-3 text-xs"><?= htmlspecialchars($log['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="mt-4 text-sm text-slate-400">Page <?= (int) $page ?></div>