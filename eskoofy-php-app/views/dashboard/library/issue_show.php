<?php $pageTitle = 'Book Issue'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Book Issue</h1>
    <a href="/dashboard/book-issues" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-xl">
    <dl class="grid grid-cols-2 gap-4">
        <div><dt class="text-xs text-gray-500">Book</dt><dd class="font-medium"><?= e($issue['book_title'] ?? '') ?></dd></div>
        <div><dt class="text-xs text-gray-500">Borrower</dt><dd class="font-medium"><?= e($issue['member_name'] ?? '—') ?></dd></div>
        <div><dt class="text-xs text-gray-500">Issue date</dt><dd><?= e(date('d M Y', strtotime($issue['issue_date']))) ?></dd></div>
        <div><dt class="text-xs text-gray-500">Due date</dt><dd><?= e(date('d M Y', strtotime($issue['due_date']))) ?></dd></div>
        <div><dt class="text-xs text-gray-500">Return date</dt><dd><?= !empty($issue['return_date']) ? e(date('d M Y', strtotime($issue['return_date']))) : '—' ?></dd></div>
        <div><dt class="text-xs text-gray-500">Status</dt><dd><?= e(ucfirst($issue['status'] ?? 'issued')) ?></dd></div>
        <div><dt class="text-xs text-gray-500">Late fee</dt><dd>
            <?= $issue['late_fee'] !== null ? '$' . number_format((float) $issue['late_fee'], 2) : '—' ?>
            <?php if (!empty($issue['fine_paid'])): ?><span class="text-xs text-green-600 font-semibold">(paid)</span><?php endif; ?>
        </dd></div>
        <?php if (!empty($issue['notes'])): ?>
        <div><dt class="text-xs text-gray-500">Notes</dt><dd><?= e($issue['notes']) ?></dd></div>
        <?php endif; ?>
    </dl>
</div>

<?php if (($issue['status'] ?? '') === 'issued'): ?>
<div class="flex gap-3 mt-4 max-w-xl">
    <form action="/dashboard/book-issues/<?= e($issue['id']) ?>/return" method="POST">
        <?= csrf_field() ?>
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">Return book</button>
    </form>
    <form action="/dashboard/book-issues/<?= e($issue['id']) ?>/lost" method="POST" onsubmit="return confirm('Mark this book as lost?')">
        <?= csrf_field() ?>
        <button type="submit" class="bg-amber-600 text-white px-4 py-2 rounded-lg hover:bg-amber-700 transition">Mark lost</button>
    </form>
</div>
<?php endif; ?>

<?php if (($issue['status'] ?? '') === 'returned' && !empty($issue['late_fee']) && empty($issue['fine_paid'])): ?>
<div class="mt-4 max-w-xl">
    <form action="/dashboard/book-issues/<?= e($issue['id']) ?>/fine" method="POST">
        <?= csrf_field() ?>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Collect fine ($<?= number_format((float) $issue['late_fee'], 2) ?>)</button>
    </form>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>