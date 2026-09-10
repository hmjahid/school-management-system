<?php $title = 'My licenses'; $siteTitle = $title; ?>

<section class="max-w-5xl mx-auto px-4 py-12">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-extrabold">Your licenses</h1>
            <p class="text-slate-500 mt-1">Activate each license on your installation using the license key.</p>
        </div>
        <a href="/pricing" class="bg-slate-900 hover:bg-blue-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold">Buy new license</a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-4">
        <div class="text-sm text-slate-500 mb-2">Activation API — call from your deployed product:</div>
        <pre class="bg-slate-900 text-green-300 rounded-lg p-4 text-xs overflow-x-auto">POST https://eskoofy.com/api/v1/licenses/activate
{ "license_key": "YOUR-KEY-HERE", "domain": "school.example.com" }</pre>
    </div>

    <div class="grid md:grid-cols-2 gap-5">
        <?php foreach ($licenses as $l): ?>
            <?php $isActive = $l['status'] === 'active' && (!$l['expires_at'] || strtotime($l['expires_at']) > time()); ?>
            <div class="bg-white rounded-2xl border <?= $isActive ? 'border-green-200' : 'border-slate-200' ?> p-6 esk-card-hover">
                <div class="flex items-center justify-between">
                    <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide"><?= htmlspecialchars((string) ($l['plan_name'] ?? $l['product'] ?? '')) ?></div>
                    <span class="text-xs px-2 py-1 rounded-full <?= $isActive ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' ?>"><?= $isActive ? 'active' : $l['status'] ?></span>
                </div>
                <div class="mt-3 font-mono text-sm text-slate-800"><?= htmlspecialchars($l['license_key']) ?></div>
                <div class="mt-1 text-xs text-slate-400">
                    <?= $l['expires_at'] ? 'Expires ' . htmlspecialchars(date('Y-m-d', strtotime($l['expires_at']))) : 'Lifetime license' ?> · <?= htmlspecialchars((string) ($l['plan_period'] ?? '')) ?>
                </div>
                <a href="/account/licenses/<?= (int) $l['id'] ?>" class="mt-4 inline-block text-sm text-blue-600 font-semibold hover:underline">Manage →</a>
            </div>
        <?php endforeach; ?>
        <?php if (empty($licenses)): ?>
            <div class="col-span-2 bg-white rounded-xl border border-slate-200 p-10 text-center text-slate-400">You don’t own any licenses yet. <a href="/pricing" class="text-blue-600 font-semibold">Browse plans →</a></div>
        <?php endif; ?>
    </div>
</section>