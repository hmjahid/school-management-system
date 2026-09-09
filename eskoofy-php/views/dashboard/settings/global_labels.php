<?php $pageTitle = 'Global Labels'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <a href="/dashboard/settings" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    <h1 class="text-2xl font-bold text-gray-800">Global UI Labels</h1>
    <p class="text-gray-500">Override default terminology throughout the application.</p>
</div>

<div class="bg-white rounded-xl shadow-sm p-8">
    <form action="/dashboard/settings/global-labels" method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">"Student" Label</label>
                <input type="text" name="label_student" value="<?= e($settings['label_student'] ?? '') ?>" placeholder="Student (default)" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">"Teacher" Label</label>
                <input type="text" name="label_teacher" value="<?= e($settings['label_teacher'] ?? '') ?>" placeholder="Teacher (default)" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">"Class" Label</label>
                <input type="text" name="label_class" value="<?= e($settings['label_class'] ?? '') ?>" placeholder="Class (default)" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">"Guardian" Label</label>
                <input type="text" name="label_guardian" value="<?= e($settings['label_guardian'] ?? '') ?>" placeholder="Guardian (default)" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Save</button>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/dashboard.php'; ?>
