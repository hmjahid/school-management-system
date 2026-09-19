<?php $adminTitle = 'Issue license'; ?>

<div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
    <form method="post" action="/admin/licenses" class="space-y-5">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-semibold mb-1">Existing customer</label>
            <select name="customer_id" class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm">
                <option value="">— select customer (or enter a new email below) —</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['email']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">…or new customer email</label>
            <input type="email" name="new_customer_email" placeholder="parent@school.com" class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm">
            <p class="text-xs text-slate-400 mt-1.5">Leave the customer dropdown empty and type an email to auto-create an unregistered customer and issue the license to them. If the email already exists, the existing account is used.</p>
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

        <div>
            <label class="block text-sm font-semibold mb-1">Add-on package (optional)</label>
            <select name="addon" class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm">
                <option value="">None — self-hosted only</option>
                <option value="deployment">Assisted deployment (one-time)</option>
                <option value="care">Monthly care &amp; maintenance</option>
                <option value="deployment_care">Deployment + monthly care</option>
            </select>
            <p class="text-xs text-slate-400 mt-1.5">Recorded on the license for your records — quoted and invoiced separately.</p>
        </div>

        <button class="bg-slate-900 text-white px-6 py-3 rounded-lg font-semibold">Generate &amp; issue license</button>
    </form>
</div>