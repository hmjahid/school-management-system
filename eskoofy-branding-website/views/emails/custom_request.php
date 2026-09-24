<?php $emailTitle = $emailTitle ?? 'New custom order request'; ?>
<p style="margin:0 0 16px;color:#334155;font-size:14px;line-height:1.6;font-family:Arial,Helvetica,sans-serif;">
  A visitor requested custom work or an extra feature via the website (request #<?= (int) $id ?>). Reply via Admin → Custom orders.
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td style="padding:6px 0;color:#64748b;font-size:13px;font-family:Arial,Helvetica,sans-serif;width:130px;">Name</td>
    <td style="padding:6px 0;font-size:14px;color:#0f172a;font-family:Arial,Helvetica,sans-serif;"><?= htmlspecialchars($name ?? '—') ?></td>
  </tr>
  <tr>
    <td style="padding:6px 0;color:#64748b;font-size:13px;font-family:Arial,Helvetica,sans-serif;">Email</td>
    <td style="padding:6px 0;font-size:14px;color:#0f172a;font-family:Arial,Helvetica,sans-serif;"><?= htmlspecialchars($email ?? '—') ?></td>
  </tr>
  <tr>
    <td style="padding:6px 0;color:#64748b;font-size:13px;font-family:Arial,Helvetica,sans-serif;">Phone</td>
    <td style="padding:6px 0;font-size:14px;color:#0f172a;font-family:Arial,Helvetica,sans-serif;"><?= htmlspecialchars($phone ?? '—') ?></td>
  </tr>
  <tr>
    <td style="padding:6px 0;color:#64748b;font-size:13px;font-family:Arial,Helvetica,sans-serif;">Product</td>
    <td style="padding:6px 0;font-size:14px;color:#0f172a;font-family:Arial,Helvetica,sans-serif;"><?= htmlspecialchars($product ?? 'multi') ?></td>
  </tr>
  <tr>
    <td style="padding:6px 0;color:#64748b;font-size:13px;font-family:Arial,Helvetica,sans-serif;">Request type</td>
    <td style="padding:6px 0;font-size:14px;color:#0f172a;font-family:Arial,Helvetica,sans-serif;"><?= htmlspecialchars(str_replace('_', ' ', (string) ($request_type ?? ''))) ?></td>
  </tr>
  <?php if (!empty($subject)): ?>
  <tr>
    <td style="padding:6px 0;color:#64748b;font-size:13px;font-family:Arial,Helvetica,sans-serif;">Subject</td>
    <td style="padding:6px 0;font-size:14px;color:#0f172a;font-family:Arial,Helvetica,sans-serif;"><?= htmlspecialchars($subject) ?></td>
  </tr>
  <?php endif; ?>
  <?php if (!empty($budget)): ?>
  <tr>
    <td style="padding:6px 0;color:#64748b;font-size:13px;font-family:Arial,Helvetica,sans-serif;">Budget</td>
    <td style="padding:6px 0;font-size:14px;color:#0f172a;font-family:Arial,Helvetica,sans-serif;"><?= htmlspecialchars($budget) ?></td>
  </tr>
  <?php endif; ?>
  <?php if (!empty($timeline)): ?>
  <tr>
    <td style="padding:6px 0;color:#64748b;font-size:13px;font-family:Arial,Helvetica,sans-serif;">Timeline</td>
    <td style="padding:6px 0;font-size:14px;color:#0f172a;font-family:Arial,Helvetica,sans-serif;"><?= htmlspecialchars($timeline) ?></td>
  </tr>
  <?php endif; ?>
  <tr>
    <td style="padding:6px 0;color:#64748b;font-size:13px;font-family:Arial,Helvetica,sans-serif;vertical-align:top;">Details</td>
    <td style="padding:6px 0;font-size:14px;color:#0f172a;font-family:Arial,Helvetica,sans-serif;line-height:1.6;white-space:pre-wrap;"><?= nl2br(htmlspecialchars($details ?? '')) ?></td>
  </tr>
</table>