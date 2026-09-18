<?php $emailTitle = $emailTitle ?? 'Welcome to ' . ($brandName ?? 'Eskoofy'); ?>
<p style="margin:0 0 16px;color:#334155;font-size:14px;line-height:1.6;font-family:Arial,Helvetica,sans-serif;">
  Hi <?= htmlspecialchars($name ?? 'there') ?>,
</p>
<p style="margin:0 0 16px;color:#334155;font-size:14px;line-height:1.6;font-family:Arial,Helvetica,sans-serif;">
  Welcome aboard! Your account is ready, and in just a few minutes you can pick a plan,
  get your license key, and bring your school onto one platform — admissions, attendance,
  fees, exams, results and more.
</p>
<a href="<?= htmlspecialchars($accountUrl ?? '/account') ?>" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;padding:12px 24px;border-radius:8px;">
  Go to my account
</a>
<p style="margin:24px 0 0;color:#94a3b8;font-size:12px;line-height:1.5;font-family:Arial,Helvetica,sans-serif;">
  No student or staff limits. Your data always stays yours. Cancel or renew anytime from your account.
</p>