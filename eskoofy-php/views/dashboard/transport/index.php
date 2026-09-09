<?php $pageTitle = 'Transport'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Transport Management</h1>
    <a href="/dashboard/vehicles" class="text-gray-600 hover:text-gray-800">Manage Vehicles →</a>
</div>

<div class="grid md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-blue-600"><?= e($stats['total_vehicles'] ?? 0) ?></div>
        <div class="text-gray-500">Total Vehicles</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-green-600"><?= e($stats['total_routes'] ?? 0) ?></div>
        <div class="text-gray-500">Routes</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-purple-600"><?= e($stats['total_students'] ?? 0) ?></div>
        <div class="text-gray-500">Students Using Transport</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold">Routes</h2>
        <button onclick="document.getElementById('route-modal').classList.remove('hidden')" class="text-blue-600 hover:underline text-sm">+ Add Route</button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b">
                    <th class="py-3 px-4 font-semibold">Route Name</th>
                    <th class="py-3 px-4 font-semibold">Vehicle</th>
                    <th class="py-3 px-4 font-semibold">Pickup Points</th>
                    <th class="py-3 px-4 font-semibold">Monthly Fee</th>
                    <th class="py-3 px-4 font-semibold">Students</th>
                    <th class="py-3 px-4 font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($routes)): ?>
                    <?php foreach ($routes as $route): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($route->name) ?></td>
                        <td class="py-3 px-4"><?= e($route->vehicle->plate_number ?? '-') ?></td>
                        <td class="py-3 px-4"><?= e($route->pickup_points_count ?? 0) ?></td>
                        <td class="py-3 px-4"><?= e(config('currency.symbol', '$')) ?><?= number_format($route->monthly_fee ?? 0, 2) ?></td>
                        <td class="py-3 px-4"><?= e($route->students_count ?? 0) ?></td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="/dashboard/transport/<?= e($route->id) ?>/edit" class="text-green-600 hover:underline text-sm">Edit</a>
                                <form action="/dashboard/transport/<?= e($route->id) ?>" method="POST" onsubmit="return confirm('Delete?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="py-8 text-center text-gray-500">No routes configured yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="route-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Add Route</h2>
        <form action="/dashboard/transport" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Route Name *</label>
                <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle</label>
                <select name="vehicle_id" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Select Vehicle</option>
                    <?php if (!empty($vehicles)): ?>
                        <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?= e($vehicle->id) ?>"><?= e($vehicle->plate_number) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Fee</label>
                <input type="number" name="monthly_fee" step="0.01" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('route-modal').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>