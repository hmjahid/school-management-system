<?php $pageTitle = 'Library Settings'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <a href="/dashboard/settings" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    <h1 class="text-2xl font-bold text-gray-800">Library Settings</h1>
</div>

<div class="bg-white rounded-xl shadow-sm p-8">
    <form action="/dashboard/settings/library" method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max Books Per User</label>
                <input type="number" name="max_books_per_user" value="<?= e($settings['max_books_per_user'] ?? 3) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Issue Duration (days)</label>
                <input type="number" name="issue_duration_days" value="<?= e($settings['issue_duration_days'] ?? 14) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fine Per Day</label>
                <input type="number" name="fine_per_day" step="0.01" value="<?= e($settings['fine_per_day'] ?? 5) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Save</button>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
