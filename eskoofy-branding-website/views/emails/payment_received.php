<?php $emailTitle = $emailTitle ?? 'Payment received'; ?>
<p style="margin:0 0 16px;color:#334155;font-size:14px;line-height:1.6;font-family:Arial,Helvetica,sans-serif;">
  Hi <?= htmlspecialchars($name ?? 'there') ?>,
</p>
<p style="margin:0 0 16px;color:#334155;font-size:14px;line-height:1.6;font-family:Arial,Helvetica,sans-serif;">
  We received your payment. Thank you — here is a quick summary:
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
  <tr>
    <td style="padding:14px 16px;background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;color:#334155;font-family:Arial,Helvetica,sans-serif;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="padding:4px 0;color:#64748b;">Amount</td>
          <td align="right" style="padding:4px 0;font-weight:600;"><?= htmlspecialchars($currency ?? 'USD') ?> <?= htmlspecialchars(number_format((float) ($amount ?? 0), 2)) ?></td>
        </tr>
        <tr>
          <td style="padding:4px 0;color:#64748b;">Reference</td>
          <td align="right" style="padding:4px 0;font-family:monospace;font-size:13px;"><?= htmlspecialchars($reference ?? '—') ?></td>
        </tr>
        <tr>
          <td style="padding:4px 0;color:#64748b;">Method</td>
          <td align="right" style="padding:4px 0;"><?= htmlspecialchars(($gateway ?? '') ?: '—') ?></td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<a href="<?= htmlspecialchars($accountUrl ?? '/account') ?>" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;padding:12px 24px;border-radius:8px;">
  View my licenses
</a>