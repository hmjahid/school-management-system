<?php $adminTitle = 'Deployment & maintenance'; ?>

<form method="post" action="/admin/services" class="max-w-3xl space-y-6">
    <?= csrf_field() ?>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-bold mb-1">Deployment & maintenance add-on</h2>
        <p class="text-sm text-slate-500 mb-5">Indicative prices for the optional add-on shown on the product and pricing pages. Shown in the visitor’s currency (BDT for Bangladeshi visitors) at the current rate. Services are quoted and invoiced per client — they never affect license or plan pricing.</p>
        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">App — assisted deployment (USD)</label>
                <input type="number" min="0" step="0.01" name="services_deploy_app" value="<?= htmlspecialchars((string) ($settings['services.deploy_app'] ?? '250')) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">PHP & Theme — assisted deployment (USD)</label>
                <input type="number" min="0" step="0.01" name="services_deploy_php_theme" value="<?= htmlspecialchars((string) ($settings['services.deploy_php_theme'] ?? '150')) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Monthly care (USD / month)</label>
                <input type="number" min="0" step="0.01" name="services_care_monthly" value="<?= htmlspecialchars((string) ($settings['services.care_monthly'] ?? '29')) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>
    </div>

    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold px-6 py-3 rounded-lg">Save prices</button>
</form>