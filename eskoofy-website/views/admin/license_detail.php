<?php $adminTitle = 'License — ' . $license['license_key']; ?>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-bold mb-4">License detail</h2>
        <dl class="text-sm space-y-2">
            <div class="flex justify-between"><dt class="text-slate-400">License key</dt><dd class="font-mono text-xs"><?= htmlspecialchars($license['license_key']) ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Product</dt><dd><?= htmlspecialchars((string) ($license['product'] ?? '—')) ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Status</dt><dd><?= $license['status'] ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Starts</dt><dd><?= htmlspecialchars((string) ($license['starts_at'] ?? '—')) ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Expires</dt><dd><?= $license['expires_at'] ? htmlspecialchars($license['expires_at']) : 'Never (lifetime)' ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Max activations</dt><dd><?= (int) $license['max_activations'] ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Active activations</dt><dd><?= (int) $activeCount ?></dd></div>
        </dl>

        <h2 class="font-bold mt-6 mb-3">Actions</h2>
        <div class="flex flex-wrap gap-2">
            <form method="post" action="/admin/licenses/<?= (int) $license['id'] ?>
            <?= csrf_field() ?>/status">
                <?php if ($license['status'] === 'active'): ?>
                    <input type="hidden" name="status" value="suspended">
                    <button class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm">Suspend</button>
                <?php else: ?>
                    <input type="hidden" name="status" value="active">
                    <button class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm">Activate</button>
                <?php endif; ?>
            </form>
            <form method="post" action="/admin/licenses/<?= (int) $license['id'] ?>
            <?= csrf_field() ?>/extend" class="flex gap-2 items-center">
                <input type="number" name="days" value="365" min="1" class="border border-slate-300 rounded-lg px-3 py-2 text-sm w-28">
                <button class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm">Extend (days)</button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-bold mb-4">Activations (<?= count($activations) ?>)</h2>
        <table class="w-full text-sm">
            <thead class="text-left text-slate-400 text-xs uppercase"><tr><th>Domain</th><th>Activated</th><th>Deactivated</th></tr></thead>
            <tbody>
            <?php foreach ($activations as $a): ?>
                <tr class="border-t border-slate-100">
                    <td class="py-2"><?= htmlspecialchars($a['domain']) ?><?= $a['machine_id'] ? ' <span class="text-slate-400 text-xs">(' . htmlspecialchars(substr($a['machine_id'], 0, 12)) . '…)</span>' : '' ?></td>
                    <td class="py-2 text-xs"><?= htmlspecialchars($a['activated_at']) ?></td>
                    <td class="py-2 text-xs"><?= $a['deactivated_at'] ? htmlspecialchars($a['deactivated_at']) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>