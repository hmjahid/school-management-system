<?php $pageTitle = 'Committee'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">School Committee</h1>
    <button onclick="document.getElementById('create-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Add Member</button>
</div>

<div class="grid md:grid-cols-3 gap-6">
    <?php if (!empty($members)): ?>
        <?php foreach ($members as $member): ?>
        <div class="bg-white rounded-xl shadow-sm p-6 text-center">
            <div class="w-20 h-20 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">
                <?= strtoupper(substr($member['name'], 0, 1)) ?>
            </div>
            <h3 class="font-bold text-lg"><?= e($member['name']) ?></h3>
            <p class="text-blue-600 text-sm font-medium"><?= e($member['position'] ?? '') ?></p>
            <?php if ($member['phone'] ?? null): ?>
                <p class="text-gray-500 text-sm mt-1">📞 <?= e($member['phone']) ?></p>
            <?php endif; ?>
            <?php if ($member['email'] ?? null): ?>
                <p class="text-gray-500 text-sm">✉️ <?= e($member['email']) ?></p>
            <?php endif; ?>
            <p class="text-gray-600 text-sm mt-2"><?= e(truncate($member['bio'] ?? '', 100)) ?></p>
            <div class="flex justify-center space-x-2 mt-4">
                <a href="/dashboard/committee/<?= e($member['id']) ?>/edit" class="text-green-600 hover:underline text-sm">Edit</a>
                <form action="/dashboard/committee/<?= e($member['id']) ?>" method="POST" onsubmit="return confirm('Delete?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-3 bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">No committee members yet.</div>
    <?php endif; ?>
</div>

<div id="create-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Add Committee Member</h2>
        <form action="/dashboard/committee" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Position *</label>
                <input type="text" name="position" placeholder="e.g. Chairman" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="tel" name="phone" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                <textarea name="bio" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                <input type="number" name="order" value="0" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>