<?php $adminTitle = 'Dashboard'; ?>

<?php
$expSoon = $expiringLicenses ?? [];
$unread = $unreadMessages ?? [];
$daysLeft = function (string $expires): int {
    return max(0, (int) floor((strtotime($expires) - time()) / 86400));
};
?>

<?php
$barChart = function (array $points, string $color = '#2563eb') {
    $w = 640; $h = 240; $padL = 40; $padB = 28; $padT = 14; $padR = 8;
    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;
    $max = 1.0;
    foreach ($points as $p) {
        $max = max($max, (float) ($p['value'] ?? 0));
    }
    $n = count($points);
    $slot = $n > 0 ? $plotW / $n : $plotW;
    $barW = max(6, min(36, (int) floor($slot * 0.62)));
    $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="xMidYMid meet" class="w-full h-56" role="img" aria-label="Revenue trend">';
    $svg .= '<g stroke="#e2e8f0" stroke-width="1">';
    for ($i = 0; $i <= 3; $i++) {
        $y = $padT + $plotH - ($plotH * $i / 3);
        $svg .= '<line x1="' . $padL . '" y1="' . $y . '" x2="' . ($w - $padR) . '" y2="' . $y . '"></line>';
        $val = $max * $i / 3;
        $svg .= '<text x="' . ($padL - 6) . '" y="' . ($y + 4) . '" text-anchor="end" font-size="10" fill="#94a3b8">$' . number_format($val) . '</text>';
    }
    $svg .= '</g>';
    foreach ($points as $i => $p) {
        $x = $padL + $slot * $i + ($slot - $barW) / 2;
        $bh = $plotH * ((float) ($p['value'] ?? 0)) / $max;
        $y = $padT + $plotH - $bh;
        $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $barW . '" height="' . $bh . '" rx="3" fill="' . $color . '" opacity="0.85"></rect>';
        $svg .= '<text x="' . ($x + $barW / 2) . '" y="' . ($padT + $plotH + 16) . '" text-anchor="middle" font-size="10" fill="#64748b">' . htmlspecialchars((string) $p['label']) . '</text>';
        if ($bh > 24) {
            $svg .= '<text x="' . ($x + $barW / 2) . '" y="' . ($y - 6) . '" text-anchor="middle" font-size="9" fill="#475569">' . number_format((float) $p['value'], (float) $p['value'] && (float) $p['value'] < 1000 ? 0 : 0) . '</text>';
        }
    }
    $svg .= '</svg>';

    return $svg;
};

$donut = function (array $segments, int $total) {
    if ($total <= 0) {
        return '<div class="text-sm text-slate-400 text-center py-10">No licenses yet.</div>';
    }
    $r = 54; $cx = 74; $cy = 74; $c = 2 * M_PI * $r;
    $offset = 0;
    $colors = ['active' => '#16a34a', 'expired' => '#cbd5e1', 'suspended' => '#f59e0b', 'cancelled' => '#ef4444'];
    $svg = '<svg viewBox="0 0 148 148" class="w-36 h-36 mx-auto" role="img" aria-label="Licenses by status">';
    $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none" stroke="#e2e8f0" stroke-width="20"></circle>';
    $acc = 0.0;
    foreach ($segments as $s) {
        $val = (float) $s['c'];
        if ($val <= 0) {
            continue;
        }
        $len = $val / $total * $c;
        $color = $colors[$s['status']] ?? '#64748b';
        $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none" stroke="' . $color . '" stroke-width="20" stroke-dasharray="' . round($len, 2) . ' ' . round($c, 2) . '" stroke-dashoffset="' . round(-$offset, 2) . '"></circle>';
        $offset += $len;
        $acc += $val;
    }
    $svg .= '<text x="' . $cx . '" y="' . ($cy + 2) . '" text-anchor="middle" font-size="13" font-weight="700" fill="#0f172a">' . ((int) $acc) . '</text>';
    $svg .= '<text x="' . $cx . '" y="' . ($cy + 16) . '" text-anchor="middle" font-size="9" fill="#94a3b8">licenses</text>';
    $svg .= '</svg>';

    return $svg;
};

$productColors = ['app' => '#2563eb', 'theme' => '#7c3aed', 'php' => '#0ea5e9'];
$totalLicenses = max(1, (int) $stats['licenses']);
$maxProduct = 1;
foreach ($licenseByProduct as $row) {
    $maxProduct = max($maxProduct, (int) $row['c']);
}
?>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center justify-between">
        <div>
            <div class="text-xs uppercase tracking-wide text-slate-400">Revenue</div>
            <div class="text-2xl font-extrabold mt-1">$<?= number_format((float) $stats['revenue'], 0) ?></div>
            <div class="text-xs text-slate-400 mt-1">$<?= number_format((float) $stats['revenue_this_month']) ?> this month</div>
            <?php
            $prev = (float) $stats['revenue_prev_month'];
            $now = (float) $stats['revenue_this_month'];
            if ($prev > 0) {
                $delta = round(($now - $prev) / $prev * 100);
                echo '<div class="text-xs mt-1 font-semibold ' . ($delta >= 0 ? 'text-green-600' : 'text-red-500') . '">' . ($delta >= 0 ? '▲' : '▼') . ' ' . abs($delta) . '% vs last month</div>';
            }
            ?>
        </div>
        <div class="text-blue-600 bg-blue-50 rounded-lg p-2.5">
            <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-xs uppercase tracking-wide text-slate-400">Customers</div>
        <div class="text-3xl font-extrabold mt-1"><?= (int) $stats['customers'] ?></div>
        <div class="text-xs text-slate-400 mt-1">accounts</div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-xs uppercase tracking-wide text-slate-400">Licenses</div>
        <div class="text-3xl font-extrabold mt-1"><?= (int) $stats['licenses'] ?></div>
        <div class="text-xs text-green-600 mt-1"><?= (int) $stats['active_licenses'] ?> active</div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-xs uppercase tracking-wide text-slate-400">Expiring ≤30d</div>
        <div class="text-3xl font-extrabold mt-1 <?= (int) $stats['expiring_soon'] > 0 ? 'text-amber-600' : '' ?>"><?= (int) $stats['expiring_soon'] ?></div>
        <div class="text-xs <?= (int) $stats['unread_messages'] > 0 ? 'text-blue-600' : 'text-slate-400' ?> mt-1"><?= (int) $stats['unread_messages'] ?> unread message(s)</div>
    </div>
</div>

<div class="mb-6 bg-slate-900 rounded-xl p-4 flex flex-wrap items-center gap-3">
    <span class="text-xs uppercase tracking-wide text-slate-400 mr-2">Quick actions</span>
    <a href="/admin/licenses/create" class="text-sm bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg font-semibold">+ Issue license</a>
    <a href="/admin/plans/create" class="text-sm bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg font-medium">+ New plan</a>
    <a href="/admin/messages" class="text-sm bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg font-medium">Messages</a>
    <a href="/admin/settings" class="text-sm bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg font-medium">Email settings</a>
    <a href="/admin/payments/export" class="text-sm bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg font-medium">↓ Export payments</a>
    <a href="/admin/account" class="text-sm ml-auto bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg font-medium">⚙ Account</a>
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 bg-amber-50 border border-amber-200 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-amber-900 flex items-center gap-2">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2h4M12 2v4M4.6 9H19.4M4.9 5h14.2A2.9 2.9 0 0 1 22 7.9v9.2A2.9 2.9 0 0 1 19.1 20H4.9A2.9 2.9 0 0 1 2 17.1V7.9A2.9 2.9 0 0 1 4.9 5ZM9 16h6M9 13h6"/></svg>
                Licenses expiring in 30 days
            </h2>
            <a href="/admin/licenses" class="text-sm font-semibold text-amber-700 hover:underline">Manage licenses →</a>
        </div>
        <?php if (!empty($expSoon)): ?>
            <ul class="space-y-2">
                <?php foreach ($expSoon as $l): ?>
                    <li class="flex flex-wrap items-center justify-between gap-3 bg-white border border-amber-200 rounded-lg px-4 py-2.5">
                        <div>
                            <div class="font-mono text-xs"><?= htmlspecialchars($l['license_key']) ?></div>
                            <div class="text-xs text-slate-500"><?= htmlspecialchars($l['customer_name'] ?? '—') ?> · <?= htmlspecialchars($l['customer_email'] ?? '') ?></div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-semibold <?= $daysLeft($l['expires_at']) <= 7 ? 'text-red-600' : 'text-amber-700' ?>"><?= $daysLeft($l['expires_at']) ?> day(s)</span>
                            <span class="text-xs text-slate-400"><?= htmlspecialchars($l['expires_at']) ?></span>
                            <a href="/admin/licenses/<?= (int) $l['id'] ?>" class="text-xs bg-amber-600 hover:bg-amber-500 text-white px-3 py-1.5 rounded-lg font-semibold">View</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-sm text-amber-800/70">No licenses expiring in the next 30 days. All quiet.</p>
        <?php endif; ?>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-blue-900">Unread messages</h2>
            <a href="/admin/messages" class="text-sm font-semibold text-blue-700 hover:underline">View all →</a>
        </div>
        <?php if (!empty($unread)): ?>
            <ul class="space-y-2">
                <?php foreach (array_slice($unread, 0, 5) as $m): ?>
                    <li class="bg-white border border-blue-200 rounded-lg px-3 py-2.5">
                        <div class="text-sm font-semibold text-slate-700"><?= htmlspecialchars($m['name'] ?? '—') ?> <span class="text-slate-400 font-normal">· <?= htmlspecialchars(substr((string) ($m['created_at'] ?? ''), 0, 10)) ?></span></div>
                        <div class="text-xs text-slate-500 truncate"><?= htmlspecialchars((string) ($m['message'] ?? '')) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-sm text-blue-800/70">Inbox zero — no unread messages.</p>
        <?php endif; ?>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold">Revenue — last 12 months</h2>
        </div>
        <?= $barChart($revenueTrend) ?>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-bold mb-4">Licenses by status</h2>
        <?= $donut($licenseByStatus, $totalLicenses) ?>
        <ul class="mt-4 space-y-1.5 text-sm">
            <?php foreach ($licenseByStatus as $row): ?>
                <li class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-slate-500"><span class="inline-block h-2.5 w-2.5 rounded-full" style="background:<?= (['active' => '#16a34a', 'expired' => '#cbd5e1', 'suspended' => '#f59e0b', 'cancelled' => '#ef4444'])[$row['status']] ?? '#64748b' ?>"></span><?= htmlspecialchars((string) $row['status']) ?></span>
                    <span class="font-semibold"><?= (int) $row['c'] ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
    <h2 class="font-bold mb-4">Licenses by product</h2>
    <div class="space-y-4">
        <?php foreach ($licenseByProduct as $row): ?>
            <?php $pct = round((int) $row['c'] / $totalLicenses * 100); ?>
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="font-medium capitalize text-slate-600"><?= htmlspecialchars((string) $row['product']) ?></span>
                    <span class="text-slate-500"><?= (int) $row['c'] ?> <span class="text-slate-300 text-xs">(<?= $pct ?>%)</span></span>
                </div>
                <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full" style="width:<?= $pct ?>%;background:<?= $productColors[$row['product']] ?? '#2563eb' ?>"></div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($licenseByProduct)): ?>
            <p class="text-sm text-slate-400">No licenses yet.</p>
        <?php endif; ?>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold">Recent payments</h2>
            <div class="flex items-center gap-3">
                <a href="/admin/payments/export" class="text-sm text-slate-500 hover:text-blue-600">Download CSV</a>
                <a href="/admin/payments" class="text-sm text-blue-600">View all</a>
            </div>
        </div>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-slate-400 text-xs uppercase"><th>Ref</th><th>Customer</th><th class="text-right">Amount</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recentPayments as $p): ?>
                <tr class="border-t border-slate-100">
                    <td class="py-2 font-mono text-xs"><?= htmlspecialchars($p['reference']) ?></td>
                    <td class="py-2"><?= htmlspecialchars($p['customer_name'] ?? '—') ?></td>
                    <td class="py-2 text-right">$<?= number_format((float) $p['amount'], 2) ?></td>
                    <td class="py-2"><span class="text-xs px-2 py-1 rounded-full <?= $p['status'] === 'paid' ? 'bg-green-100 text-green-700' : ($p['status'] === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>"><?= $p['status'] ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold">Recent licenses</h2>
            <a href="/admin/licenses" class="text-sm text-blue-600">View all</a>
        </div>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-slate-400 text-xs uppercase"><th>Key</th><th>Customer</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recentLicenses as $l): ?>
                <tr class="border-t border-slate-100">
                    <td class="py-2 font-mono text-xs"><?= htmlspecialchars($l['license_key']) ?></td>
                    <td class="py-2"><?= htmlspecialchars($l['customer_name'] ?? '—') ?></td>
                    <td class="py-2"><span class="text-xs px-2 py-1 rounded-full <?= $l['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' ?>"><?= $l['status'] ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>