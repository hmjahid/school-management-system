<?php
/**
 * A KPI tile: label, value, optional icon, optional sparkline, delta chip.
 *
 * The delta chip carries an arrow glyph as well as a colour, so the direction
 * survives a colour-vision deficiency or a greyscale print. Pass `$statDelta`
 * as a signed percentage; `null` renders "no prior data" instead of a fake 0%.
 *
 * @var string      $statLabel
 * @var string      $statValue      preformatted (already currency/grouped)
 * @var string      $statFoot       escaped text, e.g. "$1.2k this month"
 * @var float|null  $statDelta      signed percentage vs the prior window
 * @var string      $statDeltaLabel e.g. "vs prior 30 days"
 * @var string      $statIcon       raw trusted SVG
 * @var array|null  $statSpark      ChartPayload::spark() spec
 * @var string|null $statHref
 * @var string      $statTone       brand | success | warning | danger | muted
 * @var bool        $statHero       wide accent tile for the primary metric
 * @var string      $statSpan       grid span classes (e.g. 'sm:col-span-2')
 */
$statLabel = $statLabel ?? '';
$statValue = $statValue ?? '—';
$statFoot = $statFoot ?? '';
$statDelta = $statDelta ?? null;
$statDeltaLabel = $statDeltaLabel ?? '';
$statIcon = $statIcon ?? '';
$statSpark = $statSpark ?? null;
$statHref = $statHref ?? null;
$statTone = $statTone ?? 'brand';
$statHero = (bool) ($statHero ?? false);
$statSpan = (string) ($statSpan ?? '');

$isLink = $statHref !== null && $statHref !== '';
$tag = $isLink ? 'a' : 'div';

$showDelta = $statDelta !== null || trim((string) $statDeltaLabel) !== '';
$deltaClass = '';
$deltaGlyph = '';
$deltaText = '';
$deltaAria = '';
if ($statDelta === null) {
    $deltaClass = 'esk-delta esk-delta--flat';
    $deltaGlyph = '→';
    $deltaText = 'no prior data';
    $deltaAria = 'no prior data';
} else {
    $deltaClass = 'esk-delta ' . ($statDelta > 0 ? 'esk-delta--up' : ($statDelta < 0 ? 'esk-delta--down' : 'esk-delta--flat'));
    $deltaGlyph = $statDelta > 0 ? '▲' : ($statDelta < 0 ? '▼' : '■');
    $deltaText = ($statDelta > 0 ? '+' : ($statDelta < 0 ? '−' : '')) . abs(round($statDelta, 1)) . '%';
    $deltaAria = ($statDelta > 0 ? 'up' : ($statDelta < 0 ? 'down' : 'flat')) . ' ' . abs(round($statDelta, 1)) . ' percent';
}
?>
<<?= $tag ?> <?= $isLink ? 'href="' . htmlspecialchars($statHref) . '"' : '' ?> class="esk-stat<?= $isLink ? ' esk-stat--link' : '' ?><?= $statHero ? ' esk-stat--hero' : '' ?><?= $statSpan !== '' ? ' ' . $statSpan : '' ?>"<?= $isLink ? '' : ' role="group"' ?>>
    <div class="esk-stat-head">
        <div class="min-w-0">
            <div class="esk-stat-label"><?= htmlspecialchars($statLabel) ?></div>
            <div class="esk-stat-value esk-tabular"><?= htmlspecialchars($statValue) ?></div>
        </div>
        <?php if ($statIcon !== ''): ?>
            <span class="esk-stat-icon esk-stat-icon--<?= htmlspecialchars($statTone) ?>" aria-hidden="true"><?= $statIcon ?></span>
        <?php endif; ?>
    </div>

    <?php if (is_array($statSpark) && ($statSpark['options']['series'][0]['data'] ?? []) !== []): ?>
        <div
            data-esk-chart="apex"
            data-chart-spec="<?= \App\Services\Analytics\ChartPayload::json($statSpark) ?>"
            class="esk-stat-spark"
            role="img"
            aria-label="<?= htmlspecialchars($statLabel . ' trend') ?>"
        >
            <div data-chart-skeleton class="esk-skeleton absolute inset-0"></div>
            <div data-apex-target class="h-full w-full"></div>
        </div>
    <?php endif; ?>

    <div class="esk-stat-foot">
        <?php if ($showDelta): ?>
            <span class="<?= $deltaClass ?>" title="<?= htmlspecialchars(trim((string) $statDeltaLabel)) ?>">
                <span aria-hidden="true"><?= $deltaGlyph ?></span>
                <?= htmlspecialchars($deltaText) ?>
                <span class="esk-sr-only"><?= htmlspecialchars(trim($deltaAria . ' ' . (string) $statDeltaLabel)) ?></span>
            </span>
        <?php else: ?>
            <span></span>
        <?php endif; ?>
        <?php if ($statFoot !== ''): ?>
            <span class="esk-stat-foot-note"><?= htmlspecialchars($statFoot) ?></span>
        <?php endif; ?>
    </div>
</<?= $tag ?>>
