<?php $adminTitle = 'Payment gateways'; ?>

<form method="post" action="/admin/gateways" class="space-y-6">
    <?= csrf_field() ?>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-bold mb-1">Payment gateways</h2>
        <p class="text-sm text-slate-500">Enable the gateways you accept and store their credentials. Values here override the server <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">.env</code> config. Local methods are shown to Bangladeshi customers; international methods to everyone else. Manual / bank transfer is always available.</p>
        <p class="text-xs text-slate-400 mt-2">The zero-credential <strong>Test / Sandbox</strong> gateway is a development tool; customers only see it at checkout when <code class="bg-slate-100 px-1 py-0.5 rounded">TEST_GATEWAY_ENABLED=true</code>. Sandbox URLs for the extended BD gateways are drafts — verify each vendor's docs before enabling.</p>
    </div>

    <?php foreach (['local' => 'Local (Bangladesh)', 'international' => 'International', 'offline' => 'Offline', 'testing' => 'Testing / Sandbox'] as $group => $groupLabel): ?>
        <div>
            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500 mb-3"><?= htmlspecialchars($groupLabel) ?></h3>
            <div class="grid lg:grid-cols-2 gap-4">
                <?php foreach ($gateways as $code => $meta): if ($meta[1] !== $group) continue; ?>
                    <div class="bg-white rounded-xl border border-slate-200 p-5">
                        <label class="flex items-center justify-between mb-3 cursor-pointer">
                            <span class="font-bold"><?= htmlspecialchars($meta[0]) ?></span>
                            <span class="flex items-center gap-2 text-sm font-medium">
                                <?php $enabled = (string) ($settings['gateway.' . $code . '.enabled'] ?? (in_array($code, ['manual', 'test_gateway'], true) ? '1' : '0')) === '1'; ?>
                                <input type="checkbox" name="enabled[<?= $code ?>]" value="1" <?= $enabled ? 'checked' : '' ?> class="rounded border-slate-300">
                                Enabled
                            </span>
                        </label>
                        <?php if (empty($meta[2])): ?>
                            <p class="text-xs text-slate-400">No credentials required — works out of the box.</p>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($meta[2] as $field => $info): ?>
                                    <div>
                                        <label class="block text-xs font-semibold mb-1"><?= htmlspecialchars($info[0]) ?></label>
                                        <input type="<?= $info[1] ? 'password' : 'text' ?>" name="fields[<?= $code ?>][<?= $field ?>]" value="<?= htmlspecialchars((string) ($settings['gateway.' . $code . '.' . $field] ?? '')) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono" autocomplete="off">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php $d = $diagnostics[$code] ?? null; if ($d): ?>
                            <div class="mt-3 pt-3 border-t border-slate-100 text-xs text-slate-500 flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span>Driver: <code class="bg-slate-100 px-1 rounded"><?= htmlspecialchars($d['driver']) ?></code></span>
                                <span>Configured: <strong class="<?= $d['configured'] ? 'text-green-600' : 'text-red-500' ?>"><?= $d['configured'] ? 'yes' : 'no' ?></strong></span>
                                <span>Active: <strong class="<?= $d['enabled'] ? 'text-green-600' : 'text-slate-400' ?>"><?= $d['enabled'] ? 'yes' : 'no' ?></strong></span>
                                <?php if ($d['testable']): ?>
                                    <a href="/checkout" class="text-blue-600 hover:underline">Run test payment →</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold px-6 py-3 rounded-lg">Save gateways</button>
</form>