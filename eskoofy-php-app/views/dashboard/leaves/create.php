<?php $pageTitle = 'New Leave Request'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">New Leave Request</h1>
    <a href="/dashboard/leaves" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <form action="/dashboard/leaves" method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <?php if (!empty($teachers)): ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Teacher *</label>
            <select name="teacher_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                <option value="">Select teacher</option>
                <?php foreach ($teachers as $t): ?>
                <option value="<?= e($t['id']) ?>" <?= !empty($teacher) && $t['id'] == $teacher['id'] ? 'selected' : '' ?>><?= e($t['name'] ?? ('Teacher #' . $t['id'])) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php elseif (!empty($teacher)): ?>
        <input type="hidden" name="teacher_id" value="<?= e($teacher['id']) ?>">
        <p class="text-sm text-gray-600">Requesting leave for your own account.</p>
        <?php endif; ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Leave type *</label>
            <select name="leave_type_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                <option value="">Select type</option>
                <?php foreach ($types as $type): ?>
                <option value="<?= e($type['id']) ?>"><?= e($type['name_bn'] ?? $type['name_en']) ?> (<?= e((string) $type['days_per_year']) ?> days/yr)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From *</label>
                <input type="date" name="from_date" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To *</label>
                <input type="date" name="to_date" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason *</label>
            <textarea name="reason" rows="4" required maxlength="1000" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"></textarea>
        </div>
        <div class="flex justify-end gap-2">
            <a href="/dashboard/leaves" class="text-gray-600 hover:text-gray-800 px-4 py-2">Cancel</a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Submit request</button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>