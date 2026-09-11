<?php $pageTitle = 'Report Builder'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Report Builder</h1>
    <a href="/dashboard/reports" class="text-gray-600 hover:text-gray-800">&larr; Back to Reports</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6">
    <form action="/dashboard/reports/builder/export" method="POST">
        <?= csrf_field() ?>
        <div class="grid md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Report type</label>
                <select name="entity" id="report-entity" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($config as $key => $cfg): ?>
                    <option value="<?= e($key) ?>"><?= e($cfg['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Class (students only)</label>
                <select name="class_id" id="class-filter" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">All classes</option>
                    <?php foreach ($classes as $class): ?>
                    <option value="<?= e($class['id']) ?>"><?= e($class['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">From date</label>
                <input type="date" name="date_from" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">To date</label>
                <input type="date" name="date_to" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                <input type="text" name="status" placeholder="e.g. active, completed" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Columns to include</label>
            <?php foreach ($config as $entity => $cfg): ?>
            <div class="report-col-group grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 mb-4" data-entity="<?= e($entity) ?>">
                <?php foreach ($cfg['columns'] as $key => $label): ?>
                <label class="flex items-center gap-2 text-sm border border-gray-200 rounded-lg px-3 py-2">
                    <input type="checkbox" name="columns[]" value="<?= e($key) ?>" class="rounded">
                    <?= e($label) ?>
                </label>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Export CSV</button>
            <a href="/dashboard/reports" class="text-gray-600 hover:text-gray-800">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var entity = document.getElementById('report-entity');
    var groups = document.querySelectorAll('.report-col-group');
    function sync() {
        var val = entity.value;
        groups.forEach(function (g) {
            g.style.display = (g.dataset.entity === val) ? '' : 'none';
        });
        document.getElementById('class-filter').style.display = (val === 'students') ? '' : 'none';
    }
    entity.addEventListener('change', sync);
    sync();
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>