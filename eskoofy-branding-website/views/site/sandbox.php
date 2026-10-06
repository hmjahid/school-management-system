<?php $title = 'Test / Sandbox payment'; $siteTitle = $title; $seo = ['title' => $title, 'canonical' => '/checkout/sandbox', 'noindex' => true]; ?>

<?php
$sym = ($payment['currency'] ?? 'USD') === 'BDT' ? '৳' : '$';
?>

<section class="max-w-2xl mx-auto px-4 py-16">
    <div class="rounded-xl border border-amber-300 bg-amber-50 text-amber-900 px-4 py-3 text-sm font-medium mb-6">
        Free test payment — no money moves. This gateway is for verifying the checkout pipeline only.
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-8">
        <h1 class="text-3xl font-extrabold mb-1">Test / Sandbox</h1>
        <p class="text-sm text-slate-500 mb-6">Choose a simulated outcome.</p>

        <dl class="divide-y divide-slate-100 border-y border-slate-100 mb-6">
            <div class="flex items-center justify-between py-3">
                <dt class="text-sm text-slate-500">Plan</dt>
                <dd class="text-sm font-semibold"><?= htmlspecialchars((string) ($plan['name'] ?? '—')) ?></dd>
            </div>
            <div class="flex items-center justify-between py-3">
                <dt class="text-sm text-slate-500">Amount</dt>
                <dd class="text-lg font-extrabold text-blue-700"><?= $sym ?><?= number_format((float) $payment['amount'], ($payment['currency'] ?? '') === 'BDT' ? 0 : 2) ?> <span class="text-xs text-slate-400 font-normal uppercase"><?= htmlspecialchars((string) $payment['currency']) ?></span></dd>
            </div>
            <div class="flex items-center justify-between py-3">
                <dt class="text-sm text-slate-500">Reference</dt>
                <dd class="text-sm font-mono"><?= htmlspecialchars((string) $payment['reference']) ?></dd>
            </div>
            <div class="flex items-center justify-between py-3">
                <dt class="text-sm text-slate-500">Gateway transaction</dt>
                <dd class="text-sm font-mono"><?= htmlspecialchars((string) ($payment['transaction_id'] ?? '')) ?></dd>
            </div>
        </dl>

        <div class="grid sm:grid-cols-3 gap-3">
            <form method="post" action="/checkout/sandbox/<?= htmlspecialchars(rawurlencode((string) $payment['reference'])) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="simulate" value="success">
                <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white px-4 py-3 rounded-lg font-semibold">Simulate success</button>
            </form>
            <form method="post" action="/checkout/sandbox/<?= htmlspecialchars(rawurlencode((string) $payment['reference'])) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="simulate" value="failure">
                <button type="submit" class="w-full bg-red-600 hover:bg-red-500 text-white px-4 py-3 rounded-lg font-semibold">Simulate failure</button>
            </form>
            <form method="post" action="/checkout/sandbox/<?= htmlspecialchars(rawurlencode((string) $payment['reference'])) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="simulate" value="cancel">
                <button type="submit" class="w-full border border-slate-300 hover:bg-slate-50 text-slate-700 px-4 py-3 rounded-lg font-semibold">Cancel</button>
            </form>
        </div>
    </div>
</section>
