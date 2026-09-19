<?php $pageTitle = 'About Settings'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <a href="/dashboard/settings" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    <h1 class="text-2xl font-bold text-gray-800">About Content</h1>
</div>

<div class="bg-white rounded-xl shadow-sm p-8">
    <form action="/dashboard/settings/about" method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mission</label>
            <textarea name="mission" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2"><?= e($content['mission'] ?? '') ?></textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Vision</label>
            <textarea name="vision" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2"><?= e($content['vision'] ?? '') ?></textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">History</label>
            <textarea name="history" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2"><?= e($content['history'] ?? '') ?></textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">About</label>
            <textarea name="about" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2"><?= e($content['about'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Save</button>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
