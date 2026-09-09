<?php $pageTitle = 'Edit Class'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Edit Class: <?= e($class->name) ?></h1>
    <a href="/dashboard/classes/<?= e($class->id) ?>" class="text-gray-600 hover:text-gray-800">← Back to Class</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6">
    <form action="/dashboard/classes/<?= e($class->id) ?>" method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class Name *</label>
                <input type="text" name="name" value="<?= e($class->name) ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Numeric Value</label>
                <input type="number" name="numeric_value" value="<?= e($class->numeric_value ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Fee</label>
                <input type="number" name="monthly_fee" value="<?= e($class->monthly_fee ?? 0) ?>" step="0.01" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Shift</label>
                <select name="shift" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="morning" <?= ($class->shift ?? '') == 'morning' ? 'selected' : '' ?>>Morning</option>
                    <option value="day" <?= ($class->shift ?? '') == 'day' ? 'selected' : '' ?>>Day</option>
                    <option value="evening" <?= ($class->shift ?? '') == 'evening' ? 'selected' : '' ?>>Evening</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pass Marks</label>
                <input type="number" name="pass_marks" value="<?= e($class->pass_marks ?? 40) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Total Marks</label>
                <input type="number" name="total_marks" value="<?= e($class->total_marks ?? 100) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                <input type="number" name="order" value="<?= e($class->order ?? 0) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"><?= e($class->description ?? '') ?></textarea>
        </div>
        <div class="flex justify-end space-x-4">
            <a href="/dashboard/classes/<?= e($class->id) ?>" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Update Class</button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>