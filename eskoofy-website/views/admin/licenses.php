<?php $adminTitle = 'Licenses'; ?>

<div class="flex items-center justify-between mb-4">
    <form method="get" action="/admin/licenses" class="flex gap-2">
        <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search key, name or email…" class="border border-slate-300 rounded-lg px-4 py-2 text-sm w-72">
        <button class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm">Search</button>
    </form>
    <a href="/admin/licenses/create" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm">Issue license</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-400 text-xs uppercase">
            <tr><th class="px-4 py-3">License key</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Plan</th><th class="px-4 py-3">Product</th><th class="px-4 py-3">Expires</th><th class="px-4 py-3">Status</th></tr>
        </thead>
        <tbody>
        <?php foreach ($licenses as $l): ?>
            <tr class="border-t border-slate-100">
                <td class="px-4 py-3"><a href="/admin/licenses/<?= (int) $l['id'] ?>" class="font-mono text-xs text-blue-600 hover:underline"><?= htmlspecialchars($l['license_key']) ?></a></td>
                <td class="px-4 py-3"><?= htmlspecialchars($l['customer_name'] ?? '—') ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($l['plan_name'] ?? '—') ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($l['product'] ?? '—') ?></td>
                <td class="px-4 py-3"><?= $l['expires_at'] ? htmlspecialchars(date('Y-m-d', strtotime($l['expires_at']))) : '∞' ?></td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-1 rounded-full <?= $l['status'] === 'active' ? 'bg-green-100 text-green-700' : ($l['status'] === 'suspended' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600') ?>"><?= $l['status'] ?></span>
                    <?php if ($l['status'] === 'active' && $l['expires_at'] && strtotime($l['expires_at']) < time()): ?>
                        <span class="text-xs px-2 py-1 rounded-full bg-amber-100 text-amber-700">expired</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($licenses)): ?>
            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No licenses found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>