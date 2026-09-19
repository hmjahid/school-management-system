<?php $emailTitle = $emailTitle ?? 'New message from the contact form'; ?>
<p style="margin:0 0 16px;color:#334155;font-size:14px;line-height:1.6;font-family:Arial,Helvetica,sans-serif;">
  A visitor sent a message through the website:
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td style="padding:6px 0;color:#64748b;font-size:13px;font-family:Arial,Helvetica,sans-serif;width:90px;">Name</td>
    <td style="padding:6px 0;font-size:14px;color:#0f172a;font-family:Arial,Helvetica,sans-serif;"><?= htmlspecialchars($name ?? '—') ?></td>
  </tr>
  <tr>
    <td style="padding:6px 0;color:#64748b;font-size:13px;font-family:Arial,Helvetica,sans-serif;">Email</td>
    <td style="padding:6px 0;font-size:14px;color:#0f172a;font-family:Arial,Helvetica,sans-serif;"><?= htmlspecialchars($email ?? '—') ?></td>
  </tr>
  <?php if (!empty($topic)): ?>
  <tr>
    <td style="padding:6px 0;color:#64748b;font-size:13px;font-family:Arial,Helvetica,sans-serif;">Topic</td>
    <td style="padding:6px 0;font-size:14px;color:#0f172a;font-family:Arial,Helvetica,sans-serif;"><?= htmlspecialchars($topic) ?></td>
  </tr>
  <?php endif; ?>
  <?php if (!empty($subject)): ?>
  <tr>
    <td style="padding:6px 0;color:#64748b;font-size:13px;font-family:Arial,Helvetica,sans-serif;">Subject</td>
    <td style="padding:6px 0;font-size:14px;color:#0f172a;font-family:Arial,Helvetica,sans-serif;"><?= htmlspecialchars($subject) ?></td>
  </tr>
  <?php endif; ?>
  <tr>
    <td style="padding:6px 0;color:#64748b;font-size:13px;font-family:Arial,Helvetica,sans-serif;vertical-align:top;">Message</td>
    <td style="padding:6px 0;font-size:14px;color:#0f172a;font-family:Arial,Helvetica,sans-serif;line-height:1.6;white-space:pre-wrap;"><?= nl2br(htmlspecialchars($message ?? '')) ?></td>
  </tr>
</table>