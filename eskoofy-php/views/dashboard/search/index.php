<?php $pageTitle = 'Search'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Global Search</h1>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/search" method="GET">
        <div class="flex gap-2">
            <input type="text" name="q" value="<?= e($q ?? '') ?>" placeholder="Search students, teachers, classes..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Search</button>
        </div>
    </form>
</div>

<?php if ($q !== ''): ?>
    <?php $total = count($results['students']) + count($results['teachers']) + count($results['classes']) + count($results['exams']) + count($results['fees']); ?>
    <p class="text-sm text-gray-500 mb-4"><?= e($total) ?> result(s) for "<strong><?= e($q) ?></strong>"</p>

    <?php if (!empty($results['students'])): ?>
    <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
        <h2 class="text-lg font-bold mb-3">👨‍🎓 Students</h2>
        <ul class="space-y-2">
            <?php foreach ($results['students'] as $r): ?>
            <li><a href="/dashboard/students/<?= e($r['id']) ?>" class="text-blue-600 hover:underline"><?= e($r['name']) ?> (<?= e($r['admission_number']) ?>) — <?= e($r['class_name'] ?? '') ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['teachers'])): ?>
    <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
        <h2 class="text-lg font-bold mb-3">👨‍🏫 Teachers</h2>
        <ul class="space-y-2">
            <?php foreach ($results['teachers'] as $r): ?>
            <li><a href="/dashboard/teachers/<?= e($r['id']) ?>" class="text-blue-600 hover:underline"><?= e($r['name']) ?> (<?= e($r['employee_id'] ?? '') ?>)</a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['classes'])): ?>
    <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
        <h2 class="text-lg font-bold mb-3">🏫 Classes</h2>
        <ul class="space-y-2">
            <?php foreach ($results['classes'] as $r): ?>
            <li><a href="/dashboard/classes/<?= e($r['id']) ?>" class="text-blue-600 hover:underline"><?= e($r['name']) ?> (<?= e($r['code'] ?? '') ?>)</a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['exams'])): ?>
    <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
        <h2 class="text-lg font-bold mb-3">📝 Exams</h2>
        <ul class="space-y-2">
            <?php foreach ($results['exams'] as $r): ?>
            <li><a href="/dashboard/exams/<?= e($r['id']) ?>" class="text-blue-600 hover:underline"><?= e($r['name']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['fees'])): ?>
    <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
        <h2 class="text-lg font-bold mb-3">💰 Fees</h2>
        <ul class="space-y-2">
            <?php foreach ($results['fees'] as $r): ?>
            <li><a href="/dashboard/fees/<?= e($r['id']) ?>/edit" class="text-blue-600 hover:underline"><?= e($r['name']) ?> — <?= e(format_currency((float)$r['amount'])) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if ($total === 0): ?>
    <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">No matches found.</div>
    <?php endif; ?>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
