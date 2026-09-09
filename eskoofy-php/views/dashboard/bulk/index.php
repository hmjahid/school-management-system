<?php $pageTitle = 'Bulk Import/Export'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Bulk Import / Export</h1>
    <p class="text-gray-500">Import or export data in CSV format</p>
</div>

<div class="bg-white rounded-xl shadow-sm p-6">
    <h2 class="text-lg font-bold mb-4">Select a Resource</h2>
    <div class="grid md:grid-cols-3 gap-4">
        <?php foreach ($resources as $key => $cfg): ?>
        <div class="border rounded-lg p-4 hover:shadow-md transition">
            <h3 class="font-bold mb-2"><?= e($cfg['label']) ?></h3>
            <p class="text-sm text-gray-500 mb-3">Columns: <?= e(implode(', ', $cfg['columns'])) ?></p>
            <div class="flex space-x-2">
                <a href="/dashboard/bulk/export/<?= e($key) ?>" class="text-sm bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">Export CSV</a>
                <a href="/dashboard/bulk/import/<?= e($key) ?>" class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Import CSV</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/dashboard.php'; ?>
