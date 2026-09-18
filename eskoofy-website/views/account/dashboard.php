<?php $title = 'My account — Dashboard'; $siteTitle = $title; ?>

<?php
$barMini = function (array $points, string $color = '#2563eb') {
    $w = 360; $h = 150; $padL = 30; $padB = 22; $padT = 10; $padR = 6;
    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;
    $max = 1.0;
    foreach ($points as $p) {
        $max = max($max, (float) ($p['value'] ?? 0));
    }
    $n = count($points);
    $slot = $n > 0 ? $plotW / $n : $plotW;
    $barW = max(6, min(26, (int) floor($slot * 0.56)));
    $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="xMidYMid meet" class="w-full h-36" role="img" aria-label="Your spend, last 6 months">';
    $svg .= '<g stroke="#e2e8f0" stroke-width="1">';
    for ($i = 0; $i <= 2; $i++) {
        $y = $padT + $plotH - ($plotH * $i / 2);
        $svg .= '<line x1="' . $padL . '" y1="' . $y . '" x2="' . ($w - $padR) . '" y2="' . $y . '"></line>';
        $svg .= '<text x="' . ($padL - 5) . '" y="' . ($y + 4) . '" text-anchor="end" font-size="9" fill="#94a3b8">$' . number_format($max * $i / 2) . '</text>';
    }
    $svg .= '</g>';
    foreach ($points as $i => $p) {
        $x = $padL + $slot * $i + ($slot - $barW) / 2;
        $bh = $plotH * ((float) ($p['value'] ?? 0)) / $max;
        $y = $padT + $plotH - $bh;
        $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $barW . '" height="' . $bh . '" rx="3" fill="' . $color . '" opacity="0.85"></rect>';
        $svg .= '<text x="' . ($x + $barW / 2) . '" y="' . ($padT + $plotH + 14) . '" text-anchor="middle" font-size="9" fill="#64748b">' . htmlspecialchars((string) $p['label']) . '</text>';
    }
    $svg .= '</svg>';

    return $svg;
};
$apiToken = (string) ($customer['api_token'] ?? '');
$apiMasked = $apiToken !== '' ? substr($apiToken, 0, 10) . '••••••••••••' : '';
?>

<?php \App\Core\View::partial('account.partials.account_header', ['accountPage' => 'dashboard', 'customer' => $customer]); ?>

<section class="max-w-6xl mx-auto px-4 pb-12">

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide text-slate-400">Licenses</div>
            <div class="text-3xl font-extrabold"><?= (int) $stats['total_licenses'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide text-slate-400">Active</div>
            <div class="text-3xl font-extrabold text-green-600"><?= (int) $stats['active_licenses'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide text-slate-400">Total spent</div>
            <div class="text-3xl font-extrabold text-blue-700">$<?= number_format((float) $stats['total_spent'], 2) ?></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide text-slate-400">Activations</div>
            <div class="text-3xl font-extrabold"><?= (int) $stats['total_activations'] ?></div>
        </div>
    </div>

    <?php if (!empty($renewals)): ?>
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-amber-900 flex items-center gap-2">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2h4M12 2v4M4.6 9H19.4M4.9 5h14.2A2.9 2.9 0 0 1 22 7.9v9.2A2.9 2.9 0 0 1 19.1 20H4.9A2.9 2.9 0 0 1 2 17.1V7.9A2.9 2.9 0 0 1 4.9 5ZM9 16h6M9 13h6"/></svg>
                    Upcoming renewals
                </h2>
                <span class="text-xs font-semibold text-amber-700 bg-amber-100 px-2.5 py-1 rounded-full"><?= count($renewals) ?> license(s)</span>
            </div>
            <ul class="space-y-2">
                <?php foreach ($renewals as $l):
                    $days = (int) floor((strtotime($l['expires_at']) - time()) / 86400);
                ?>
                    <li class="flex flex-wrap items-center justify-between gap-3 bg-white border border-amber-200 rounded-xl px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="h-2.5 w-2.5 rounded-full <?= $days <= 7 ? 'bg-red-500' : 'bg-amber-500' ?>"></span>
                            <div>
                                <div class="font-mono text-sm"><?= htmlspecialchars($l['license_key']) ?></div>
                                <div class="text-xs text-slate-500">expires <?= htmlspecialchars($l['expires_at']) ?> · <?= $days <= 7 ? 'expiring very soon' : 'expires in ' . $days . ' day(s)' ?></div>
                            </div>
                        </div>
                        <a href="/account/licenses/<?= (int) $l['id'] ?>" class="text-sm bg-amber-600 hover:bg-amber-500 text-white px-4 py-2 rounded-lg font-semibold">Renew now</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <h2 class="font-bold">Your spend — last 6 months</h2>
            </div>
            <?= $barMini($spendTrend) ?>
            <p class="text-xs text-slate-400 mt-2"><?= (int) $stats['paid_payments'] ?> paid payment(s) total.</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-bold mb-2">Your API key</h2>
            <p class="text-sm text-slate-500 mb-4">Used by the license server to manage your licenses programmatically. Keep it secret.</p>
            <div class="font-mono text-sm bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 truncate"><?= htmlspecialchars($apiMasked !== '' ? $apiMasked : 'No API key yet — generate one below.') ?></div>
            <form method="post" action="/account/api-token/regenerate" class="mt-4" onsubmit="return confirm('This replaces your current API key. Products using the old key will need the new one. Continue?');">
                <?= csrf_field() ?>
                <button type="submit" class="text-sm border border-slate-300 hover:border-blue-400 text-slate-700 hover:text-blue-600 px-4 py-2 rounded-lg font-semibold">Regenerate API key</button>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-bold">Your profile</h2>
                <a href="/account/settings" class="text-sm text-blue-600 hover:underline">Edit</a>
            </div>
            <div class="flex items-center gap-3 mb-4">
                <div class="h-12 w-12 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-lg"><?= htmlspecialchars(strtoupper(substr($customer['name'] ?? 'U', 0, 2))) ?></div>
                <div>
                    <div class="font-semibold"><?= htmlspecialchars($customer['name'] ?? '') ?></div>
                    <div class="text-sm text-slate-500"><?= htmlspecialchars($customer['email'] ?? '') ?></div>
                </div>
            </div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-400">Company</dt><dd class="font-medium"><?= htmlspecialchars((string) ($customer['company'] ?? '—')) ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Country</dt><dd class="font-medium"><?= htmlspecialchars((string) ($customer['country'] ?? '—')) ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Language</dt><dd class="font-medium"><?= ($customer['locale'] ?? 'en') === 'bn' ? 'বাংলা' : 'English' ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-400">Member since</dt><dd class="font-medium"><?= htmlspecialchars(substr((string) ($customer['created_at'] ?? '—'), 0, 10)) ?></dd></div>
            </dl>
            <a href="/account/settings" class="mt-4 inline-block text-sm border border-slate-300 hover:border-blue-400 text-slate-700 hover:text-blue-600 px-4 py-2 rounded-lg font-semibold">Manage account settings</a>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold">Your licenses</h2>
                <a href="/account/licenses" class="text-sm text-blue-600 hover:underline">View all</a>
            </div>
            <ul class="space-y-3">
                <?php foreach ($licenses as $l): ?>
                    <li class="flex items-center justify-between border border-slate-100 rounded-lg px-4 py-3">
                        <div>
                            <a href="/account/licenses/<?= (int) $l['id'] ?>" class="font-mono text-sm text-blue-600 hover:underline"><?= htmlspecialchars($l['license_key']) ?></a>
                            <div class="text-xs text-slate-400"><?= htmlspecialchars((string) ($l['plan_name'] ?? '')) ?> · <?= htmlspecialchars((string) ($l['plan_period'] ?? '')) ?></div>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full <?= $l['status'] === 'active' && (!$l['expires_at'] || strtotime($l['expires_at']) > time()) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                            <?= $l['status'] ?>
                        </span>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($licenses)): ?>
                    <li class="text-slate-400 text-sm text-center py-6">No licenses yet. <a href="/pricing" class="text-blue-600">Buy one →</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold">Recent payments</h2>
                <div class="flex items-center gap-3">
                    <a href="/account/payments" class="text-sm text-blue-600 hover:underline">View all</a>
                    <a href="/account/payments/export" class="text-sm text-slate-500 hover:text-blue-600 hover:underline">Download CSV</a>
                </div>
            </div>
            <ul class="space-y-2">
                <?php foreach ($payments as $p): ?>
                    <li class="flex justify-between border border-slate-100 rounded-lg px-4 py-2 text-sm">
                        <span class="font-mono text-xs"><?= htmlspecialchars($p['reference']) ?></span>
                        <span>$<?= number_format((float) $p['amount'], 2) ?> · <?= $p['status'] ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($payments)): ?>
                    <li class="text-slate-400 text-sm text-center py-6">No payments yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</section>