<?php
/**
 * The single place a raw status string becomes a coloured pill.
 *
 * Convention: raw snake_case status → tone. A caller that already knows the tone
 * (e.g. the license derived status) passes `$pillTone` and wins. Never colour
 * alone — the pill also carries the human label.
 *
 * @var string $pillStatus raw status value
 * @var string $pillLabel  optional label override (defaults to the ucfirst value)
 * @var string $pillTone   optional tone override
 */
$pillStatus = (string) ($pillStatus ?? '');
$pillKey = strtolower(trim($pillStatus));

$toneMap = [
    'paid' => 'success', 'active' => 'success', 'success' => 'success',
    'completed' => 'success', 'approved' => 'success', 'done' => 'success',
    'pending' => 'warning', 'warning' => 'warning', 'in_review' => 'warning',
    'quoting' => 'warning', 'suspended' => 'warning', 'processing' => 'info',
    'new' => 'info', 'info' => 'info', 'open' => 'info',
    'failed' => 'danger', 'cancelled' => 'danger', 'canceled' => 'danger',
    'declined' => 'danger', 'danger' => 'danger', 'error' => 'danger',
    'expired' => 'muted', 'muted' => 'muted', 'archived' => 'muted',
];

$pillTone = $pillTone ?? ($toneMap[$pillKey] ?? 'muted');
$pillLabel = $pillLabel ?? ($pillKey !== '' ? ucfirst(str_replace('_', ' ', $pillKey)) : 'Unknown');
?>
<span class="esk-status esk-status--<?= htmlspecialchars($pillTone) ?>"><?= htmlspecialchars($pillLabel) ?></span>
