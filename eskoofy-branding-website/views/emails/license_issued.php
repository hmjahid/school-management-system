<?php $emailTitle = $emailTitle ?? 'Your license key is ready'; ?>
<p style="margin:0 0 16px;color:#334155;font-size:14px;line-height:1.6;font-family:Arial,Helvetica,sans-serif;">
  Hi <?= htmlspecialchars($name ?? 'there') ?>,
</p>
<p style="margin:0 0 8px;color:#334155;font-size:14px;line-height:1.6;font-family:Arial,Helvetica,sans-serif;">
  Your license for <strong><?= htmlspecialchars($product ?? 'Eskoofy') ?></strong> is ready. Keep this key safe — you will use it to activate your school site:
</p>
<p style="margin:0 0 20px;padding:14px 18px;background-color:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;font-family:monospace;font-size:15px;color:#1d4ed8;letter-spacing:0.05em;">
  <?= htmlspecialchars($licenseKey ?? '') ?>
</p>
<?php if (!empty($expiresAt)): ?>
  <p style="margin:0 0 20px;color:#334155;font-size:14px;line-height:1.6;font-family:Arial,Helvetica,sans-serif;">
    Your subscription runs until <strong><?= date('F j, Y', strtotime((string) $expiresAt)) ?></strong>. Renewals are simple and covered from your account.
  </p>
<?php endif; ?>
<a href="<?= htmlspecialchars($accountUrl ?? '/account') ?>" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;padding:12px 24px;border-radius:8px;">
  Manage my licenses
</a>