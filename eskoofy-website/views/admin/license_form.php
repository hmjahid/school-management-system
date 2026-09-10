<?php $adminTitle = 'Issue license'; ?>

<div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
    <form method="post" action="/admin/licenses" class="space-y-4">
            <?= csrf_field() ?>
        <div>
            <label class="block text-sm font-semibold mb-1">Customer</label>
            <select name="customer_id" required class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm">
                <option value="">— select customer —</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['email']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Plan</label>
            <select name="plan_id" required class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm">
                <option value="">— select plan —</option>
                <?php foreach ($plans as $plan): ?>
                    <option value="<?= (int) $plan['id'] ?>"><?= htmlspecialchars($plan['product']) ?> · <?= htmlspecialchars($plan['name']) ?> — $<?= number_format((float) $plan['price'], 2) ?>/<?= htmlspecialchars($plan['period']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="bg-slate-900 text-white px-6 py-3 rounded-lg font-semibold">Generate &amp; issue license</button>
    </form>
</div>