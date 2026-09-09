<?php $pageTitle = 'Setup'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Setup Checklist</h1>
    <p class="text-gray-500">Complete these steps to get your school up and running.</p>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="flex justify-between items-center mb-2">
        <h2 class="font-bold">Progress</h2>
        <span class="text-2xl font-bold text-blue-600"><?= e($percent) ?>%</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-3">
        <div class="bg-blue-600 h-3 rounded-full" style="width: <?= e($percent) ?>%"></div>
    </div>
    <p class="text-sm text-gray-500 mt-2"><?= e($completed) ?> of <?= e($total) ?> tasks complete</p>
</div>

<div class="space-y-3">
    <?php foreach ($checks as $c): ?>
    <div class="bg-white rounded-xl shadow-sm p-4 flex items-center justify-between">
        <div class="flex items-center">
            <div class="w-10 h-10 rounded-full <?= $c['done'] ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400' ?> flex items-center justify-center mr-3 text-lg">
                <?= $c['done'] ? '✓' : '○' ?>
            </div>
            <div>
                <h3 class="font-bold"><?= e($c['title']) ?></h3>
                <p class="text-sm text-gray-500"><?= e($c['description']) ?></p>
            </div>
        </div>
        <a href="<?= e($c['link']) ?>" class="<?= $c['done'] ? 'bg-gray-100 text-gray-700' : 'bg-blue-600 text-white' ?> px-4 py-2 rounded-lg hover:opacity-90 text-sm">
            <?= $c['done'] ? 'Review' : 'Setup' ?>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/dashboard.php'; ?>
