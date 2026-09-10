<?php $pageTitle = 'Import ' . $config['label']; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <a href="/dashboard/bulk" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    <h1 class="text-2xl font-bold text-gray-800">Import <?= e($config['label']) ?></h1>
    <p class="text-gray-500">Upload a CSV file with columns: <?= e(implode(', ', $config['columns'])) ?></p>
</div>

<div class="bg-white rounded-xl shadow-sm p-8">
    <form action="/dashboard/bulk/import/<?= e($resource) ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">CSV File</label>
            <input type="file" name="csv" accept=".csv,text/csv" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
        </div>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Upload &amp; Import</button>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
