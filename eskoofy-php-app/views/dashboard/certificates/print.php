<?php $pageTitle = 'Certificate'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - <?= e($cert['student_name'] ?? '') ?></title>
    <style>
        body { font-family: Georgia, serif; background: #f3f4f6; margin: 0; padding: 40px; display: flex; justify-content: center; }
        .cert { width: 800px; background: #fff; border: 3px double #1d4ed8; border-radius: 8px; padding: 48px; position: relative; box-shadow: 0 10px 25px rgba(0,0,0,.1); }
        .school { text-align: center; font-size: 24px; font-weight: 700; color: #1e3a8a; }
        .address { text-align: center; font-size: 12px; color: #6b7280; }
        .title { text-align: center; font-size: 28px; font-weight: 700; color: #1e3a8a; margin: 24px 0 8px; letter-spacing: 2px; }
        .no { text-align: center; font-size: 12px; color: #9ca3af; margin-bottom: 24px; }
        .body-text { font-size: 16px; line-height: 1.9; color: #374151; }
        .details { margin-top: 32px; font-size: 14px; color: #374151; }
        .details div { padding: 4px 0; }
        .details b { color: #111827; }
        .sign { margin-top: 56px; display: flex; justify-content: space-between; font-size: 14px; color: #111827; }
        .sign .line { border-top: 1px solid #9ca3af; padding-top: 6px; width: 200px; text-align: center; }
    </style>
</head>
<body>
<div class="cert">
    <div class="school"><?= e($settings['school_name'] ?? 'School') ?></div>
    <div class="address"><?= e($settings['address'] ?? '') ?></div>
    <div class="title">CERTIFICATE OF <?= strtoupper(e($cert['certificate_type'] ?? 'ACHIEVEMENT')) ?></div>
    <div class="no">Certificate No: <?= e($cert['certificate_number'] ?? '') ?></div>
    <div class="body-text">
        <?php if (!empty($cert['body'])): ?>
            <?php $bodyLines = is_array($cert['body']) ? $cert['body'] : [$cert['body']]; ?>
            <?php foreach ($bodyLines as $line): ?>
            <p><?= e((string) $line) ?></p>
            <?php endforeach; ?>
        <?php else: ?>
        <p>This is to certify that <b><?= e($cert['student_name'] ?? '') ?></b> has completed the requirements for the <?= e($cert['certificate_type'] ?? '') ?> certificate.</p>
        <?php endif; ?>
    </div>
    <div class="details">
        <div>Student: <b><?= e($cert['student_name'] ?? '') ?></b></div>
        <div>Class: <b><?= e($cert['class_name'] ?? '') ?></b><?= !empty($cert['section_name']) ? ' | Section: <b>' . e($cert['section_name']) . '</b>' : '' ?></div>
    </div>
    <div class="sign">
        <div></div>
        <div class="line">Authorized Signature</div>
    </div>
</div>
<script>window.onload = function(){ window.print(); };</script>
</body>
</html>