<?php $pageTitle = 'Transport Assignments'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Transport Assignments</h1>
    <button onclick="document.getElementById('create-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Assign Student</button>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Student</th>
                    <th class="py-3 px-4 font-semibold border-b">Admission #</th>
                    <th class="py-3 px-4 font-semibold border-b">Route</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Fare</th>
                    <th class="py-3 px-4 font-semibold border-b">Effective From</th>
                    <th class="py-3 px-4 font-semibold border-b">Effective To</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($row['student_name'] ?? '') ?></td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($row['admission_number'] ?? '') ?></td>
                        <td class="py-3 px-4"><?= e($row['route_name'] ?? '') ?></td>
                        <td class="py-3 px-4 text-center font-medium"><?= e(number_format((float) ($row['fare'] ?? 0), 2)) ?></td>
                        <td class="py-3 px-4 text-sm"><?= e($row['effective_from'] ?? '') ?></td>
                        <td class="py-3 px-4 text-sm"><?= e($row['effective_to'] ?? 'Ongoing') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="py-8 text-center text-gray-500">No transport assignments found.</td></tr>
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
        <h2 class="text-xl font-bold mb-4">Assign Student to Route</h2>
        <form action="/dashboard/transport-assignments" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Student *</label>
                <select name="student_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Select Student</option>
                    <?php if (!empty($students)): ?>
                        <?php foreach ($students as $student): ?>
                        <option value="<?= e($student['id']) ?>"><?= e($student['name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Route *</label>
                <select name="route_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Select Route</option>
                    <?php if (!empty($routes)): ?>
                        <?php foreach ($routes as $route): ?>
                        <option value="<?= e($route['id']) ?>"><?= e($route['name'] ?? '') ?> (<?= e(number_format((float) ($route['fare'] ?? 0), 2)) ?>)</option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Effective From *</label>
                <input type="date" name="effective_from" value="<?= e(date('Y-m-d')) ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Effective To</label>
                <input type="date" name="effective_to" class="w-full border border-gray-300 rounded-lg px-4 py-2">
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
