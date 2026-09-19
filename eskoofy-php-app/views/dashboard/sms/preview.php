<?php $pageTitle = 'SMS Campaign Preview'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Campaign Preview</h1>
    <a href="/dashboard/sms/compose" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl mb-6">
    <h2 class="text-lg font-bold mb-2"><?= e($data['name'] ?? '') ?></h2>
    <p class="text-gray-700"><?= e($data['message'] ?? '') ?></p>
    <p class="text-sm text-gray-500 mt-3"><?= (int) $total ?> recipient(s)</p>
</div>

<form action="/dashboard/sms/campaign" method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="name" value="<?= e($data['name'] ?? '') ?>">
    <input type="hidden" name="audience_type" value="<?= e($data['audience_type'] ?? '') ?>">
    <input type="hidden" name="message" value="<?= e($data['message'] ?? '') ?>">
    <input type="hidden" name="school_class_id" value="<?= e($data['school_class_id'] ?? '') ?>">
    <input type="hidden" name="section_id" value="<?= e($data['section_id'] ?? '') ?>">
    <input type="hidden" name="role_name" value="<?= e($data['role_name'] ?? '') ?>">
    <?php if (!empty($data['scheduled_at'])): ?>
    <input type="hidden" name="scheduled_at" value="<?= e($data['scheduled_at']) ?>">
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="font-bold">Recipients (first 50)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="py-3 px-4 font-semibold border-b">Name</th>
                        <th class="py-3 px-4 font-semibold border-b">Phone</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recipients as $r): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 px-4"><?= e($r['name'] ?? '') ?></td>
                        <td class="py-2 px-4"><?= e($r['phone'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Send campaign</button>
        </div>
    </div>
</form>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>