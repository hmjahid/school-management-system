<?php $title = 'Subscriptions'; $siteTitle = $title; ?>

<section class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold">Subscriptions</h1>
            <p class="text-slate-500 mt-1">Subscription lifecycle across all customers</p>
        </div>
        <a href="/admin/dashboard" class="text-sm text-blue-600 font-semibold hover:underline">← Dashboard</a>
    </div>

    <div class="grid md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="text-3xl font-extrabold text-blue-700">$<?= number_format($mrr, 2) ?></div>
            <div class="text-xs text-slate-400 uppercase tracking-wide mt-1">MRR (USD est.)</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="text-3xl font-extrabold text-blue-700">$<?= number_format($arr, 2) ?></div>
            <div class="text-xs text-slate-400 uppercase tracking-wide mt-1">ARR (USD est.)</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="text-3xl font-extrabold text-blue-700"><?= $active ?></div>
            <div class="text-xs text-slate-400 uppercase tracking-wide mt-1">Active subscriptions</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left text-xs uppercase tracking-wide">
                <tr>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">License</th>
                    <th class="px-4 py-3">Plan</th>
                    <th class="px-4 py-3">Gateway</th>
                    <th class="px-4 py-3">Period</th>
                    <th class="px-4 py-3">Renews</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">No subscriptions yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold"><?= htmlspecialchars($r['customer_name']) ?></div>
                                <div class="text-xs text-slate-400"><?= htmlspecialchars($r['customer_email']) ?></div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars($r['license_key']) ?></td>
                            <td class="px-4 py-3">
                                <div><?= htmlspecialchars($r['plan_name']) ?> (<?= htmlspecialchars($r['product']) ?>)</div>
                                <div class="text-xs text-slate-400"><?= htmlspecialchars($r['currency']) ?> <?= number_format((float) $r['price'], 2) ?> / <?= htmlspecialchars($r['period']) ?></div>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars(ucfirst($r['gateway'])) ?></td>
                            <td class="px-4 py-3 text-xs"><?= htmlspecialchars((string) ($r['current_period_start'] ?? '')) ?><br><?= htmlspecialchars((string) ($r['current_period_end'] ?? '')) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string) ($r['renews_at'] ?? '—')) ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?= ($r['status'] ?? '') === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' ?>"><?= htmlspecialchars($r['status']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>