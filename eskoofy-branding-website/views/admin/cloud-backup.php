<?php $adminTitle = 'Cloud Backup'; ?>
<?php
$settings = $settings ?? [];
$providers = $providers ?? [];
$remote = $remote ?? [];
$remoteNotice = $remoteNotice ?? null;
$isConfigured = $isConfigured ?? false;
$runs = $runs ?? [];
$credentials = is_array($settings['credentials'] ?? null) ? $settings['credentials'] : [];
?>

<div class="mb-4">
    <h2 class="font-bold text-lg">Cloud backup</h2>
    <p class="text-sm text-slate-500 mt-1">Upload a portable backup of this website to Google Drive, Dropbox, 4shared or S3 on your own schedule, and restore it from here.</p>
</div>

<?php if ($flash = \App\Core\Session::getInstance()->getFlash('success')): ?>
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?= esc($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Core\Session::getInstance()->getFlash('error')): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= esc($flash) ?></div>
<?php endif; ?>

<?php if (!$isConfigured): ?>
    <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        This provider has no complete credential set yet, so uploads are skipped. Fill in the required fields below.
    </div>
<?php endif; ?>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="font-bold text-slate-900 mb-4">Provider &amp; schedule</h3>
        <form method="post" action="/admin/cloud-backup/settings">
            <?= csrf_field() ?>

            <label class="block text-sm font-medium text-slate-700 mb-1">Provider</label>
            <select name="provider" id="cloud-provider" class="w-full rounded-lg border-slate-300 text-sm mb-4">
                <?php foreach ($providers as $key => $provider): ?>
                    <option value="<?= esc($key) ?>" <?= ($settings['provider'] ?? 'local') === $key ? 'selected' : '' ?>><?= esc($provider['label']) ?></option>
                <?php endforeach; ?>
            </select>

            <label class="block text-sm font-medium text-slate-700 mb-1">Remote folder</label>
            <input name="folder" type="text" value="<?= esc((string) ($settings['folder'] ?? 'eskoofy-backups')) ?>" class="w-full rounded-lg border-slate-300 text-sm mb-4" autocomplete="off">

            <?php foreach ($providers as $key => $provider): ?>
                <div data-provider-panel="<?= esc($key) ?>" <?= ($settings['provider'] ?? 'local') !== $key ? 'hidden' : '' ?>>
                    <?php if (empty($provider['fields'])): ?>
                        <p class="text-sm text-slate-500 mb-3">This provider needs no credentials.</p>
                    <?php endif; ?>
                    <?php foreach ($provider['fields'] as $field => $label): ?>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            <?= esc($label) ?>
                            <?php if (!empty($credentials[$field] ?? null)): ?>
                                <span class="text-xs font-normal text-emerald-700 ml-1">saved</span>
                            <?php endif; ?>
                        </label>
                        <input name="credentials[<?= esc($field) ?>]" type="password" value=""
                               placeholder="<?= !empty($credentials[$field] ?? null) ? '•••••••• (unchanged)' : '' ?>"
                               class="w-full rounded-lg border-slate-300 text-sm mb-3" autocomplete="new-password">
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Interval (minutes)</label>
                    <input name="interval_minutes" type="number" min="5" max="10080" value="<?= (int) ($settings['interval_minutes'] ?? 60) ?>" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Keep newest files</label>
                    <input name="keep" type="number" min="1" max="365" value="<?= (int) ($settings['keep'] ?? 7) ?>" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700 mb-1">
                <input name="is_enabled" type="checkbox" value="1" <?= !empty($settings['is_enabled']) ? 'checked' : '' ?>>
                Cloud backup enabled
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700 mb-4">
                <input name="auto_enabled" type="checkbox" value="1" <?= !empty($settings['auto_enabled']) ? 'checked' : '' ?>>
                Upload automatically on a schedule
            </label>

            <div class="flex flex-wrap gap-2">
                <button class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2 rounded-lg">Save settings</button>
                <button formaction="/admin/cloud-backup/test" class="bg-slate-900 hover:bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">Test saved settings</button>
                <button formaction="/admin/cloud-backup/run" class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold px-4 py-2 rounded-lg" <?= $isConfigured ? '' : 'disabled' ?>>Back up now</button>
            </div>
        </form>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
            <div class="px-6 py-4 border-b border-slate-200 font-bold">Remote files</div>
            <?php if ($remoteNotice): ?>
                <p class="px-6 py-4 text-sm text-slate-500"><?= esc($remoteNotice) ?></p>
            <?php elseif (empty($remote)): ?>
                <p class="px-6 py-4 text-sm text-slate-500">No files on the provider yet.</p>
            <?php else: ?>
                <table class="w-full text-sm min-w-[600px]">
                    <thead class="bg-slate-50 text-left text-slate-400 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3">File</th>
                            <th class="px-4 py-3">Size</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($remote as $file): ?>
                            <tr>
                                <td class="px-4 py-3 font-medium"><?= esc((string) $file['name']) ?></td>
                                <td class="px-4 py-3 text-slate-500"><?= number_format((int) ($file['size'] ?? 0) / 1024, 1) ?> KB</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-3">
                                        <form method="post" action="/admin/cloud-backup/restore/<?= esc(rawurlencode((string) $file['id'])) ?>" class="inline" onsubmit="return confirm('Restore this remote backup? Existing data will be overwritten.')">
                                            <?= csrf_field() ?>
                                            <button class="text-amber-700 font-semibold text-sm">Restore</button>
                                        </form>
                                        <form method="post" action="/admin/cloud-backup/delete/<?= esc(rawurlencode((string) $file['id'])) ?>" class="inline" onsubmit="return confirm('Delete this remote file?')">
                                            <?= csrf_field() ?>
                                            <button class="text-red-700 font-semibold text-sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-bold text-slate-900 mb-3">History</h3>
            <?php if (empty($runs)): ?>
                <p class="text-sm text-slate-500">No backup runs yet.</p>
            <?php else: ?>
                <ul class="divide-y divide-slate-100">
                    <?php foreach ($runs as $run): ?>
                        <li class="py-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold <?= $run['status'] === 'success' ? 'bg-green-100 text-green-800' : ($run['status'] === 'failed' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-700') ?>">
                                    <?= esc((string) $run['status']) ?>
                                </span>
                                <span class="text-xs text-slate-500"><?= esc(date('M d, Y H:i', (int) strtotime((string) ($run['created_at'] ?? 'now')))) ?></span>
                            </div>
                            <p class="mt-1 text-sm text-slate-700"><?= esc((string) $run['message']) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    var select = document.getElementById('cloud-provider');
    if (!select) return;
    function sync() {
        document.querySelectorAll('[data-provider-panel]').forEach(function (panel) {
            var active = panel.getAttribute('data-provider-panel') === select.value;
            panel.hidden = !active;
            panel.querySelectorAll('input').forEach(function (field) { field.disabled = !active; });
        });
    }
    select.addEventListener('change', sync);
    sync();
})();
</script>