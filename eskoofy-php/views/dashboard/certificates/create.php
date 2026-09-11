<?php $pageTitle = 'New Certificate'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">New Certificate</h1>
    <a href="/dashboard/certificates" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <form action="/dashboard/certificates" method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Student *</label>
            <select name="student_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                <option value="">Select student</option>
                <?php foreach ($students as $student): ?>
                <option value="<?= e($student['id']) ?>"><?= e($student['name']) ?> (<?= e($student['admission_number']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Certificate type *</label>
                <select name="certificate_type" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">Select type</option>
                    <?php foreach ($types as $t): ?>
                    <option value="<?= $t ?>"><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Issue date *</label>
                <input type="date" name="issue_date" value="<?= date('Y-m-d') ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                <option value="draft">Draft</option>
                <option value="issued">Issued</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Body / content</label>
            <textarea name="body" rows="4" placeholder="Certificate body..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"></textarea>
        </div>
        <div class="flex justify-end gap-2">
            <a href="/dashboard/certificates" class="text-gray-600 hover:text-gray-800 px-4 py-2">Cancel</a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Generate</button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>