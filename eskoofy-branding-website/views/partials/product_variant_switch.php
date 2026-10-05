<?php
/**
 * Market-build switcher for a single product page.
 *
 * The product stays fixed; only the **variant** column changes, so this must
 * read as a two-way choice inside one product rather than a second product.
 * It is a real link pair (`?variant=…`) so the page is shareable, bookmarkable
 * and works with JavaScript disabled.
 *
 * @var string      $variant      the currently displayed variant
 * @var string      $productCode  Catalog code, used to ring the matrix cell
 * @var string|null $productPath  absolute path of this product page
 */
use App\Services\VariantResolver;

$variant = $variant ?? VariantResolver::INT;
$productCode = $productCode ?? '';
$productPath = $productPath ?? \App\Services\Catalog::page($productCode);
?>
<div class="esk-variant-switch" data-variant-switch>
    <span class="esk-variant-switch-label"><?= __('matrix.variant_axis') ?></span>
    <?php foreach (VariantResolver::all() as $variantCode): ?>
        <?php
        $href = $productPath . (str_contains($productPath, '?') ? '&' : '?') . 'variant=' . rawurlencode($variantCode);
        $on = $variantCode === $variant;
        ?>
        <a href="<?= htmlspecialchars($href) ?>"
           data-variant="<?= htmlspecialchars($variantCode) ?>"
           <?= $on ? 'aria-current="true"' : '' ?>
           class="esk-variant-switch-option<?= $on ? ' is-active esk-variant-chip--' . htmlspecialchars($variantCode) : '' ?>">
            <span class="esk-variant-switch-name"><?= htmlspecialchars(VariantResolver::label($variantCode, \App\Services\I18n::current())) ?></span>
            <span class="esk-variant-switch-meta">
                <?= htmlspecialchars(VariantResolver::currencyCode($variantCode)) ?>
                · <?= htmlspecialchars(implode(', ', VariantResolver::gatewayCodes($variantCode))) ?>
            </span>
        </a>
    <?php endforeach; ?>
</div>
