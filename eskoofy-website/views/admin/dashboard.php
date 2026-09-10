<?php $adminTitle = 'Dashboard'; ?>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-xs uppercase tracking-wide text-slate-400">Customers</div>
        <div class="text-3xl font-extrabold mt-1"><?= (int) $stats['customers'] ?></div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-xs uppercase tracking-wide text-slate-400">Licenses</div>
        <div class="text-3xl font-extrabold mt-1"><?= (int) $stats['licenses'] ?></div>
        <div class="text-xs text-slate-400 mt-1"><?= (int) $stats['active_licenses'] ?> active</div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-xs uppercase tracking-wide text-slate-400">Revenue</div>
        <div class="text-3xl font-extrabold mt-1">$<?= number_format((float) $stats['revenue'], 2) ?></div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-xs uppercase tracking-wide text-slate-400">Expiring ≤30d</div>
        <div class="text-3xl font-extrabold mt-1"><?= (int) $stats['expiring_soon'] ?></div>
        <div class="text-xs <?= (int) $stats['unread_messages'] > 0 ? 'text-blue-600' : 'text-slate-400' ?> mt-1"><?= (int) $stats['unread_messages'] ?> unread message(s)</div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold">Recent payments</h2>
            <a href="/admin/payments" class="text-sm text-blue-600">View all</a>
        </div>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-slate-400 text-xs uppercase"><th>Ref</th><th>Customer</th><th class="text-right">Amount</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recentPayments as $p): ?>
                <tr class="border-t border-slate-100">
                    <td class="py-2 font-mono text-xs"><?= htmlspecialchars($p['reference']) ?></td>
                    <td class="py-2"><?= htmlspecialchars($p['customer_name'] ?? '—') ?></td>
                    <td class="py-2 text-right">$<?= number_format((float) $p['amount'], 2) ?></td>
                    <td class="py-2"><span class="text-xs px-2 py-1 rounded-full <?= $p['status'] === 'paid' ? 'bg-green-100 text-green-700' : ($p['status'] === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>"><?= $p['status'] ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold">Recent licenses</h2>
            <a href="/admin/licenses" class="text-sm text-blue-600">View all</a>
        </div>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-slate-400 text-xs uppercase"><th>Key</th><th>Customer</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recentLicenses as $l): ?>
                <tr class="border-t border-slate-100">
                    <td class="py-2 font-mono text-xs"><?= htmlspecialchars($l['license_key']) ?></td>
                    <td class="py-2"><?= htmlspecialchars($l['customer_name'] ?? '—') ?></td>
                    <td class="py-2"><span class="text-xs px-2 py-1 rounded-full <?= $l['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' ?>"><?= $l['status'] ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>