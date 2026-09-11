<?php $pageTitle = 'Admit Card'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admit Card - <?= e($card['student_name'] ?? '') ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 40px; display: flex; justify-content: center; }
        .card { width: 600px; background: #fff; border: 2px solid #1d4ed8; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,.1); }
        .header { background: #1d4ed8; color: #fff; text-align: center; padding: 16px; }
        .header .school { font-size: 18px; font-weight: 700; }
        .header .type { font-size: 12px; opacity: .85; }
        .body { padding: 24px; }
        .exam { text-align: center; margin-bottom: 16px; }
        .exam .name { font-size: 16px; font-weight: 700; color: #111827; }
        .exam .date { font-size: 12px; color: #6b7280; }
        .row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #e5e7eb; font-size: 14px; }
        .row span:first-child { color: #6b7280; }
        .row span:last-child { font-weight: 600; color: #111827; }
        .note { margin-top: 16px; font-size: 12px; color: #6b7280; text-align: center; }
        .footer { background: #f9fafb; text-align: center; padding: 8px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        <div class="school"><?= e($settings['school_name'] ?? 'School') ?></div>
        <div class="type">ADMIT CARD</div>
    </div>
    <div class="body">
        <div class="exam">
            <div class="name"><?= e($card['exam_name'] ?? '') ?></div>
            <div class="date"><?= e(date('d M Y', strtotime($card['start_date'] ?? 'now'))) ?></div>
        </div>
        <div class="row"><span>Student Name</span><span><?= e($card['student_name'] ?? '') ?></span></div>
        <div class="row"><span>Class</span><span><?= e($card['class_name'] ?? '') ?></span></div>
        <div class="row"><span>Section</span><span><?= e($card['section_name'] ?? '-') ?></span></div>
        <div class="row"><span>Roll Number</span><span><?= e($card['roll_number'] ?? '-') ?></span></div>
        <div class="row"><span>Admission No</span><span><?= e($card['admission_number'] ?? '') ?></span></div>
        <div class="row"><span>Card Number</span><span><?= e($card['admit_card_number'] ?? '') ?></span></div>
        <div class="row"><span>Issue Date</span><span><?= e(date('d M Y', strtotime($card['issue_date'] ?? 'now'))) ?></span></div>
        <div class="note">Please bring this card to the examination hall. Valid photo ID required.</div>
    </div>
    <div class="footer"><?= e($settings['phone'] ?? '') ?></div>
</div>
<?php if (empty($preview)): ?>
<script>window.onload = function(){ window.print(); };</script>
<?php endif; ?>
</body>
</html>