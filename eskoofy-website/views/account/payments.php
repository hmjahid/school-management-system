<?php $title = 'Payments'; $siteTitle = $title; ?>

<section class="max-w-5xl mx-auto px-4 py-12">
    <h1 class="text-3xl font-extrabold mb-6">Payment history</h1>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-400 text-xs uppercase">
                <tr><th class="px-4 py-3">Reference</th><th class="px-4 py-3">Plan</th><th class="px-4 py-3">License</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Date</th></tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars($p['reference']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars((string) ($p['plan_name'] ?? '—')) ?></td>
                    <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars((string) ($p['license_key'] ?? '—')) ?></td>
                    <td class="px-4 py-3 text-right">$<?= number_format((float) $p['amount'], 2) ?></td>
                    <td class="px-4 py-3"><span class="text-xs px-2 py-1 rounded-full <?= $p['status'] === 'paid' ? 'bg-green-100 text-green-700' : ($p['status'] === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>"><?= $p['status'] ?></span></td>
                    <td class="px-4 py-3 text-xs"><?= htmlspecialchars($p['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($payments)): ?>
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No payments yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>