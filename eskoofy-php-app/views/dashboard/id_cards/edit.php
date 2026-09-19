<?php $pageTitle = 'Edit Student ID Card'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Edit Student ID Card</h1>
    <a href="/dashboard/id-cards" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <form action="/dashboard/id-cards/<?= e($card['id']) ?>" method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Student *</label>
            <select name="student_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                <?php foreach ($students as $student): ?>
                <option value="<?= e($student['id']) ?>" <?= ($student['id'] == ($card['student_id'] ?? 0)) ? 'selected' : '' ?>><?= e($student['name']) ?> (<?= e($student['admission_number']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Issue date *</label>
                <input type="date" name="issue_date" value="<?= e($card['issue_date'] ?? date('Y-m-d')) ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Expiry date</label>
                <input type="date" name="expiry_date" value="<?= e($card['expiry_date'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Blood group</label>
            <input type="text" name="blood_group" value="<?= e($card['blood_group'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                <?php foreach (['active', 'expired', 'revoked'] as $opt): ?>
                <option value="<?= $opt ?>" <?= ($card['status'] ?? '') === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex justify-end gap-2">
            <a href="/dashboard/id-cards" class="text-gray-600 hover:text-gray-800 px-4 py-2">Cancel</a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Update</button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>