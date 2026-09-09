<?php $pageTitle = 'Certificates'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Certificates</h1>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Generate Certificate</h2>
        <form action="/dashboard/certificates/generate" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Student *</label>
                <input type="text" name="student_id" placeholder="Student ID" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Certificate Type *</label>
                <select name="type" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="transfer">Transfer Certificate</option>
                    <option value="character">Character Certificate</option>
                    <option value="bonafide">Bonafide Certificate</option>
                    <option value="attendance">Attendance Certificate</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Custom Title</label>
                <input type="text" name="title" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Content/Remarks</label>
                <textarea name="content" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2"></textarea>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Generate</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Recent Certificates</h2>
        <div class="space-y-3 max-h-96 overflow-y-auto">
            <?php if (!empty($certificates)): ?>
                <?php foreach ($certificates as $cert): ?>
                <div class="flex justify-between items-center border-b pb-3">
                    <div>
                        <p class="font-medium text-sm"><?= e($cert['student']['name'] ?? '') ?></p>
                        <p class="text-xs text-gray-500"><?= e(ucfirst($cert['type'])) ?> | <?= e(date('M d, Y', strtotime($cert['created_at']))) ?></p>
                    </div>
                    <a href="/dashboard/certificates/<?= e($cert['id']) ?>/print" target="_blank" class="text-blue-600 hover:underline text-sm">Print</a>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">No certificates generated yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>