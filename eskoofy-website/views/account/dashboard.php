<?php $title = 'My account — Dashboard'; $siteTitle = $title; ?>

<section class="max-w-5xl mx-auto px-4 py-12">
    <h1 class="text-3xl font-extrabold mb-1">Hi, <?= htmlspecialchars($customer['name']) ?></h1>
    <p class="text-slate-500 mb-8">Here’s an overview of your licenses and payments.</p>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide text-slate-400">Licenses</div>
            <div class="text-3xl font-extrabold"><?= (int) $stats['total_licenses'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide text-slate-400">Active</div>
            <div class="text-3xl font-extrabold text-green-600"><?= (int) $stats['active_licenses'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide text-slate-400">Total spent</div>
            <div class="text-3xl font-extrabold text-blue-700">$<?= number_format((float) $stats['total_spent'], 2) ?></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide text-slate-400">Activations</div>
            <div class="text-3xl font-extrabold"><?= (int) $stats['total_activations'] ?></div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold">Your licenses</h2>
                <a href="/account/licenses" class="text-sm text-blue-600 hover:underline">View all</a>
            </div>
            <ul class="space-y-3">
                <?php foreach ($licenses as $l): ?>
                    <li class="flex items-center justify-between border border-slate-100 rounded-lg px-4 py-3">
                        <div>
                            <a href="/account/licenses/<?= (int) $l['id'] ?>" class="font-mono text-sm text-blue-600 hover:underline"><?= htmlspecialchars($l['license_key']) ?></a>
                            <div class="text-xs text-slate-400"><?= htmlspecialchars((string) ($l['plan_name'] ?? '')) ?> · <?= htmlspecialchars((string) ($l['plan_period'] ?? '')) ?></div>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full <?= $l['status'] === 'active' && (!$l['expires_at'] || strtotime($l['expires_at']) > time()) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                            <?= $l['status'] ?>
                        </span>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($licenses)): ?>
                    <li class="text-slate-400 text-sm text-center py-6">No licenses yet. <a href="/pricing" class="text-blue-600">Buy one →</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold">Recent payments</h2>
                <a href="/account/payments" class="text-sm text-blue-600 hover:underline">View all</a>
            </div>
            <ul class="space-y-2">
                <?php foreach ($payments as $p): ?>
                    <li class="flex justify-between border border-slate-100 rounded-lg px-4 py-2 text-sm">
                        <span class="font-mono text-xs"><?= htmlspecialchars($p['reference']) ?></span>
                        <span>$<?= number_format((float) $p['amount'], 2) ?> · <?= $p['status'] ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($payments)): ?>
                    <li class="text-slate-400 text-sm text-center py-6">No payments yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</section>