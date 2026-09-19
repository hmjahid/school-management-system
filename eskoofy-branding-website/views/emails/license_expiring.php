<?php $emailTitle = $emailTitle ?? 'Your license is expiring soon'; ?>
<p style="margin:0 0 16px;color:#334155;font-size:14px;line-height:1.6;font-family:Arial,Helvetica,sans-serif;">
  Hi <?= htmlspecialchars($name ?? 'there') ?>,
</p>
<p style="margin:0 0 16px;color:#334155;font-size:14px;line-height:1.6;font-family:Arial,Helvetica,sans-serif;">
  Heads-up: one of your licenses is close to expiring. No action needed for your data — for self-hosted
  deployments your data stays on your server either way — but renewing keeps your subscription and
  support running smoothly.
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
  <tr>
    <td style="padding:14px 16px;background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;color:#334155;font-family:Arial,Helvetica,sans-serif;">
      <div style="font-family:monospace;font-size:14px;color:#1d4ed8;margin-bottom:6px;"><?= htmlspecialchars($licenseKey ?? '') ?></div>
      <?php if (!empty($expiresAt)): ?>
        <div style="color:#b45309;font-size:13px;">Expires <?= date('F j, Y', strtotime((string) $expiresAt)) ?></div>
      <?php endif; ?>
    </td>
  </tr>
</table>
<a href="<?= htmlspecialchars($renewUrl ?? '/account/licenses') ?>" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;padding:12px 24px;border-radius:8px;">
  Review my renewal
</a>