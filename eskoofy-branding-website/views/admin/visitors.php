<?php $adminTitle = 'Visitor log'; ?>

<?php
$pagination = $logs;
$rows = $pagination['data'] ?? [];
$path = (string) ($filters['path'] ?? '');
$hideBots = (bool) ($filters['hide_bots'] ?? true);

$barChart = function (array $points, string $color = '#0ea5e9') {
    $w = 640; $h = 220; $padL = 40; $padB = 28; $padT = 14; $padR = 8;
    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;
    $max = 1.0;
    foreach ($points as $p) {
        $max = max($max, (float) ($p['value'] ?? 0));
    }
    $n = count($points);
    $slot = $n > 0 ? $plotW / $n : $plotW;
    $barW = max(3, min(28, (int) floor($slot * 0.66)));
    $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="xMidYMid meet" class="w-full h-52" role="img" aria-label="Visits over the last 30 days">';
    $svg .= '<g stroke="#e2e8f0" stroke-width="1">';
    for ($i = 0; $i <= 3; $i++) {
        $y = $padT + $plotH - ($plotH * $i / 3);
        $svg .= '<line x1="' . $padL . '" y1="' . $y . '" x2="' . ($w - $padR) . '" y2="' . $y . '"></line>';
        $svg .= '<text x="' . ($padL - 6) . '" y="' . ($y + 4) . '" text-anchor="end" font-size="10" fill="#94a3b8">' . number_format($max * $i / 3, 0) . '</text>';
    }
    $svg .= '</g>';
    foreach ($points as $i => $p) {
        $x = $padL + $slot * $i + ($slot - $barW) / 2;
        $bh = $plotH * ((float) ($p['value'] ?? 0)) / $max;
        $y = $padT + $plotH - $bh;
        $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $barW . '" height="' . $bh . '" rx="2" fill="' . $color . '" opacity="0.85"></rect>';
        if ($i % 5 === 0) {
            $svg .= '<text x="' . ($x + $barW / 2) . '" y="' . ($padT + $plotH + 16) . '" text-anchor="middle" font-size="9" fill="#64748b">' . htmlspecialchars((string) $p['label']) . '</text>';
        }
    }
    $svg .= '</svg>';

    return $svg;
};

$maxPath = 1;
foreach ($topPaths as $tp) {
    $maxPath = max($maxPath, (int) $tp['c']);
}
?>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-xs uppercase tracking-wide text-slate-400">Visits today</div>
        <div class="text-3xl font-extrabold mt-1"><?= number_format((int) $kpis['today_views']) ?></div>
        <div class="text-xs text-slate-400 mt-1"><?= number_format((int) $kpis['today_unique']) ?> unique</div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-xs uppercase tracking-wide text-slate-400">Last 7 days</div>
        <div class="text-3xl font-extrabold mt-1"><?= number_format((int) $kpis['week_views']) ?></div>
        <div class="text-xs text-slate-400 mt-1"><?= number_format((int) $kpis['week_unique']) ?> unique</div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-xs uppercase tracking-wide text-slate-400">Last 30 days</div>
        <div class="text-3xl font-extrabold mt-1"><?= number_format((int) $kpis['month_views']) ?></div>
        <div class="text-xs text-slate-400 mt-1"><?= number_format((int) $kpis['month_unique']) ?> unique</div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-xs uppercase tracking-wide text-slate-400">All time</div>
        <div class="text-3xl font-extrabold mt-1"><?= number_format((int) $kpis['total_views']) ?></div>
        <div class="text-xs text-slate-400 mt-1"><?= number_format((int) $kpis['total_unique']) ?> unique · <span class="text-amber-600"><?= number_format((int) $kpis['bot_views']) ?> bot</span></div>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h2 class="font-bold">Visits — last 30 days</h2>
        <a href="/admin/settings" class="text-sm font-semibold text-blue-600 hover:underline">Analytics settings →</a>
    </div>
    <?= $barChart($trend) ?>
</div>

<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-bold mb-4">Top pages</h2>
        <?php if (empty($topPaths)): ?>
            <p class="text-sm text-slate-400">No visits recorded yet.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($topPaths as $tp): ?>
                    <?php $pct = round((int) $tp['c'] / $maxPath * 100); ?>
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1 gap-3">
                            <span class="font-mono text-xs text-slate-600 truncate"><?= htmlspecialchars((string) $tp['path']) ?></span>
                            <span class="text-slate-500 whitespace-nowrap"><?= number_format((int) $tp['c']) ?></span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-sky-500" style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-bold mb-4">Top countries</h2>
        <?php if (empty($topCountries)): ?>
            <p class="text-sm text-slate-400">No country data recorded yet (set a geo/CDN country header).</p>
        <?php else: ?>
            <table class="w-full text-sm">
                <thead><tr class="text-left text-slate-400 text-xs uppercase"><th class="pb-2">Country</th><th class="pb-2 text-right">Visits</th></tr></thead>
                <tbody>
                <?php foreach ($topCountries as $tc): ?>
                    <tr class="border-t border-slate-100">
                        <td class="py-2 font-medium text-slate-600"><?= htmlspecialchars(strtoupper((string) $tc['country'])) ?></td>
                        <td class="py-2 text-right"><?= number_format((int) $tc['c']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <form method="get" action="/admin/visitors" class="p-4 border-b border-slate-100 flex flex-wrap items-end gap-3">
        <div>
            <label for="visitor-path" class="block text-xs font-semibold text-slate-500 mb-1">Filter by path</label>
            <input id="visitor-path" name="path" value="<?= htmlspecialchars($path) ?>" placeholder="/pricing" class="border border-slate-300 rounded-lg px-3 py-2 text-sm w-56">
        </div>
        <div>
            <label for="visitor-bots" class="block text-xs font-semibold text-slate-500 mb-1">Traffic</label>
            <select id="visitor-bots" name="bots" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <option value="hide" <?= $hideBots ? 'selected' : '' ?>>Humans only</option>
                <option value="show" <?= !$hideBots ? 'selected' : '' ?>>Include bots</option>
            </select>
        </div>
        <button type="submit" class="bg-slate-900 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Apply</button>
        <span class="text-xs text-slate-400 ml-auto"><?= number_format((int) $pagination['total']) ?> matching visit(s)</span>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[820px]">
            <thead class="bg-slate-50 text-left text-slate-400 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">Path</th>
                    <th class="px-4 py-3">IP</th>
                    <th class="px-4 py-3">Country</th>
                    <th class="px-4 py-3">Referrer</th>
                    <th class="px-4 py-3">Type</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No visits recorded yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $v): ?>
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-3 text-xs whitespace-nowrap text-slate-500"><?= htmlspecialchars((string) $v['visited_at']) ?></td>
                    <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars((string) $v['path']) ?></td>
                    <td class="px-4 py-3 text-xs text-slate-500"><?= htmlspecialchars((string) ($v['ip_address'] ?? '—')) ?></td>
                    <td class="px-4 py-3 text-xs"><?= htmlspecialchars(strtoupper((string) ($v['country'] ?? '—'))) ?></td>
                    <td class="px-4 py-3 text-xs text-slate-400 truncate max-w-[200px]"><?= htmlspecialchars((string) ($v['referrer'] ?? '—')) ?></td>
                    <td class="px-4 py-3">
                        <?php if ((int) ($v['is_bot'] ?? 0) === 1): ?>
                            <span class="text-xs px-2 py-1 rounded-full bg-amber-100 text-amber-700">Bot</span>
                        <?php else: ?>
                            <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700">Visitor</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ((int) $pagination['last_page'] > 1): ?>
        <div class="flex items-center justify-between px-4 py-3 border-t border-slate-100 text-sm">
            <?php $page = (int) $pagination['current_page']; $last = (int) $pagination['last_page']; ?>
            <span class="text-slate-400">Page <?= $page ?> of <?= $last ?></span>
            <div class="flex items-center gap-2">
                <?php $qs = http_build_query(['path' => $path, 'bots' => $hideBots ? 'hide' : 'show']); ?>
                <a href="/admin/visitors?page=<?= max(1, $page - 1) ?>&<?= $qs ?>" class="px-3 py-1.5 rounded-lg border border-slate-300 <?= $page <= 1 ? 'pointer-events-none opacity-40' : 'hover:border-blue-400 text-slate-700' ?>">Previous</a>
                <a href="/admin/visitors?page=<?= min($last, $page + 1) ?>&<?= $qs ?>" class="px-3 py-1.5 rounded-lg border border-slate-300 <?= $page >= $last ? 'pointer-events-none opacity-40' : 'hover:border-blue-400 text-slate-700' ?>">Next</a>
            </div>
        </div>
    <?php endif; ?>
</div>
