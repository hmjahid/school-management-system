<?php $title = 'License detail'; $siteTitle = $title; ?>

<section class="max-w-5xl mx-auto px-4 py-12">
    <p><a href="/account/licenses" class="text-sm text-blue-600 hover:underline">← Back to licenses</a></p>
    <h1 class="text-3xl font-extrabold mt-2 mb-1"><?= htmlspecialchars((string) ($license['plan_name'] ?? $license['product'])) ?></h1>
    <p class="text-slate-500 mb-6">License key: <code class="font-mono text-sm bg-slate-100 rounded px-2 py-0.5"><?= htmlspecialchars($license['license_key']) ?></code></p>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-bold mb-4">Status</h2>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between"><dt class="text-slate-400">Status</dt><dd><?= htmlspecialchars($license['status']) ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Product</dt><dd><?= htmlspecialchars((string) ($license['product'] ?? '—')) ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Starts</dt><dd><?= htmlspecialchars((string) ($license['starts_at'] ?? '—')) ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Expires</dt><dd class="<?= !empty($expired) ? 'text-red-600 font-semibold' : '' ?>"><?= $license['expires_at'] ? htmlspecialchars($license['expires_at']) : 'Never (lifetime)' ?><?= !empty($expired) ? ' — expired' : '' ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Max activations</dt><dd><?= (int) $license['max_activations'] ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Active activations</dt><dd><?= (int) $activeCount ?></dd></div>
            </dl>

            <h2 class="font-bold mt-6 mb-3">Renew</h2>
            <form method="post" action="/account/licenses/<?= (int) $license['id'] ?>
            <?= csrf_field() ?>/renew" class="flex gap-2">
                <input type="hidden" name="plan_id" value="<?= (int) $license['plan_id'] ?>">
                <input type="hidden" name="gateway" value="manual">
                <button class="bg-slate-900 hover:bg-blue-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold">
                    Renew 1 period — $<?= number_format((float) ($license['plan_price'] ?? 0), 2) ?> (manual)
                </button>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-bold mb-4">Activations (<?= count($activations) ?>)</h2>
            <ul class="space-y-3">
                <?php foreach ($activations as $a): ?>
                    <li class="flex items-center justify-between border border-slate-100 rounded-lg px-4 py-3">
                        <div>
                            <div class="font-mono text-sm"><?= htmlspecialchars($a['domain']) ?></div>
                            <div class="text-xs text-slate-400"><?= htmlspecialchars($a['activated_at']) ?><?= $a['machine_id'] ? ' · ' . htmlspecialchars(substr($a['machine_id'], 0, 16)) : '' ?><?= $a['deactivated_at'] ? ' · deactivated ' . htmlspecialchars($a['deactivated_at']) : '' ?></div>
                        </div>
                        <?php if (!$a['deactivated_at']): ?>
                            <form method="post" action="/account/activations/revoke">
            <?= csrf_field() ?>
                                <input type="hidden" name="activation_id" value="<?= (int) $a['id'] ?>">
                                <button class="text-xs text-red-600 hover:underline">Revoke</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($activations)): ?>
                    <li class="text-slate-400 text-sm text-center py-6">Not activated yet. Call the activation API with your license key.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</section>