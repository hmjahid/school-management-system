<?php $pageTitle = 'Theme Settings'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <a href="/dashboard/settings" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    <h1 class="text-2xl font-bold text-gray-800">Theme Settings</h1>
</div>

<div class="bg-white rounded-xl shadow-sm p-8">
    <form action="/dashboard/settings/theme" method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Theme Color</label>
                <input type="color" name="theme_color" value="<?= e($settings['theme_color'] ?? '#1d4ed8') ?>" class="w-full h-10 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Theme Mode</label>
                <select name="theme_mode" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="light" <?= ($settings['theme_mode'] ?? '') === 'light' ? 'selected' : '' ?>>Light</option>
                    <option value="dark" <?= ($settings['theme_mode'] ?? '') === 'dark' ? 'selected' : '' ?>>Dark</option>
                    <option value="auto" <?= ($settings['theme_mode'] ?? '') === 'auto' ? 'selected' : '' ?>>Auto</option>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Logo Path</label>
            <input type="text" name="logo_path" value="<?= e($settings['logo_path'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2" placeholder="uploads/school/logo.png">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Favicon Path</label>
            <input type="text" name="favicon_path" value="<?= e($settings['favicon_path'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2">
        </div>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Save</button>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/dashboard.php'; ?>
