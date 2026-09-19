<?php
$brandName = $brandName ?? 'Eskoofy';
$tagline   = $tagline ?? '';
$emailTitle = $emailTitle ?? '';
$year      = $year ?? (int) date('Y');
$siteUrl   = $siteUrl ?? '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($emailTitle) ?></title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:24px 16px;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 24px rgba(15,23,42,0.08);">
        <tr style="background:linear-gradient(135deg,#1e1b4b 0%,#1e3a8a 55%,#2563eb 100%);">
          <td style="padding:24px 32px;">
            <div style="font-size:20px;font-weight:700;color:#ffffff;letter-spacing:-0.02em;"><?= htmlspecialchars($brandName) ?></div>
            <?php if ($tagline !== ''): ?>
              <div style="font-size:12px;color:#bfdbfe;margin-top:2px;"><?= htmlspecialchars($tagline) ?></div>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <td style="padding:32px;">
            <h1 style="margin:0 0 16px;font-size:20px;color:#0f172a;font-family:Arial,Helvetica,sans-serif;"><?= htmlspecialchars($emailTitle) ?></h1>
            <?= $contentHtml ?>
          </td>
        </tr>
        <tr>
          <td style="padding:20px 32px;border-top:1px solid #e2e8f0;background-color:#f8fafc;">
            <div style="font-size:12px;color:#64748b;font-family:Arial,Helvetica,sans-serif;">
              <?= htmlspecialchars($brandName) ?> · <a href="<?= htmlspecialchars($siteUrl) ?>" style="color:#2563eb;text-decoration:none;"><?= htmlspecialchars($siteUrl) ?></a>
              <br>© <?= (int) $year ?> <?= htmlspecialchars($brandName) ?>. All rights reserved.
            </div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>