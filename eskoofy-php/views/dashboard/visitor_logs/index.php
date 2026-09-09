<?php $pageTitle = 'Visitor Logs'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Visitor Logs</h1>
    <button onclick="document.getElementById('create-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Log Visitor</button>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/visitor-logs" method="GET" class="flex flex-col sm:flex-row gap-4">
        <input type="date" name="date" value="<?= e($date ?? '') ?>" class="border border-gray-300 rounded-lg px-4 py-2">
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Search visitors..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2">
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Visitor</th>
                    <th class="py-3 px-4 font-semibold border-b">Phone</th>
                    <th class="py-3 px-4 font-semibold border-b">Purpose</th>
                    <th class="py-3 px-4 font-semibold border-b">Person to Meet</th>
                    <th class="py-3 px-4 font-semibold border-b">Check In</th>
                    <th class="py-3 px-4 font-semibold border-b">Check Out</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($row['visitor_name'] ?? '') ?></td>
                        <td class="py-3 px-4 text-sm"><?= e($row['phone'] ?? '-') ?></td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($row['purpose'] ?? '') ?></td>
                        <td class="py-3 px-4 text-sm"><?= e($row['person_to_meet'] ?? '-') ?></td>
                        <td class="py-3 px-4 text-sm"><?= e($row['check_in_time'] ?? '') ?></td>
                        <td class="py-3 px-4 text-sm"><?= e($row['check_out_time'] ?? 'Still here') ?></td>
                        <td class="py-3 px-4">
                            <form action="/dashboard/visitor-logs/<?= e($row['id']) ?>" method="POST" onsubmit="return confirm('Delete?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="py-8 text-center text-gray-500">No visitors logged for this date.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="create-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-lg">
        <h2 class="text-xl font-bold mb-4">Log Visitor</h2>
        <form action="/dashboard/visitor-logs" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Visitor Name *</label>
                    <input type="text" name="visitor_name" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ID Number</label>
                    <input type="text" name="id_number" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Number</label>
                    <input type="text" name="vehicle_number" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Purpose *</label>
                <input type="text" name="purpose" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Person to Meet</label>
                <input type="text" name="person_to_meet" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Visit Date *</label>
                    <input type="date" name="visit_date" value="<?= e(date('Y-m-d')) ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Check In Time</label>
                    <input type="time" name="check_in_time" value="<?= e(date('H:i')) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2"></textarea>
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
