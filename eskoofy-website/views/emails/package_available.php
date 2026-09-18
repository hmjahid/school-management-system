<?php
declare(strict_types=1);
/** @var string $name @var string $product @var string $version @var string $notes */
?>
<h2 style="margin:0 0 16px;font-size:20px;color:#0f172a;">Your <?= htmlspecialchars($product) ?> package is ready</h2>
<p style="margin:0 0 16px;color:#334155;">Hello <?= htmlspecialchars($name) ?>,</p>
<p style="margin:0 0 16px;color:#334155;">A new release of your <strong><?= htmlspecialchars($product) ?></strong> package (v<?= htmlspecialchars($version) ?>) is available to download from your account.</p>
<?php if (!empty($notes)): ?>
    <p style="margin:0 0 16px;color:#475569;background:#f1f5f9;padding:12px 16px;border-radius:8px;"><?= nl2br(htmlspecialchars($notes)) ?></p>
<?php endif; ?>
<p style="margin:0 0 20px;color:#334155;">Log in to your dashboard to download it and to access your license keys and setup guides.</p>
<p style="margin:0;"><a href="<?= htmlspecialchars($siteUrl) ?>/account/downloads" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:600;">Open downloads</a></p>