<?php $pageTitle = 'Leave Types'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Leave Types</h1>
    <button onclick="document.getElementById('create-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Add Leave Type</button>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Name</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Days Allowed</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Paid</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($leaveTypes)): ?>
                    <?php foreach ($leaveTypes as $lt): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($lt['name'] ?? '') ?></td>
                        <td class="py-3 px-4 text-center"><?= e($lt['days_allowed'] ?? 0) ?></td>
                        <td class="py-3 px-4 text-center">
                            <?php if ($lt['is_paid'] ?? 0): ?>
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Yes</span>
                            <?php else: ?>
                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-700">No</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <button onclick="editLeaveType(<?= e(json_encode($lt)) ?>)" class="text-green-600 hover:underline text-sm">Edit</button>
                                <form action="/dashboard/leave-types/<?= e($lt['id']) ?>" method="POST" onsubmit="return confirm('Delete?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="py-8 text-center text-gray-500">No leave types configured.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="create-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Add Leave Type</h2>
        <form action="/dashboard/leave-types" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="POST">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Days Allowed *</label>
                <input type="number" name="days_allowed" min="0" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="is_paid" value="1" class="rounded border-gray-300 text-blue-600 mr-2">
                    <span class="text-sm text-gray-700">Paid Leave</span>
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
function editLeaveType(lt) {
    document.getElementById('create-modal').querySelector('h2').textContent = 'Edit Leave Type';
    const form = document.getElementById('create-modal').querySelector('form');
    form.action = '/dashboard/leave-types/' + lt.id;
    form.querySelector('input[name=_method]').value = 'PUT';
    form.querySelector('input[name=name]').value = lt.name || '';
    form.querySelector('input[name=days_allowed]').value = lt.days_allowed || 0;
    form.querySelector('input[name=is_paid]').checked = !!parseInt(lt.is_paid || 0);
    document.getElementById('create-modal').classList.remove('hidden');
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
