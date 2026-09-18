<?php $accountPage = $accountPage ?? 'dashboard'; ?>
<section class="max-w-6xl mx-auto px-4 pt-10">
    <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-extrabold">Hi, <?= htmlspecialchars($customer['name'] ?? 'there') ?> 👋</h1>
            <p class="text-slate-500 mt-1">Manage your licenses, payments and account settings from one place.</p>
        </div>
        <a href="/pricing" class="esk-chip border border-blue-300 text-blue-600 px-5 py-2.5 rounded-xl font-semibold">+ Subscribe another school</a>
    </div>
    <nav class="flex gap-1 border-b border-slate-200 pb-px overflow-x-auto mb-10">
        <?php
        $tabs = [
            ['dashboard', '/account', 'Dashboard'],
            ['licenses', '/account/licenses', 'Licenses'],
            ['payments', '/account/payments', 'Payments'],
            ['settings', '/account/settings', 'Settings'],
        ];
        foreach ($tabs as [$key, $href, $label]):
            $active = $accountPage === $key;
        ?>
            <a href="<?= $href ?>" class="whitespace-nowrap px-4 py-2 text-sm font-semibold -mb-px border-b-2 rounded-t-lg <?= $active ? 'border-blue-600 text-blue-600 bg-blue-50/60' : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </nav>
</section>