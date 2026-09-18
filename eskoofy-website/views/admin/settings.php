<?php $adminTitle = 'Settings'; ?>

<form method="post" action="/admin/settings" class="space-y-6">
    <?= csrf_field() ?>

    <div class="grid lg:grid-cols-3 gap-6 items-start">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-bold mb-1">Site</h2>
            <p class="text-sm text-slate-500 mb-4">Name, tagline and contact details shown across the public site.</p>
            <label class="block text-sm font-semibold mb-1">Site name</label>
            <input name="site_name" value="<?= htmlspecialchars((string) ($settings['site.name'] ?? 'Eskoofy')) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm mb-3">
            <label class="block text-sm font-semibold mb-1">Tagline</label>
            <input name="site_tagline" value="<?= htmlspecialchars((string) ($settings['site.tagline'] ?? '')) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm mb-3">
            <label class="block text-sm font-semibold mb-1">Contact email</label>
            <input type="email" name="site_contact_email" value="<?= htmlspecialchars((string) ($settings['site.contact_email'] ?? '')) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm mb-3">
            <label class="block text-sm font-semibold mb-1">Currency label</label>
            <input name="site_currency_label" value="<?= htmlspecialchars((string) ($settings['site.currency_label'] ?? 'USD')) ?>" maxlength="8" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-bold mb-1">Appearance</h2>
            <p class="text-sm text-slate-500 mb-4">Brand accent colour and the public site’s default theme.</p>
            <label class="block text-sm font-semibold mb-1">Brand colour</label>
            <div class="flex items-center gap-3 mb-4">
                <input type="color" name="appearance_brand_color" value="<?= htmlspecialchars((string) ($settings['appearance.brand_color'] ?? '#2563eb')) ?>" class="h-10 w-14 rounded-lg border border-slate-300 cursor-pointer">
                <input name="appearance_brand_color" value="<?= htmlspecialchars((string) ($settings['appearance.brand_color'] ?? '#2563eb')) ?>" class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono" aria-label="Brand colour hex value">
            </div>
            <label class="flex items-center gap-2 text-sm font-medium cursor-pointer">
                <input type="checkbox" name="appearance_dark_default" value="1" <?= !empty($settings['appearance.dark_default']) && (string) $settings['appearance.dark_default'] === '1' ? 'checked' : '' ?> class="rounded border-slate-300">
                Dark theme by default
            </label>
            <p class="text-xs text-slate-400 mt-2">Visitors can still override with the header toggle.</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-bold mb-1">Email / SMTP</h2>
            <p class="text-sm text-slate-500 mb-4">Used for welcome, payment and renewal emails. Leave blank to use the server’s built-in mail (sendmail).</p>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-sm font-semibold mb-1">Driver</label>
                    <select name="email_driver" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="sendmail" <?= ($settings['email.driver'] ?? 'sendmail') === 'sendmail' ? 'selected' : '' ?>>sendmail</option>
                        <option value="smtp" <?= ($settings['email.driver'] ?? '') === 'smtp' ? 'selected' : '' ?>>SMTP</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Port</label>
                    <input name="email_port" value="<?= htmlspecialchars((string) ($settings['email.port'] ?? '587')) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <label class="block text-sm font-semibold mb-1">Host</label>
            <input name="email_host" value="<?= htmlspecialchars((string) ($settings['email.host'] ?? '')) ?>" placeholder="smtp.example.com" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm mb-3">
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-sm font-semibold mb-1">Username</label>
                    <input name="email_username" value="<?= htmlspecialchars((string) ($settings['email.username'] ?? '')) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Encryption</label>
                    <select name="email_encryption" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="tls" <?= ($settings['email.encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                        <option value="ssl" <?= ($settings['email.encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        <option value="none" <?= ($settings['email.encryption'] ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                    </select>
                </div>
            </div>
            <label class="block text-sm font-semibold mb-1">Password</label>
            <input type="password" name="email_password" placeholder="•••••••• (leave blank to keep current)" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm mb-3" autocomplete="new-password">
            <label class="block text-sm font-semibold mb-1">From address</label>
            <input type="email" name="email_from_address" value="<?= htmlspecialchars((string) ($settings['email.from_address'] ?? '')) ?>" placeholder="no-reply@eskoofy.com" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm mb-3">
            <label class="block text-sm font-semibold mb-1">From name</label>
            <input name="email_from_name" value="<?= htmlspecialchars((string) ($settings['email.from_name'] ?? 'Eskoofy')) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-bold mb-1">Deployment & maintenance</h2>
        <p class="text-sm text-slate-500 mb-4">Indicative prices for the optional add-on shown on the product and pricing pages. Shown in the visitor’s currency (BDT for Bangladeshi visitors) at the current rate.</p>
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
        <p class="text-xs text-slate-400 mt-3">Quoted and invoiced per client — these values never affect license or plan pricing.</p>
    </div>

    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold px-6 py-3 rounded-lg">Save settings</button>
</form>