<?php $pageTitle = 'Transport Routes'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Transport Routes</h1>
    <button onclick="document.getElementById('create-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Add Route</button>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Route Name</th>
                    <th class="py-3 px-4 font-semibold border-b">Code</th>
                    <th class="py-3 px-4 font-semibold border-b">Vehicle</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Fare</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Status</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($row['name'] ?? '') ?></td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($row['code'] ?? '') ?></td>
                        <td class="py-3 px-4"><?= e($row['vehicle_number'] ?? '-') ?></td>
                        <td class="py-3 px-4 text-center font-medium"><?= e(number_format((float) ($row['fare'] ?? 0), 2)) ?></td>
                        <td class="py-3 px-4 text-center">
                            <?php if (($row['is_active'] ?? 1) == 1): ?>
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Active</span>
                            <?php else: ?>
                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-700">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <button onclick="editRoute(<?= e(json_encode($row)) ?>)" class="text-green-600 hover:underline text-sm">Edit</button>
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

    <?php if ($lastPage > 1): ?>
    <div class="p-6 flex justify-center space-x-2">
        <?php for ($i = 1; $i <= $lastPage; $i++): ?>
        <a href="?page=<?= $i ?>" class="px-3 py-1 rounded-lg <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<div id="create-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Add Route</h2>
        <form action="/dashboard/transport-routes" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Route Name *</label>
                <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Code *</label>
                <input type="text" name="code" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fare *</label>
                <input type="number" name="fare" step="0.01" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle</label>
                <select name="vehicle_id" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Select Vehicle</option>
                    <?php if (!empty($vehicles)): ?>
                        <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?= e($vehicle['id']) ?>"><?= e($vehicle['number'] ?? '') ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-blue-600 mr-2">
                    <span class="text-sm text-gray-700">Active</span>
                </label>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function editRoute(route) {
    alert('Edit route: ' + route.name);
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
