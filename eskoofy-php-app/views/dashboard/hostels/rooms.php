<?php $pageTitle = 'Hostel Rooms'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Hostel Rooms</h1>
    <button onclick="document.getElementById('create-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Add Room</button>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/hostel-rooms" method="GET" class="flex flex-col sm:flex-row gap-4">
        <select name="hostel_id" class="border border-gray-300 rounded-lg px-4 py-2">
            <option value="">All Hostels</option>
            <?php if (!empty($hostels)): ?>
                <?php foreach ($hostels as $hostel): ?>
                <option value="<?= e($hostel['id']) ?>" <?= ($hostelId ?? 0) == $hostel['id'] ? 'selected' : '' ?>><?= e($hostel['name'] ?? '') ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Hostel</th>
                    <th class="py-3 px-4 font-semibold border-b">Room #</th>
                    <th class="py-3 px-4 font-semibold border-b">Type</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Capacity</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Occupied</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Available</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                    <?php
                    $capacity = (int) ($row['capacity'] ?? 0);
                    $occupied = (int) ($row['occupied'] ?? 0);
                    $available = $capacity - $occupied;
                    ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($row['hostel_name'] ?? '') ?></td>
                        <td class="py-3 px-4"><?= e($row['room_number'] ?? '') ?></td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($row['room_type'] ?? '-') ?></td>
                        <td class="py-3 px-4 text-center"><?= e($capacity) ?></td>
                        <td class="py-3 px-4 text-center"><?= e($occupied) ?></td>
                        <td class="py-3 px-4 text-center">
                            <span class="<?= $available > 0 ? 'text-green-600 font-medium' : 'text-red-600 font-medium' ?>"><?= e($available) ?></span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <?php
                            $statusColors = [
                                'available' => 'bg-green-100 text-green-700',
                                'occupied' => 'bg-red-100 text-red-700',
                                'maintenance' => 'bg-yellow-100 text-yellow-700',
                            ];
                            $sColor = $statusColors[$row['status'] ?? 'available'] ?? 'bg-gray-100 text-gray-700';
                            ?>
                            <span class="px-2 py-1 text-xs rounded-full <?= $sColor ?>"><?= e(ucfirst($row['status'] ?? 'available')) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="py-8 text-center text-gray-500">No rooms found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="create-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Add Room</h2>
        <form action="/dashboard/hostel-rooms" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hostel *</label>
                <select name="hostel_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Select Hostel</option>
                    <?php if (!empty($hostels)): ?>
                        <?php foreach ($hostels as $hostel): ?>
                        <option value="<?= e($hostel['id']) ?>"><?= e($hostel['name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Room Number *</label>
                <input type="text" name="room_number" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Room Type</label>
                <input type="text" name="room_type" placeholder="e.g. Single, Double, Dormitory" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Capacity (beds) *</label>
                <input type="number" name="capacity" min="1" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="available">Available</option>
                    <option value="occupied">Occupied</option>
                    <option value="maintenance">Maintenance</option>
                </select>
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
