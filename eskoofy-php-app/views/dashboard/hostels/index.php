<?php $pageTitle = 'Hostel'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Hostel Management</h1>
    <button onclick="document.getElementById('create-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Add Hostel</button>
</div>

<div class="grid md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-blue-600"><?= e($stats['total_hostels'] ?? 0) ?></div>
        <div class="text-gray-500">Hostels</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-green-600"><?= e($stats['total_rooms'] ?? 0) ?></div>
        <div class="text-gray-500">Rooms</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-purple-600"><?= e($stats['total_students'] ?? 0) ?></div>
        <div class="text-gray-500">Students</div>
    </div>
</div>

<div class="space-y-4">
    <?php if (!empty($hostels)): ?>
        <?php foreach ($hostels as $hostel): ?>
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-lg font-bold"><?= e($hostel['name']) ?></h3>
                    <p class="text-sm text-gray-500"><?= e($hostel['type'] ?? 'Boys') ?> Hostel | <?= e($hostel['rooms_count'] ?? 0) ?> rooms</p>
                </div>
                <div class="flex space-x-2">
                    <a href="/dashboard/hostels/<?= e($hostel['id']) ?>/edit" class="text-green-600 hover:underline text-sm">Edit</a>
                    <form action="/dashboard/hostels/<?= e($hostel['id']) ?>" method="POST" onsubmit="return confirm('Delete?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                    </form>
                </div>
            </div>
            <?php if (!empty($hostel['rooms'])): ?>
            <div class="grid grid-cols-4 md:grid-cols-8 gap-2">
                <?php foreach ($hostel['rooms'] as $room): ?>
                <div class="text-center p-2 rounded-lg <?= $room['is_full'] ? 'bg-red-100' : 'bg-green-100' ?>">
                    <div class="text-sm font-bold"><?= e($room['number']) ?></div>
                    <div class="text-xs text-gray-500"><?= e($room['occupants_count'] ?? 0) ?>/<?= e($room['capacity']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">No hostels configured yet.</div>
    <?php endif; ?>
</div>

<div id="create-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Add Hostel</h2>
        <form action="/dashboard/hostels" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hostel Name *</label>
                <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="type" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="boys">Boys</option>
                    <option value="girls">Girls</option>
                    <option value="both">Both</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea name="address" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Fee</label>
                <input type="number" name="monthly_fee" step="0.01" class="w-full border border-gray-300 rounded-lg px-4 py-2">
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