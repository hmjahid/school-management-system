<?php $pageTitle = 'Student ID Card'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID Card - <?= e($card['student_name'] ?? '') ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 40px; display: flex; justify-content: center; }
        .card { width: 340px; background: #fff; border: 2px solid #1d4ed8; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,.1); }
        .header { background: #1d4ed8; color: #fff; text-align: center; padding: 14px; }
        .header .school { font-size: 15px; font-weight: 700; }
        .header .type { font-size: 11px; opacity: .85; }
        .body { padding: 20px; text-align: center; }
        .photo { width: 64px; height: 64px; border-radius: 50%; background: #e5e7eb; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: #9ca3af; font-weight: 700; overflow: hidden; }
        .photo img { width: 100%; height: 100%; object-fit: cover; }
        .name { font-size: 15px; font-weight: 700; color: #111827; }
        .meta { font-size: 12px; color: #6b7280; margin-top: 2px; }
        .meta b { color: #374151; }
        .footer { background: #f9fafb; text-align: center; padding: 8px; font-size: 11px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        <div class="school"><?= e($settings['school_name'] ?? 'School') ?></div>
        <div class="type">STUDENT IDENTITY CARD</div>
    </div>
    <div class="body">
        <div class="photo">
            <?php $initials = strtoupper(substr(trim($card['student_name'] ?? 'S'), 0, 1)); ?>
            <?= e($initials) ?>
        </div>
        <div class="name"><?= e($card['student_name'] ?? '') ?></div>
        <div class="meta">Class: <b><?= e($card['class_name'] ?? '') ?></b> | Section: <b><?= e($card['section_name'] ?? '-') ?></b></div>
        <div class="meta">Roll: <b><?= e($card['roll_number'] ?? '-') ?></b></div>
        <div class="meta">ID No: <b><?= e($card['id_card_number'] ?? '') ?></b></div>
        <?php if (!empty($card['blood_group'])): ?>
        <div class="meta">Blood: <b><?= e($card['blood_group']) ?></b></div>
        <?php endif; ?>
    </div>
    <div class="footer">
        Issued: <?= e(date('d M Y', strtotime($card['issue_date'] ?? 'now'))) ?>
        <?php if (!empty($card['expiry_date'])): ?> | Expires: <?= e(date('d M Y', strtotime($card['expiry_date']))) ?><?php endif; ?>
    </div>
</div>
<?php if (empty($preview)): ?>
<script>window.onload = function(){ window.print(); };</script>
<?php endif; ?>
</body>
</html>