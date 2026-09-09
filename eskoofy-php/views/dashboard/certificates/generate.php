<?php $pageTitle = 'Generate Certificate'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Generate Certificate</h1>
    <a href="/dashboard/certificates" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Certificate Details</h2>
        <form action="/dashboard/certificates/generate" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Student *</label>
                <select name="student_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Select Student</option>
                    <?php if (!empty($students)): ?>
                        <?php foreach ($students as $student): ?>
                        <option value="<?= e($student['id']) ?>" <?= ($student['id'] ?? 0) == ($student_id ?? 0) ? 'selected' : '' ?>><?= e($student['name'] ?? '') ?> (<?= e($student['admission_number'] ?? '') ?>)</option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Certificate Type *</label>
                <select name="certificate_type" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="transfer" <?= ($certificateType ?? '') === 'transfer' ? 'selected' : '' ?>>Transfer Certificate</option>
                    <option value="character" <?= ($certificateType ?? '') === 'character' ? 'selected' : '' ?>>Character Certificate</option>
                    <option value="bonafide" <?= ($certificateType ?? '') === 'bonafide' ? 'selected' : '' ?>>Bonafide Certificate</option>
                    <option value="attendance" <?= ($certificateType ?? '') === 'attendance' ? 'selected' : '' ?>>Attendance Certificate</option>
                    <option value="custom" <?= ($certificateType ?? '') === 'custom' ? 'selected' : '' ?>>Custom</option>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Generate</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Preview</h2>
        <?php if (!empty($generated)): ?>
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="bg-blue-600 text-white p-4 text-center">
                    <p class="text-lg font-bold"><?= e($settings['school_name'] ?? config('school.name', 'School')) ?></p>
                    <p class="text-sm opacity-80"><?= e($settings['address'] ?? '') ?></p>
                </div>
                <div class="p-6 text-center">
                    <h3 class="text-xl font-bold mb-4 uppercase"><?= e(ucfirst(str_replace('_', ' ', $certificateType ?? ''))) ?> Certificate</h3>
                    <p class="mb-2">This is to certify that</p>
                    <p class="text-xl font-bold mb-2"><?= e($student['name'] ?? '') ?></p>
                    <p class="text-sm text-gray-500 mb-1">Admission No: <?= e($student['admission_number'] ?? '') ?></p>
                    <p class="text-sm text-gray-500 mb-4">Class: <?= e($student['class_name'] ?? '') ?></p>
                    <p class="mb-6">is a bonafide student of this institution.</p>
                    <div class="flex justify-between mt-8 text-sm">
                        <div>
                            <p class="border-t border-gray-300 pt-1">Principal Signature</p>
                        </div>
                        <div>
                            <p class="border-t border-gray-300 pt-1">School Stamp</p>
                        </div>
                        <div>
                            <p class="border-t border-gray-300 pt-1">Date: <?= e(date('M d, Y')) ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 p-2 text-center text-xs text-gray-400">
                    <?= e($settings['phone'] ?? '') ?> | <?= e($settings['email'] ?? '') ?>
                </div>
            </div>
        </div>
        <div class="mt-4 flex justify-center">
            <button onclick="window.print()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Print Certificate</button>
        </div>
        <?php else: ?>
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center text-gray-500">
            Select a student and certificate type, then click Generate to preview.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
