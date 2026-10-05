<?php
/**
 * The Product x Variant matrix — one component, used in five places.
 *
 * Rows are **products** (what you deploy). Columns are **variants** (which
 * market build). The two axes are styled orthogonally on purpose so a visitor
 * can never confuse them: products get a rounded pill in the product brand
 * colour, variants get a square-corner chip with a diagonal hatch.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §4
 *
 * Expected variables (all optional except `$matrix`):
 *   array        $matrix           from {@see \App\Services\ProductMatrix::build()}
 *   string       $matrixMode       'full' | 'compact' | 'admin'   (default 'full')
 *   string       $matrixTitle      overrides `matrix.title`; '' hides the heading
 *   string       $matrixSub        overrides `matrix.sub`;   '' hides the intro
 *   bool         $matrixLegend     show the one-line explainer   (default true)
 *   string|null  $matrixHighlight  "product:variant" cell to ring (choose result)
 *   bool         $matrixFootnote   show the BDT derivation note  (default true)
 *
 * @var array $matrix
 */
use App\Services\ProductMatrix;

$matrix = $matrix ?? [];
$mode = $matrixMode ?? 'full';
$isCompact = $mode === 'compact';
$showLegend = $matrixLegend ?? true;
$showFootnote = $matrixFootnote ?? true;
$highlight = $matrixHighlight ?? null;

$rows = (array) ($matrix['rows'] ?? []);
$variantColumns = (array) ($matrix['variants'] ?? []);

if ($rows === [] || $variantColumns === []) {
    return;
}

$title = (string) ($matrixTitle ?? __('matrix.title'));
$sub = (string) ($matrixSub ?? __('matrix.sub'));
$bdRate = (float) ($matrix['rate'] ?? 0);

$fmtPrice = static function (array $cell): string {
    $price = (array) ($cell['price'] ?? []);
    if (($price['available'] ?? false) !== true) {
        return '';
    }

    $decimals = ($price['currency'] ?? 'USD') === 'BDT' ? 0 : 2;
    $formatted = number_format((float) ($price['amount'] ?? 0), $decimals);

    return (string) ($price['symbol'] ?? '$') . $formatted;
};

$fmtPlanCount = static function (int $count): string {
    return $count === 1
        ? __('matrix.plan_one')
        : __('matrix.plan_other', ['n' => (string) $count]);
};
?>
<section class="esk-matrix"<?= $title !== '' ? ' aria-labelledby="esk-matrix-title"' : ' aria-label="' . htmlspecialchars((string) __('matrix.legend')) . '"' ?>>
    <?php if ($title !== ''): ?>
        <h2 id="esk-matrix-title" class="text-2xl font-extrabold text-slate-900"><?= htmlspecialchars($title) ?></h2>
    <?php endif; ?>
    <?php if ($sub !== ''): ?>
        <p class="text-slate-500 mt-2 max-w-3xl"><?= htmlspecialchars($sub) ?></p>
    <?php endif; ?>

    <?php if ($showLegend): ?>
        <p class="esk-matrix-legend mt-4">
            <span class="esk-matrix-legend-item">
                <span class="esk-legend-axis"><?= htmlspecialchars((string) __('variant.product')) ?></span>
                <?= htmlspecialchars((string) __('matrix.legend_product')) ?>
            </span>
            <span class="esk-matrix-legend-item">
                <span class="esk-legend-axis esk-legend-axis--variant"><?= htmlspecialchars((string) __('variant.label')) ?></span>
                <?= htmlspecialchars((string) __('matrix.legend_variant')) ?>
            </span>
        </p>
    <?php endif; ?>

    <div class="esk-matrix-grid mt-6" role="table" aria-label="<?= htmlspecialchars((string) __('matrix.legend')) ?>">
        <?php /* ---- column headers: the variant axis ---- */ ?>
        <div class="esk-matrix-corner" role="columnheader">
            <span class="esk-legend-axis"><?= htmlspecialchars((string) __('variant.product')) ?></span>
            <span class="esk-matrix-corner-glyph" aria-hidden="true">▾</span>
        </div>
        <?php foreach ($variantColumns as $column): ?>
            <div class="esk-matrix-head esk-variant-chip esk-variant-chip--<?= htmlspecialchars((string) $column['code']) ?>"
                 role="columnheader">
                <span class="esk-variant-chip-label"><?= htmlspecialchars((string) $column['label']) ?></span>
                <span class="esk-variant-chip-gateways"><?= htmlspecialchars((string) $column['desc']) ?></span>
            </div>
        <?php endforeach; ?>

        <?php /* ---- one row per product ---- */ ?>
        <?php foreach ($rows as $row): ?>
            <?php
            $product = (array) $row['product'];
            $code = (string) ($row['code'] ?? $product['code']);
            $color = (string) ($row['color'] ?? '#64748b');
            $cells = (array) ($row['cells'] ?? []);
            ?>
            <div class="esk-matrix-rowlabel" role="rowheader">
                <span class="esk-product-pill" style="--pill:<?= htmlspecialchars($color) ?>">
                    <?= htmlspecialchars((string) $row['label']) ?>
                </span>
                <span class="esk-matrix-rowmeta"><?= htmlspecialchars((string) $row['tag']) ?><?= ($row['stack'] ?? '') !== '' ? ' · ' . htmlspecialchars((string) $row['stack']) : '' ?></span>
                <?php if (!empty($row['metric'])): ?>
                    <span class="esk-matrix-metric"><?= htmlspecialchars((string) $row['metric']) ?></span>
                <?php endif; ?>
            </div>

            <?php foreach ($variantColumns as $column): ?>
                <?php
                $cell = (array) ($cells[$column['code']] ?? []);
                $offered = (bool) ($cell['offered'] ?? false);
                $href = (string) ($cell['href'] ?? ProductMatrix::REQUEST_PATH);
                $price = (array) ($cell['price'] ?? []);
                $isHighlighted = $highlight !== null && $highlight === $code . ':' . $column['code'];
                ?>
                <?php if ($offered): ?>
                    <a class="esk-matrix-cell esk-matrix-cell--offered<?= $isHighlighted ? ' esk-matrix-cell--highlight' : '' ?>"
                       href="<?= htmlspecialchars($href) ?>"
                       style="--pill:<?= htmlspecialchars($color) ?>"
                       role="cell"
                       aria-label="<?= htmlspecialchars((string) __('matrix.cell_aria', [
                           'product' => (string) $row['label'],
                           'variant' => (string) $column['label'],
                       ])) ?>">
                        <?php if ($isCompact): ?>
                            <span class="esk-matrix-cell-price"><?= htmlspecialchars($fmtPrice($cell)) ?></span>
                            <span class="esk-matrix-cell-plan"><?= htmlspecialchars($fmtPlanCount((int) ($cell['plan_count'] ?? 0))) ?></span>
                        <?php else: ?>
                            <span class="esk-matrix-cell-price"><?= htmlspecialchars($fmtPrice($cell)) ?></span>
                            <span class="esk-matrix-cell-plan"><?= htmlspecialchars($fmtPlanCount((int) ($cell['plan_count'] ?? 0))) ?></span>
                            <?php if (!empty($row['metric'])): ?>
                                <span class="esk-matrix-cell-metric"><?= htmlspecialchars((string) $row['metric']) ?></span>
                            <?php endif; ?>
                            <span class="esk-matrix-cell-cta"><?= htmlspecialchars((string) __('matrix.cell_view')) ?> →</span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <?php /* Rule 2: "not offered" is a state, never a blank and never a 0. */ ?>
                    <div class="esk-matrix-cell esk-matrix-cell--empty<?= $isHighlighted ? ' esk-matrix-cell--highlight' : '' ?>"
                         style="--pill:<?= htmlspecialchars($color) ?>"
                         role="cell">
                        <span class="esk-matrix-cell-dash" aria-hidden="true">—</span>
                        <span class="esk-matrix-cell-plan"><?= htmlspecialchars((string) __('matrix.cell_empty')) ?></span>
                        <a class="esk-matrix-cell-cta" href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars((string) __('matrix.cell_request')) ?></a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

    <?php if ($showFootnote && $bdRate > 0): ?>
        <p class="esk-matrix-footnote mt-4">
            <?= htmlspecialchars((string) __('matrix.footnote_bdt', ['rate' => number_format($bdRate, 2)])) ?>
        </p>
    <?php endif; ?>
</section>
