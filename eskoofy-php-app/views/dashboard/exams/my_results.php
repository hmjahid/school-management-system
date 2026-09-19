<?php $pageTitle = 'My Results'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">My Results</h1>
    <a href="/dashboard" class="text-gray-600 hover:text-gray-800">&larr; Back to Dashboard</a>
</div>

<?php if (empty($exams)): ?>
<div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
    No published results found.
</div>
<?php else: ?>
<div class="grid md:grid-cols-3 gap-4">
    <?php foreach ($exams as $exam): ?>
    <?php
        $total = (int) ($exam['total_students'] ?? 0);
        $marked = (int) ($exam['result_count'] ?? 0);
        $published = (int) ($exam['published_count'] ?? 0);
        $state = $published > 0 && $marked >= $total ? 'published' : (($marked >= $total && $total > 0) ? 'ready' : 'pending');
        $cardClass = $state === 'published' ? 'border-emerald-300 bg-emerald-50' : ($state === 'ready' ? 'border-blue-300 bg-blue-50' : 'border-amber-300 bg-amber-50');
    ?>
    <div class="bg-white rounded-xl shadow-sm border-2 <?= $cardClass ?> p-6 flex flex-col">
        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
            <?= e($exam['subject_name'] ?? '') ?> | <?= e($exam['section_name'] ?? $exam['batch_name'] ?? '') ?>
        </div>
        <h3 class="text-lg font-bold mt-1"><?= e($exam['name'] ?? '') ?></h3>
        <p class="text-sm text-gray-500 mt-1">Total Marks: <?= e($exam['total_marks'] ?? '') ?></p>
        <div class="mt-4 flex-1">
            <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>Marked: <?= $marked ?>/<?= $total ?: '—' ?></span>
                <span><?= $state === 'published' ? 'Published' : ($state === 'ready' ? 'Ready to publish' : 'Needs marks') ?></span>
            </div>
            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full <?= $state === 'published' ? 'bg-emerald-500' : ($state === 'ready' ? 'bg-blue-500' : 'bg-amber-500') ?>" style="width: <?= $total > 0 ? min(100, round(100 * $marked / $total)) : 0 ?>%"></div>
            </div>
        </div>
        <a href="/dashboard/exams/<?= e($exam['id']) ?>/results" class="mt-4 block text-center bg-slate-900 hover:bg-blue-600 text-white py-2 rounded-lg text-sm font-semibold transition">
            <?= $state === 'published' ? 'View' : ($state === 'ready' ? 'Review & publish' : 'Enter marks') ?>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>