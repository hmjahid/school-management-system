<?php
/**
 * Empty state: icon, headline, body, optional CTA.
 *
 * An empty state teaches the space ("what belongs here, why it matters, what
 * fills it") — a bare "No data" label is an omission, not a state.
 *
 * @var string      $emptyTitle
 * @var string      $emptyBody
 * @var string      $emptyIcon raw trusted SVG
 * @var string|null $emptyHref
 * @var string      $emptyCta
 */
$emptyTitle = $emptyTitle ?? 'Nothing here yet';
$emptyBody = $emptyBody ?? '';
$emptyIcon = $emptyIcon ?? '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>';
$emptyHref = $emptyHref ?? null;
$emptyCta = $emptyCta ?? '';
?>
<div class="esk-empty">
    <span class="esk-empty-icon" aria-hidden="true"><?= $emptyIcon ?></span>
    <div class="esk-empty-title"><?= htmlspecialchars($emptyTitle) ?></div>
    <?php if ($emptyBody !== ''): ?>
        <p class="esk-empty-body"><?= htmlspecialchars($emptyBody) ?></p>
    <?php endif; ?>
    <?php if ($emptyHref !== null && $emptyHref !== '' && $emptyCta !== ''): ?>
        <a href="<?= htmlspecialchars($emptyHref) ?>" class="esk-btn esk-btn--sm esk-btn--ghost"><?= htmlspecialchars($emptyCta) ?></a>
    <?php endif; ?>
</div>
