<?php
/**
 * A chart panel body: fixed-height wrapper + lazy-mounted vendored chart.
 *
 * Deliberately dumb. The caller passes a spec built by
 * {@see \App\Services\Analytics\ChartPayload}; this renders the mount hooks that
 * `public/js/charts.js` looks for. No chart maths, no colour decisions, no
 * inline `<script>` — the spec travels as a JSON attribute so a strict CSP and a
 * page cache both survive.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §5.3
 *
 * @var string $chartLib   'chartjs' | 'apex'
 * @var array  $chartSpec  JSON-serialisable config
 * @var string $chartLabel accessible summary (role="img")
 * @var string $chartSize  '', 'sm' | 'lg'
 * @var string $chartLegend optional legend markup rendered below the canvas
 */
$chartLib   = $chartLib ?? 'chartjs';
$chartSpec  = $chartSpec ?? [];
$chartLabel = $chartLabel ?? 'Chart';
$chartSize  = $chartSize ?? '';
$chartLegend = $chartLegend ?? '';
$sizeClass  = in_array($chartSize, ['sm', 'lg'], true) ? ' esk-chart--' . $chartSize : '';
?>
<div
    data-esk-chart="<?= htmlspecialchars($chartLib) ?>"
    data-chart-spec="<?= \App\Services\Analytics\ChartPayload::json((array) $chartSpec) ?>"
    class="esk-chart<?= $sizeClass ?>"
    role="img"
    aria-label="<?= htmlspecialchars($chartLabel) ?>"
>
    <div data-chart-skeleton class="esk-skeleton absolute inset-0"></div>
    <?php if ($chartLib === 'chartjs'): ?>
        <canvas aria-hidden="true"></canvas>
    <?php else: ?>
        <div data-apex-target class="h-full w-full"></div>
    <?php endif; ?>
</div>
<?php if ($chartLegend !== ''): ?>
    <div class="esk-chart-legend"><?= $chartLegend ?></div>
<?php endif; ?>
