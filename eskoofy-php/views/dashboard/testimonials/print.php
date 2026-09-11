<?php $pageTitle = 'Testimonial'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonial - <?= e($testimonial['student_name'] ?? '') ?></title>
    <style>
        body { font-family: Georgia, serif; background: #f3f4f6; margin: 0; padding: 40px; display: flex; justify-content: center; }
        .cert { width: 700px; background: #fff; border: 3px double #15803d; border-radius: 8px; padding: 48px; box-shadow: 0 10px 25px rgba(0,0,0,.1); }
        .school { text-align: center; font-size: 22px; font-weight: 700; color: #14532d; }
        .title { text-align: center; font-size: 26px; font-weight: 700; color: #14532d; margin: 24px 0 8px; letter-spacing: 2px; }
        .no { text-align: center; font-size: 12px; color: #9ca3af; margin-bottom: 24px; }
        .body-text { font-size: 16px; line-height: 1.9; color: #374151; }
        .stars { text-align: center; font-size: 20px; color: #f59e0b; margin-top: 12px; }
        .details { margin-top: 32px; font-size: 14px; color: #374151; }
        .details div { padding: 4px 0; }
        .details b { color: #111827; }
        .author { margin-top: 40px; font-size: 14px; color: #111827; }
        .sign { margin-top: 48px; display: flex; justify-content: flex-end; }
        .sign .line { border-top: 1px solid #9ca3af; padding-top: 6px; width: 220px; text-align: center; }
    </style>
</head>
<body>
<div class="cert">
    <div class="school"><?= e($settings['school_name'] ?? 'School') ?></div>
    <div class="title">TESTIMONIAL</div>
    <div class="no">No: <?= e($testimonial['testimonial_number'] ?? '') ?></div>
    <div class="body-text">
        <?php if (!empty($testimonial['body'])): ?>
            <?php $bodyLines = is_array($testimonial['body']) ? $testimonial['body'] : [$testimonial['body']]; ?>
            <?php foreach ($bodyLines as $line): ?>
            <p><?= e((string) $line) ?></p>
            <?php endforeach; ?>
        <?php else: ?>
        <p>This testimonial is awarded to <b><?= e($testimonial['student_name'] ?? '') ?></b> in recognition of outstanding <?= e(str_replace('_', ' ', $testimonial['testimonial_type'] ?? 'achievement')) ?>.</p>
        <?php endif; ?>
        <div class="stars"><?= str_repeat('★', (int) ($testimonial['rating'] ?? 0)) ?></div>
    </div>
    <div class="details">
        <div>Student: <b><?= e($testimonial['student_name'] ?? '') ?></b></div>
        <div>Class: <b><?= e($testimonial['class_name'] ?? '') ?></b><?= !empty($testimonial['section_name']) ? ' | Section: <b>' . e($testimonial['section_name']) . '</b>' : '' ?></div>
        <div>Issue Date: <b><?= e(date('d M Y', strtotime($testimonial['issue_date'] ?? 'now'))) ?></b></div>
    </div>
    <?php if (!empty($testimonial['author_name'])): ?>
    <div class="author">
        <b><?= e($testimonial['author_name']) ?></b>
        <?= !empty($testimonial['author_designation']) ? '— ' . e($testimonial['author_designation']) : '' ?>
    </div>
    <?php endif; ?>
    <div class="sign">
        <div class="line">Authorized Signature</div>
    </div>
</div>
<script>window.onload = function(){ window.print(); };</script>
</body>
</html>