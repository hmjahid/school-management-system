<?php $adminTitle = 'Customer — ' . $customer['name']; ?>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-bold mb-4"><?= htmlspecialchars($customer['name']) ?></h2>
        <dl class="text-sm space-y-2">
            <div class="flex justify-between"><dt class="text-slate-400">Email</dt><dd><?= htmlspecialchars($customer['email']) ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Company</dt><dd><?= htmlspecialchars((string) ($customer['company'] ?? '')) ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Country</dt><dd><?= htmlspecialchars((string) ($customer['country'] ?? '')) ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">API token</dt><dd class="font-mono text-xs"><?= htmlspecialchars((string) ($customer['api_token'] ?? '—')) ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Status</dt><dd><?= $customer['status'] ?></dd></div>
        </dl>
        <form method="post" action="/admin/customers/<?= (int) $customer['id'] ?>
            <?= csrf_field() ?>" class="mt-4 flex gap-2">
            <input name="status" value="<?= $customer['status'] === 'active' ? 'suspended' : 'active' ?>" hidden>
            <button class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm"><?= $customer['status'] === 'active' ? 'Suspend' : 'Reactivate' ?></button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-bold mb-4">Licenses (<?= count($licenses) ?>)</h2>
        <ul class="space-y-2 text-sm">
            <?php foreach ($licenses as $l): ?>
                <li class="flex items-center justify-between border border-slate-100 rounded-lg px-3 py-2">
                    <a href="/admin/licenses/<?= (int) $l['id'] ?>" class="font-mono text-xs text-blue-600 hover:underline"><?= htmlspecialchars($l['license_key']) ?></a>
                    <span class="text-xs <?= $l['status'] === 'active' ? 'text-green-600' : 'text-slate-400' ?>"><?= $l['status'] ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <h2 class="font-bold mb-2 mt-6">Payments</h2>
        <ul class="space-y-1 text-sm">
            <?php foreach ($payments as $p): ?>
                <li class="flex justify-between"><span class="text-slate-500"><?= htmlspecialchars($p['reference']) ?></span><span>$<?= number_format((float) $p['amount'], 2) ?> · <?= $p['status'] ?></span></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>