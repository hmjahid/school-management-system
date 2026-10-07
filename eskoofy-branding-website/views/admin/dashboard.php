<?php
use App\Services\Analytics\ChartPayload;
use App\Services\Analytics\LicenseService;
use App\Services\Catalog;
use App\Services\VariantResolver;

$adminTitle = 'Dashboard';

/*
 * Every variable is defaulted: this view is also rendered directly by
 * tests/Unit/Views/AdminDashboardViewTest.php, which promotes notices to
 * failures, so a key the controller forgot to pass must degrade, not fatal.
 */
$stats = $stats ?? [];
$range = $range ?? \App\Services\Analytics\DateRange::fromRequest([]);
$filters = $filters ?? ['product' => null, 'variant' => null];
$revenueTrend = $revenueTrend ?? [];
$licenseByStatus = $licenseByStatus ?? [];
$licenseByProduct = $licenseByProduct ?? [];
$licenseStatuses = $licenseStatuses ?? LicenseService::STATUSES;
$productCatalog = $productCatalog ?? Catalog::all();
$reconciliation = $reconciliation ?? ['status' => 'empty', 'note' => '', 'gap' => 0, 'mrr' => 0];
$recentPayments = $recentPayments ?? [];
$recentLicenses = $recentLicenses ?? [];
$expiringLicenses = $expiringLicenses ?? [];
$unreadMessages = $unreadMessages ?? [];
$kpis = $kpis ?? [];
$revenueByVariant = $revenueByVariant ?? [];
$mrrByProduct = $mrrByProduct ?? [];
$byGateway = $byGateway ?? [];
$funnel = $funnel ?? [];
$countries = $countries ?? [];
$renewalRisk = $renewalRisk ?? ['labels' => [], 'series' => []];
$revenueSplit = $revenueSplit ?? ['new' => [], 'renewal' => []];
$licensesSpark = $licensesSpark ?? [];
$customersSpark = $customersSpark ?? [];
$activationsSpark = $activationsSpark ?? [];
$deactivationsSpark = $deactivationsSpark ?? [];
$topCustomers = $topCustomers ?? [];
$activityFeed = $activityFeed ?? [];
$matrix = $matrix ?? [];
$renewalTotal = $renewalTotal ?? 0;

$icons = [
    'money' => '<path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
    'repeat' => '<path d="m17 2 4 4-4 4M3 11v-1a4 4 0 0 1 4-4h14M7 22l-4-4 4-4M21 13v1a4 4 0 0 1-4 4H3"/>',
    'key' => '<path d="m21 2-2 2m-7.6 7.6a5.5 5.5 0 1 1-7.8 7.8 5.5 5.5 0 1 1 7.8-7.8Zm0 0L21 2M15.5 6.5l3 3"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm14 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    'activity' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
    'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2M5.5 5h13l3.5 7v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6l3.5-7Z"/>',
    'card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
    'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0ZM12 9v4M12 17h.01"/>',
    'archive' => '<rect x="2" y="3" width="20" height="5" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8M10 12h4"/>',
    'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M16 13H8M16 17H8"/>',
    'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
    'globe' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/>',
    'tool' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76Z"/>',
];
$svg = static function (string $name, int $size = 20) use ($icons): string {
    return '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($icons[$name] ?? $icons['activity']) . '</svg>';
};

$money = static fn (float $v): string => '$' . number_format($v, $v >= 1000 ? 0 : 2);
$pctDelta = static function (?float $now, ?float $prev): ?float {
    if ($now === null || $prev === null || $prev <= 0) {
        return null;
    }
    return round((($now - $prev) / $prev) * 100, 1);
};
$toneVar = [
    'success' => 'var(--success)', 'warning' => 'var(--warning)',
    'danger' => 'var(--danger)', 'info' => 'var(--info)', 'muted' => 'var(--text-subtle)',
];
$chart = static function (array $vars): void {
    \App\Core\View::partial('admin.partials.chart', $vars);
};
$spark = static fn (array $values, string $color): array => ChartPayload::spark($values, $color);

$totalLicenses = max(1, (int) array_sum($licenseByStatus));
$daysLeft = static fn (string $expires): int => max(0, (int) floor((strtotime($expires) - time()) / 86400));

$rangeLink = static function (string $preset) use ($filters): string {
    $query = ['range' => $preset];
    if (!empty($filters['product'])) {
        $query['product'] = $filters['product'];
    }
    return '/admin/dashboard?' . http_build_query($query);
};

// Alert strip — only rendered when something needs action.
$alerts = [];
$critExpiring = 0;
foreach ($expiringLicenses as $l) {
    if ($daysLeft((string) ($l['expires_at'] ?? 'now')) <= 7) {
        $critExpiring++;
    }
}
if ($critExpiring > 0) {
    $alerts[] = ['tone' => 'danger', 'title' => $critExpiring . ' license(s) expire within 7 days', 'body' => 'Renew now to avoid interruption.', 'href' => '/admin/licenses', 'cta' => 'Review'];
}
if ((float) ($stats['revenue_pending'] ?? 0) > 0) {
    $alerts[] = ['tone' => 'warning', 'title' => 'Pending payments awaiting review', 'body' => $money((float) $stats['revenue_pending']) . ' is sitting unpaid.', 'href' => '/admin/payments', 'cta' => 'Payments'];
}
if ((int) ($stats['unread_messages'] ?? 0) > 0) {
    $alerts[] = ['tone' => 'info', 'title' => (int) $stats['unread_messages'] . ' unread message(s)', 'body' => 'Customers are waiting on a reply.', 'href' => '/admin/messages', 'cta' => 'Inbox'];
}
if (($reconciliation['status'] ?? 'empty') === 'divergent') {
    $alerts[] = ['tone' => 'warning', 'title' => 'Collected revenue diverges from committed MRR', 'body' => 'Check for one-off sales or failed payments.', 'href' => '/admin/subscriptions', 'cta' => 'Subscriptions'];
}
?>

<?php if (!empty($alerts)): ?>
    <div class="esk-alert-strip">
        <?php foreach ($alerts as $a): ?>
            <div class="esk-alert esk-alert--<?= htmlspecialchars($a['tone']) ?>">
                <span aria-hidden="true"><?= $svg('alert', 18) ?></span>
                <span class="esk-alert-title"><?= htmlspecialchars($a['title']) ?></span>
                <span class="esk-alert-body esk-hide-sm"><?= htmlspecialchars($a['body']) ?></span>
                <a class="esk-alert-link" href="<?= htmlspecialchars($a['href']) ?>"><?= htmlspecialchars($a['cta']) ?> →</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="esk-quick-actions mb-6">
    <span class="text-xs uppercase tracking-widest opacity-70 mr-2">Quick actions</span>
    <a href="/admin/licenses/create" class="esk-btn esk-btn--sm">+ Issue license</a>
    <a href="/admin/plans/create" class="esk-btn esk-btn--sm esk-btn--ghost">+ New plan</a>
    <a href="/admin/messages" class="esk-btn esk-btn--sm esk-btn--ghost">Messages</a>
    <a href="/admin/backup" class="esk-btn esk-btn--sm esk-btn--ghost">Backups</a>
    <a href="/admin/payments/export" class="esk-btn esk-btn--sm esk-btn--ghost">Export payments</a>
    <a href="/admin/account" class="esk-btn esk-btn--sm esk-btn--ghost ml-auto">Account</a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6 gap-4 mb-6">
    <?php
    $revKpi = $kpis['revenue'] ?? ['current' => 0, 'delta' => null, 'spark' => []];
    ?>
    <?php \App\Core\View::partial('admin.partials.stat_card', [
        'statLabel' => 'Revenue', 'statValue' => $money((float) ($revKpi['current'] ?? 0)),
        'statDelta' => $revKpi['delta'] ?? null, 'statDeltaLabel' => 'vs prior period',
        'statIcon' => $svg('money', 20), 'statTone' => 'brand',
        'statSpark' => $spark((array) ($revKpi['spark'] ?? []), 'var(--brand)'),
        'statFoot' => $money((float) ($stats['revenue_this_month'] ?? 0)) . ' this month',
        'statHref' => '/admin/payments',
    ]); ?>
    <?php \App\Core\View::partial('admin.partials.stat_card', [
        'statLabel' => 'MRR', 'statValue' => $money((float) ($stats['mrr'] ?? 0)),
        'statDelta' => null, 'statDeltaLabel' => '',
        'statIcon' => $svg('repeat', 20), 'statTone' => 'success',
        'statSpark' => null,
        'statFoot' => 'ARR ' . $money((float) ($stats['arr'] ?? 0)),
        'statHref' => '/admin/subscriptions',
    ]); ?>
    <?php \App\Core\View::partial('admin.partials.stat_card', [
        'statLabel' => 'Active licenses', 'statValue' => number_format((int) ($stats['active_licenses'] ?? 0)),
        'statDelta' => $pctDelta((float) ($kpis['licenses']['current'] ?? 0), (float) ($kpis['licenses']['prev'] ?? 0)),
        'statDeltaLabel' => 'vs prior period',
        'statIcon' => $svg('key', 20), 'statTone' => 'brand',
        'statSpark' => $spark((array) ($kpis['licenses']['spark'] ?? []), 'var(--brand)'),
        'statFoot' => number_format((int) ($kpis['licenses']['current'] ?? 0)) . ' new this period',
        'statHref' => '/admin/licenses',
    ]); ?>
    <?php \App\Core\View::partial('admin.partials.stat_card', [
        'statLabel' => 'Customers', 'statValue' => number_format((int) ($stats['customers'] ?? 0)),
        'statDelta' => $pctDelta((float) ($kpis['customers']['current'] ?? 0), (float) ($kpis['customers']['prev'] ?? 0)),
        'statDeltaLabel' => 'vs prior period',
        'statIcon' => $svg('users', 20), 'statTone' => 'info',
        'statSpark' => $spark((array) ($kpis['customers']['spark'] ?? []), 'var(--info)'),
        'statFoot' => number_format((int) ($kpis['customers']['current'] ?? 0)) . ' new this period',
        'statHref' => '/admin/customers',
    ]); ?>
    <?php \App\Core\View::partial('admin.partials.stat_card', [
        'statLabel' => 'Activations', 'statValue' => number_format((int) ($kpis['activations']['current'] ?? 0)),
        'statDelta' => $pctDelta((float) ($kpis['activations']['current'] ?? 0), (float) ($kpis['activations']['prev'] ?? 0)),
        'statDeltaLabel' => 'vs prior period',
        'statIcon' => $svg('activity', 20), 'statTone' => 'success',
        'statSpark' => $spark((array) ($kpis['activations']['spark'] ?? []), 'var(--success)'),
        'statFoot' => 'installs this period',
        'statHref' => '/admin/licenses',
    ]); ?>
    <?php
    $renewShare = $renewalTotal > 0 ? round(((int) ($kpis['renewals']['current'] ?? 0) / $renewalTotal) * 100) : 0;
    \App\Core\View::partial('admin.partials.stat_card', [
        'statLabel' => 'Renewals', 'statValue' => number_format((int) ($kpis['renewals']['current'] ?? 0)),
        'statDelta' => null, 'statDeltaLabel' => '',
        'statIcon' => $svg('repeat', 20), 'statTone' => 'warning',
        'statSpark' => $spark((array) ($kpis['renewals']['spark'] ?? []), 'var(--warning)'),
        'statFoot' => $renewShare . '% of paid orders',
        'statHref' => '/admin/subscriptions',
    ]); ?>
</div>

<form method="get" action="/admin/dashboard" class="esk-panel mb-6">
    <div class="esk-panel-body flex flex-wrap items-center gap-3">
        <span class="esk-label">Range</span>
        <div class="esk-seg esk-seg--wrap" role="group" aria-label="Date range">
            <?php foreach (['today', '7d', '30d', '90d', '12mo', 'ytd', 'all'] as $preset): ?>
                <?php $presetRange = \App\Services\Analytics\DateRange::fromRequest(['range' => $preset]); ?>
                <a href="<?= htmlspecialchars($rangeLink($preset)) ?>"
                   class="esk-seg-item"
                   <?= $range->preset === $preset ? 'aria-current="true"' : '' ?>>
                    <?= htmlspecialchars($presetRange->label()) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <span class="esk-label ml-auto esk-hide-sm"><?= htmlspecialchars($range->label()) ?></span>

        <label class="flex items-center gap-2 ml-2">
            <span class="esk-label">Product</span>
            <select name="product" class="esk-select">
                <option value="">All products</option>
                <?php foreach ($productCatalog as $product): ?>
                    <option value="<?= htmlspecialchars((string) $product['code']) ?>" <?= ($filters['product'] ?? null) === $product['code'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(Catalog::label((string) $product['code'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <input type="hidden" name="range" value="<?= htmlspecialchars($range->preset) ?>">
        <button type="submit" class="esk-btn esk-btn--sm">Apply</button>

        <?php if (!empty($filters['product'])): ?>
            <a href="<?= htmlspecialchars($rangeLink($range->preset)) ?>" class="esk-btn esk-btn--sm esk-btn--ghost">Clear filters</a>
        <?php endif; ?>
    </div>
</form>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <section class="esk-panel lg:col-span-2">
        <div class="esk-panel-head">
            <div>
                <h2 class="esk-panel-title">Collected revenue — <?= htmlspecialchars($range->label()) ?></h2>
                <p class="esk-panel-sub">Paid payments, USD-normalised. The line is cumulative for the window.</p>
            </div>
            <div class="esk-panel-actions">
                <a href="/admin/payments/export" class="esk-btn esk-btn--sm esk-btn--ghost">Export CSV</a>
                <a href="/admin/payments" class="esk-btn esk-btn--sm esk-btn--ghost">View all</a>
            </div>
        </div>
        <div class="esk-panel-body">
            <?php
            // Always drawn, even at zero: the axis must show every bucket in the
            // window so a month with no sales reads as 0, not as a phantom gap.
            $chart([
                'chartLib' => 'chartjs',
                'chartLabel' => 'Collected revenue over ' . $range->label(),
                'chartSize' => 'lg',
                'chartSpec' => ChartPayload::area($revenueTrend, [
                    'label' => 'Collected', 'valueFormat' => 'currency',
                    'cumulative' => true, 'cumulativeLabel' => 'Cumulative',
                ]),
            ]);
            ?>
            <?php if (array_sum(array_column($revenueTrend, 'value')) <= 0): ?>
                <p class="text-xs text-ink-subtle mt-3">No paid revenue recorded in this window yet.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="esk-panel">
        <div class="esk-panel-head">
            <div>
                <h2 class="esk-panel-title">Revenue by variant</h2>
                <p class="esk-panel-sub">Bangladesh vs international market build.</p>
            </div>
        </div>
        <div class="esk-panel-body">
            <?php if (array_sum($revenueByVariant) > 0): ?>
                <?php
                $vLabels = [];
                $vValues = [];
                foreach (VariantResolver::all() as $v) {
                    $vLabels[] = VariantResolver::shortLabel($v) . ' · ' . VariantResolver::currencySymbol($v);
                    $vValues[] = (float) ($revenueByVariant[$v] ?? 0);
                }
                $chart([
                    'chartLib' => 'apex',
                    'chartLabel' => 'Revenue by variant',
                    'chartSpec' => ChartPayload::donut($vLabels, $vValues, ['var(--v-bd)', 'var(--v-int)'], ['totalLabel' => 'Revenue']),
                ]);
                ?>
            <?php else: ?>
                <?php \App\Core\View::partial('admin.partials.empty_state', [
                    'emptyTitle' => 'No revenue yet', 'emptyBody' => 'Paid payments split by market build will appear here.',
                    'emptyIcon' => $svg('card', 22),
                ]); ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<section class="esk-panel mb-6">
    <div class="esk-panel-head">
        <div>
            <h2 class="esk-panel-title"><?= htmlspecialchars((string) __('matrix.title')) ?></h2>
            <p class="esk-panel-sub"><?= htmlspecialchars((string) __('matrix.sub')) ?></p>
        </div>
    </div>
    <div class="esk-panel-body">
        <?php \App\Core\View::partial('partials.product_variant_matrix', [
            'matrix' => $matrix,
            'matrixMode' => 'admin',
            'matrixTitle' => '',
            'matrixSub' => '',
            'matrixLegend' => true,
            'matrixFootnote' => false,
        ]); ?>
    </div>
</section>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <section class="esk-panel">
        <div class="esk-panel-head">
            <div>
                <h2 class="esk-panel-title">Licenses by status</h2>
                <p class="esk-panel-sub">Expiry is derived from the expiry date, not a stored status.</p>
            </div>
        </div>
        <div class="esk-panel-body">
            <?php if (array_sum($licenseByStatus) > 0): ?>
                <?php
                $statusLabels = [];
                $statusValues = [];
                $statusColors = [];
                foreach ($licenseStatuses as $key => $meta) {
                    $statusLabels[] = $meta['label'];
                    $statusValues[] = (int) ($licenseByStatus[$key] ?? 0);
                    $statusColors[] = $toneVar[$meta['tone']] ?? 'var(--text-subtle)';
                }
                $chart([
                    'chartLib' => 'apex',
                    'chartLabel' => 'Licenses by derived status',
                    'chartSpec' => ChartPayload::donut($statusLabels, $statusValues, $statusColors, ['totalLabel' => 'Licenses']),
                ]);
                ?>
            <?php else: ?>
                <?php \App\Core\View::partial('admin.partials.empty_state', [
                    'emptyTitle' => 'No licenses yet.', 'emptyBody' => 'Issued licenses and their derived status will appear here.',
                    'emptyIcon' => $svg('key', 22), 'emptyHref' => '/admin/licenses/create', 'emptyCta' => 'Issue a license',
                ]); ?>
            <?php endif; ?>

            <ul class="mt-4 space-y-1.5 text-sm">
                <?php foreach ($licenseStatuses as $status => $meta): ?>
                    <li class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-ink-muted">
                            <span class="inline-block h-2.5 w-2.5 rounded-full" style="background:<?= $toneVar[$meta['tone']] ?? 'var(--text-subtle)' ?>"></span>
                            <?= htmlspecialchars($meta['label']) ?>
                        </span>
                        <span class="font-semibold esk-tabular"><?= (int) ($licenseByStatus[$status] ?? 0) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <section class="esk-panel">
        <div class="esk-panel-head">
            <div>
                <h2 class="esk-panel-title">Licenses by product</h2>
                <p class="esk-panel-sub">All four products, always — colours come from the product catalog.</p>
            </div>
        </div>
        <div class="esk-panel-body space-y-4">
            <?php foreach (Catalog::keys() as $productCode): ?>
                <?php
                $product = Catalog::get($productCode);
                $count = (int) ($licenseByProduct[$productCode] ?? 0);
                $pct = $totalLicenses > 0 ? max(0, min(100, (int) round($count / $totalLicenses * 100))) : 0;
                ?>
                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <a href="/admin/licenses?product=<?= htmlspecialchars($productCode) ?>" class="font-medium text-ink-muted hover:underline flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 rounded-full" style="background:<?= htmlspecialchars((string) $product['color']) ?>"></span>
                            <?= htmlspecialchars(Catalog::label($productCode)) ?>
                        </a>
                        <span class="text-ink-muted esk-tabular"><?= $count ?> <span class="text-ink-subtle text-xs">(<?= $pct ?>%)</span></span>
                    </div>
                    <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full" style="width:<?= $pct ?>%;background:<?= htmlspecialchars((string) $product['color']) ?>"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="esk-panel">
        <div class="esk-panel-head">
            <div>
                <h2 class="esk-panel-title">Gateway mix</h2>
                <p class="esk-panel-sub">Collected revenue by payment method.</p>
            </div>
        </div>
        <div class="esk-panel-body">
            <?php if (array_sum($byGateway) > 0): ?>
                <?php
                $gwLabels = [];
                $gwValues = [];
                foreach ($byGateway as $gw => $total) {
                    $gwLabels[] = ucfirst(str_replace('_', ' ', (string) $gw));
                    $gwValues[] = (float) $total;
                }
                $chart([
                    'chartLib' => 'apex',
                    'chartLabel' => 'Revenue by payment gateway',
                    'chartSpec' => ChartPayload::hbar($gwLabels, $gwValues, ['var(--brand)', 'var(--info)', 'var(--success)', 'var(--warning)', 'var(--danger)'], ['name' => 'Revenue']),
                ]);
                ?>
            <?php else: ?>
                <?php \App\Core\View::partial('admin.partials.empty_state', [
                    'emptyTitle' => 'No gateway activity', 'emptyBody' => 'Paid payments will be grouped by gateway here.',
                    'emptyIcon' => $svg('card', 22),
                ]); ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <section class="esk-panel lg:col-span-2">
        <div class="esk-panel-head">
            <div>
                <h2 class="esk-panel-title">Activation flow — <?= htmlspecialchars($range->label()) ?></h2>
                <p class="esk-panel-sub">Installations vs removals across the window.</p>
            </div>
        </div>
        <div class="esk-panel-body">
            <?php
            $actValues = array_column($activationsSpark, 'value');
            $deactValues = array_column($deactivationsSpark, 'value');
            ?>
            <?php if (array_sum($actValues) > 0 || array_sum($deactValues) > 0): ?>
                <?php
                $actLabels = array_column($activationsSpark, 'label');
                if ($actLabels === []) {
                    $actLabels = array_column($deactivationsSpark, 'label');
                }
                $chart([
                    'chartLib' => 'chartjs',
                    'chartLabel' => 'License activations and deactivations',
                    'chartSpec' => ChartPayload::lines($actLabels, [
                        ['label' => 'Activations', 'data' => $actValues, 'color' => 'var(--brand)'],
                        ['label' => 'Deactivations', 'data' => $deactValues, 'color' => 'var(--danger)'],
                    ]),
                ]);
                ?>
            <?php else: ?>
                <?php \App\Core\View::partial('admin.partials.empty_state', [
                    'emptyTitle' => 'No activations yet', 'emptyBody' => 'License installs and removals will be charted here.',
                    'emptyIcon' => $svg('activity', 22),
                ]); ?>
            <?php endif; ?>
        </div>
    </section>

    <div class="space-y-6">
    <section class="esk-panel">
        <div class="esk-panel-head">
            <div>
                <h2 class="esk-panel-title">Traffic → sale</h2>
                <p class="esk-panel-sub">Visitor path to paid order.</p>
            </div>
        </div>
        <div class="esk-panel-body">
            <?php $funnelTotal = array_sum(array_column($funnel, 'value')); ?>
            <?php if ($funnelTotal > 0): ?>
                <?php
                $chart([
                    'chartLib' => 'apex',
                    'chartLabel' => 'Visitor to paid order funnel',
                    'chartSpec' => ChartPayload::funnel(
                        array_column($funnel, 'label'),
                        array_column($funnel, 'value'),
                        ['height' => 250]
                    ),
                ]);
                $visitors = (int) ($funnel[0]['value'] ?? 0);
                $paid = (int) ($funnel[3]['value'] ?? 0);
                ?>
                <p class="text-xs text-ink-subtle mt-3">
                    Conversion: <strong class="text-ink"><?= $visitors > 0 ? round($paid / $visitors * 100, 1) : 0 ?>%</strong>
                    of visitors became paid orders.
                </p>
            <?php else: ?>
                <?php \App\Core\View::partial('admin.partials.empty_state', [
                    'emptyTitle' => 'No traffic recorded', 'emptyBody' => 'Visitor logging must be enabled for the funnel to fill.',
                    'emptyIcon' => $svg('eye', 22), 'emptyHref' => '/admin/visitors', 'emptyCta' => 'Visitor log',
                ]); ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="esk-panel">
        <div class="esk-panel-head">
            <div>
                <h2 class="esk-panel-title">Top countries</h2>
                <p class="esk-panel-sub">Visitor origin, bots excluded.</p>
            </div>
        </div>
        <div class="esk-panel-body">
            <?php if (array_sum($countries) > 0): ?>
                <?php
                $chart([
                    'chartLib' => 'apex',
                    'chartLabel' => 'Visitors by country',
                    'chartSpec' => ChartPayload::donut(
                        array_keys($countries),
                        array_values($countries),
                        ['var(--brand)', 'var(--p-app)', 'var(--p-php)', 'var(--p-theme)', 'var(--p-node)', 'var(--info)', 'var(--text-subtle)'],
                        ['height' => 220, 'legend' => 'bottom']
                    ),
                ]);
                ?>
            <?php else: ?>
                <?php \App\Core\View::partial('admin.partials.empty_state', [
                    'emptyTitle' => 'No visitor geography', 'emptyBody' => 'Country data appears once visitor logging records traffic.',
                    'emptyIcon' => $svg('globe', 22),
                ]); ?>
            <?php endif; ?>
        </div>
    </section>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <section class="esk-panel lg:col-span-2">
        <div class="esk-panel-head">
            <div>
                <h2 class="esk-panel-title">New vs renewal orders</h2>
                <p class="esk-panel-sub">A repeat purchase is a paid order for a license the customer already owned.</p>
            </div>
        </div>
        <div class="esk-panel-body">
            <?php
            $newVals = array_column($revenueSplit['new'], 'value');
            $renVals = array_column($revenueSplit['renewal'], 'value');
            ?>
            <?php if (array_sum($newVals) + array_sum($renVals) > 0): ?>
                <?php
                $chart([
                    'chartLib' => 'chartjs',
                    'chartLabel' => 'New versus renewal orders',
                    'chartSpec' => ChartPayload::stacked(array_column($revenueSplit['new'], 'label'), [
                        ['label' => 'New', 'data' => $newVals, 'color' => 'var(--brand)'],
                        ['label' => 'Renewal', 'data' => $renVals, 'color' => 'var(--success)'],
                    ]),
                ]);
                ?>
            <?php else: ?>
                <?php \App\Core\View::partial('admin.partials.empty_state', [
                    'emptyTitle' => 'No paid orders in this window', 'emptyBody' => 'New and repeat purchases will be split here.',
                    'emptyIcon' => $svg('card', 22),
                ]); ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="esk-panel">
        <div class="esk-panel-head">
            <div>
                <h2 class="esk-panel-title">Recurring revenue by product</h2>
                <p class="esk-panel-sub">Committed MRR across live subscriptions.</p>
            </div>
        </div>
        <div class="esk-panel-body">
            <?php if (array_sum($mrrByProduct) > 0): ?>
                <?php
                $mrrLabels = [];
                $mrrValues = [];
                foreach (Catalog::keys() as $code) {
                    $mrrLabels[] = Catalog::label($code);
                    $mrrValues[] = (float) ($mrrByProduct[$code] ?? 0);
                }
                $chart([
                    'chartLib' => 'apex',
                    'chartLabel' => 'Recurring revenue by product',
                    'chartSpec' => ChartPayload::hbar($mrrLabels, $mrrValues, array_values(Catalog::colors()), ['name' => 'MRR']),
                ]);
                ?>
            <?php else: ?>
                <?php \App\Core\View::partial('admin.partials.empty_state', [
                    'emptyTitle' => 'No recurring revenue', 'emptyBody' => 'Active subscriptions will roll up per product here.',
                    'emptyIcon' => $svg('repeat', 22), 'emptyHref' => '/admin/subscriptions', 'emptyCta' => 'Subscriptions',
                ]); ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <section class="esk-panel lg:col-span-2">
        <div class="esk-panel-head">
            <div>
                <h2 class="esk-panel-title">Licenses expiring in 30 days</h2>
                <p class="esk-panel-sub">Only genuinely active licenses are listed.</p>
            </div>
            <a href="/admin/licenses" class="esk-btn esk-btn--sm esk-btn--ghost">Manage →</a>
        </div>
        <div class="esk-panel-body esk-panel--flush">
            <?php if (!empty($expiringLicenses)): ?>
                <div>
                    <?php foreach ($expiringLicenses as $l): ?>
                        <?php $days = $daysLeft((string) ($l['expires_at'] ?? 'now')); ?>
                        <div class="esk-list-row">
                            <div class="min-w-0">
                                <div class="font-mono text-xs"><?= htmlspecialchars((string) $l['license_key']) ?></div>
                                <div class="text-xs text-ink-subtle truncate"><?= htmlspecialchars((string) ($l['customer_name'] ?? '—')) ?> · <?= htmlspecialchars((string) ($l['customer_email'] ?? '')) ?></div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-semibold <?= $days <= 7 ? 'text-red-600' : 'text-amber-600' ?>"><?= $days ?> day(s)</span>
                                <span class="text-xs text-ink-subtle esk-hide-sm"><?= htmlspecialchars((string) $l['expires_at']) ?></span>
                                <a href="/admin/licenses/<?= (int) $l['id'] ?>" class="esk-btn esk-btn--sm">View</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <?php \App\Core\View::partial('admin.partials.empty_state', [
                    'emptyTitle' => 'All clear', 'emptyBody' => 'No licenses expiring in the next 30 days. All quiet.',
                    'emptyIcon' => $svg('key', 22),
                ]); ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="esk-panel">
        <div class="esk-panel-head">
            <div>
                <h2 class="esk-panel-title">Unread messages</h2>
                <p class="esk-panel-sub">Newest first.</p>
            </div>
            <a href="/admin/messages" class="esk-btn esk-btn--sm esk-btn--ghost">All →</a>
        </div>
        <div class="esk-panel-body esk-panel--flush">
            <?php if (!empty($unreadMessages)): ?>
                <div>
                    <?php foreach (array_slice($unreadMessages, 0, 5) as $m): ?>
                        <div class="esk-list-row">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold"><?= htmlspecialchars((string) ($m['name'] ?? '—')) ?> <span class="text-ink-subtle font-normal">· <?= htmlspecialchars(substr((string) ($m['created_at'] ?? ''), 0, 10)) ?></span></div>
                                <div class="text-xs text-ink-subtle truncate"><?= htmlspecialchars((string) ($m['message'] ?? '')) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <?php \App\Core\View::partial('admin.partials.empty_state', [
                    'emptyTitle' => 'Inbox zero', 'emptyBody' => 'No unread messages. You are all caught up.',
                    'emptyIcon' => $svg('inbox', 22),
                ]); ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <section class="esk-panel">
        <div class="esk-panel-head">
            <div>
                <h2 class="esk-panel-title">Renewal-risk heatmap</h2>
                <p class="esk-panel-sub">Active licenses expiring per product over six months.</p>
            </div>
        </div>
        <div class="esk-panel-body">
            <?php $riskTotal = 0; foreach ($renewalRisk['series'] as $s) { $riskTotal += array_sum($s['data']); } ?>
            <?php if ($riskTotal > 0): ?>
                <?php $chart([
                    'chartLib' => 'apex',
                    'chartLabel' => 'Licenses expiring per product by month',
                    'chartSpec' => ChartPayload::heatmap($renewalRisk['series'], $renewalRisk['labels'], ['height' => 250]),
                ]); ?>
            <?php else: ?>
                <?php \App\Core\View::partial('admin.partials.empty_state', [
                    'emptyTitle' => 'No renewals at risk', 'emptyBody' => 'No active licenses expire in the next six months.',
                    'emptyIcon' => $svg('key', 22),
                ]); ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="esk-panel lg:col-span-2">
        <div class="esk-panel-head">
            <div>
                <h2 class="esk-panel-title">Top customers by collected value</h2>
                <p class="esk-panel-sub">Within <?= htmlspecialchars($range->label()) ?>.</p>
            </div>
            <a href="/admin/customers" class="esk-btn esk-btn--sm esk-btn--ghost">All customers →</a>
        </div>
        <div class="esk-panel-body esk-panel--flush">
            <?php if (!empty($topCustomers)): ?>
                <div class="overflow-x-auto">
                    <table class="esk-table">
                        <thead>
                            <tr><th>Customer</th><th>Email</th><th class="esk-num">Orders</th><th class="esk-num">Collected</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($topCustomers as $c): ?>
                            <tr>
                                <td><a href="/admin/customers/<?= (int) $c['id'] ?>" class="font-medium hover:underline"><?= htmlspecialchars((string) $c['name']) ?></a></td>
                                <td class="text-ink-muted"><?= htmlspecialchars((string) ($c['email'] ?? '')) ?></td>
                                <td class="esk-num esk-tabular"><?= (int) $c['payments'] ?></td>
                                <td class="esk-num esk-tabular font-semibold"><?= $money((float) $c['ltv']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?php \App\Core\View::partial('admin.partials.empty_state', [
                    'emptyTitle' => 'No customer revenue yet', 'emptyBody' => 'Customers with collected payments will be ranked here.',
                    'emptyIcon' => $svg('users', 22),
                ]); ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <section class="esk-panel lg:col-span-2">
        <div class="esk-panel-head">
            <div>
                <h2 class="esk-panel-title">MRR vs collected</h2>
                <p class="esk-panel-sub">Committed recurring value against money actually collected.</p>
            </div>
        </div>
        <div class="esk-panel-body">
            <?php
            $reconTones = [
                'reconciled'          => 'success',
                'divergent'           => 'warning',
                'mrr_without_revenue' => 'warning',
                'revenue_without_mrr' => 'info',
                'empty'               => 'muted',
            ];
            $reconTone = $reconTones[(string) ($reconciliation['status'] ?? 'empty')] ?? 'muted';
            ?>
            <div class="esk-alert esk-alert--<?= $reconTone ?>">
                <div>
                    <div class="esk-alert-title"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) ($reconciliation['status'] ?? 'empty')))) ?></div>
                    <p class="esk-alert-body"><?= htmlspecialchars((string) ($reconciliation['note'] ?? '')) ?></p>
                    <?php if (($reconciliation['status'] ?? 'empty') !== 'empty'): ?>
                        <p class="text-xs font-semibold mt-1">
                            Gap: <?= (($reconciliation['gap'] ?? 0) >= 0 ? '+' : '') . $money((float) ($reconciliation['gap'] ?? 0)) ?>
                            over <?= htmlspecialchars($range->label()) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
                <div class="flex items-center justify-between rounded-lg border border-edge px-3 py-2">
                    <dt class="text-ink-muted">MRR</dt>
                    <dd class="font-semibold esk-tabular"><?= $money((float) ($reconciliation['mrr'] ?? 0)) ?></dd>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-edge px-3 py-2">
                    <dt class="text-ink-muted">Collected (window)</dt>
                    <dd class="font-semibold esk-tabular"><?= $money((float) ($reconciliation['collected_window'] ?? 0)) ?></dd>
                </div>
            </dl>
        </div>
    </section>

    <section class="esk-panel">
        <div class="esk-panel-head">
            <div>
                <h2 class="esk-panel-title">Recent activity</h2>
                <p class="esk-panel-sub">Latest system and admin events.</p>
            </div>
            <a href="/admin/activities" class="esk-btn esk-btn--sm esk-btn--ghost">Log →</a>
        </div>
        <div class="esk-panel-body esk-panel--flush">
            <?php if (!empty($activityFeed)): ?>
                <div class="esk-feed">
                    <?php foreach ($activityFeed as $ev): ?>
                        <div class="esk-feed-item">
                            <span class="esk-feed-icon esk-feed-icon--<?= htmlspecialchars((string) $ev['tone']) ?>"><?= $svg((string) $ev['icon'], 16) ?></span>
                            <div class="esk-feed-text">
                                <div><?= htmlspecialchars((string) $ev['label']) ?></div>
                                <div class="esk-feed-meta"><?= htmlspecialchars((string) $ev['actor']) ?> · <?= htmlspecialchars((string) $ev['created_at']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <?php \App\Core\View::partial('admin.partials.empty_state', [
                    'emptyTitle' => 'No activity yet', 'emptyBody' => 'Admin and system events will stream here.',
                    'emptyIcon' => $svg('activity', 22), 'emptyHref' => '/admin/activities', 'emptyCta' => 'Activity log',
                ]); ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <section class="esk-panel">
        <div class="esk-panel-head">
            <div><h2 class="esk-panel-title">Recent payments</h2></div>
            <div class="esk-panel-actions">
                <a href="/admin/payments/export" class="esk-btn esk-btn--sm esk-btn--ghost">CSV</a>
                <a href="/admin/payments" class="esk-btn esk-btn--sm esk-btn--ghost">View all</a>
            </div>
        </div>
        <div class="esk-panel-body esk-panel--flush">
            <?php if (!empty($recentPayments)): ?>
                <div class="overflow-x-auto">
                    <table class="esk-table">
                        <thead><tr><th>Ref</th><th>Customer</th><th class="esk-num">Amount</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($recentPayments as $p): ?>
                            <tr>
                                <td class="font-mono text-xs"><?= htmlspecialchars((string) $p['reference']) ?></td>
                                <td><?= htmlspecialchars((string) ($p['customer_name'] ?? '—')) ?></td>
                                <td class="esk-num esk-tabular">$<?= number_format((float) $p['amount'], 2) ?></td>
                                <td><?php \App\Core\View::partial('admin.partials.status_pill', ['pillStatus' => (string) $p['status']]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?php \App\Core\View::partial('admin.partials.empty_state', [
                    'emptyTitle' => 'No payments yet', 'emptyBody' => 'The latest transactions will be listed here.',
                    'emptyIcon' => $svg('card', 22),
                ]); ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="esk-panel">
        <div class="esk-panel-head">
            <div><h2 class="esk-panel-title">Recent licenses</h2></div>
            <a href="/admin/licenses" class="esk-btn esk-btn--sm esk-btn--ghost">View all</a>
        </div>
        <div class="esk-panel-body esk-panel--flush">
            <?php if (!empty($recentLicenses)): ?>
                <div class="overflow-x-auto">
                    <table class="esk-table">
                        <thead><tr><th>Key</th><th>Customer</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($recentLicenses as $l): ?>
                            <?php $meta = LicenseService::statusMeta((string) ($l['status'] ?? '')); ?>
                            <tr>
                                <td class="font-mono text-xs"><?= htmlspecialchars((string) $l['license_key']) ?></td>
                                <td><?= htmlspecialchars((string) ($l['customer_name'] ?? '—')) ?></td>
                                <td>
                                    <?php \App\Core\View::partial('admin.partials.status_pill', [
                                        'pillStatus' => (string) ($l['status'] ?? ''),
                                        'pillLabel' => (string) $meta['label'],
                                        'pillTone' => (string) $meta['tone'],
                                    ]); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?php \App\Core\View::partial('admin.partials.empty_state', [
                    'emptyTitle' => 'No licenses yet', 'emptyBody' => 'Newly issued licenses will be listed here.',
                    'emptyIcon' => $svg('key', 22),
                ]); ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php
/*
 * Product fleet status (W12). Replaces the old strip that shipped hardcoded
 * `localhost:8000/8051/8080/3000` fallbacks into production UI: an unset
 * dashboard URL now reads honestly as "not configured" instead of pointing at a
 * developer machine.
 */
$fleet = [
    'app'   => trim((string) \App\Models\Settings::get('products.dashboards.app', '')),
    'php'   => trim((string) \App\Models\Settings::get('products.dashboards.php', '')),
    'theme' => trim((string) \App\Models\Settings::get('products.dashboards.theme', '')),
    'node'  => trim((string) \App\Models\Settings::get('products.dashboards.node', '')),
];
$fleetConfigured = count(array_filter($fleet, static fn (string $url): bool => $url !== ''));
?>
<section class="esk-panel mt-6">
    <div class="esk-panel-head">
        <div>
            <h2 class="esk-panel-title">Product fleets</h2>
            <p class="esk-panel-sub"><?= $fleetConfigured ?>/4 product dashboards configured.</p>
        </div>
        <a href="/admin/settings" class="esk-btn esk-btn--sm esk-btn--ghost">Configure →</a>
    </div>
    <div class="esk-panel-body">
        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-3">
            <?php foreach (Catalog::keys() as $code): ?>
                <?php $url = $fleet[$code] ?? ''; ?>
                <div class="flex items-center justify-between gap-2 rounded-lg border border-edge px-3 py-2.5">
                    <span class="flex items-center gap-2 text-sm min-w-0">
                        <span class="inline-block h-2.5 w-2.5 rounded-full flex-shrink-0" style="background:<?= htmlspecialchars(Catalog::color($code)) ?>"></span>
                        <span class="truncate"><?= htmlspecialchars(Catalog::label($code)) ?></span>
                    </span>
                    <?php if ($url !== ''): ?>
                        <a class="esk-btn esk-btn--sm esk-btn--ghost" href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener">Open</a>
                    <?php else: ?>
                        <?php \App\Core\View::partial('admin.partials.status_pill', ['pillStatus' => 'muted', 'pillLabel' => 'Not configured', 'pillTone' => 'muted']); ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
