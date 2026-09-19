<?php $adminTitle = 'Activity log'; ?>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm min-w-[760px]">
        <thead class="bg-slate-50 text-left text-slate-400 text-xs uppercase">
            <tr><th class="px-4 py-3">ID</th><th class="px-4 py-3">Action</th><th class="px-4 py-3">Actor</th><th class="px-4 py-3">Actor id</th><th class="px-4 py-3">Details</th><th class="px-4 py-3">Created</th></tr>
        </thead>
        <tbody>
        <?php if (empty($logs)): ?>
            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No activity recorded yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($logs as $log): ?>
            <tr class="border-t border-slate-100">
                <td class="px-4 py-3 text-xs"><?= (int) $log['id'] ?></td>
                <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars((string) ($log['action'] ?? '')) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars((string) ($log['actor_type'] ?? '')) ?></td>
                <td class="px-4 py-3"><?= (int) ($log['actor_id'] ?? 0) ?></td>
                <td class="px-4 py-3 text-xs text-slate-500 max-w-[320px] truncate"><?= htmlspecialchars((string) ($log['details'] ?? '')) ?></td>
                <td class="px-4 py-3 text-xs whitespace-nowrap"><?= htmlspecialchars((string) ($log['created_at'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="mt-4 text-sm text-slate-400">Page <?= (int) $page ?></div>
