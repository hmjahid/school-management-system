<?php $pageTitle = 'SMS Templates'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">SMS Templates</h1>
    <a href="/dashboard/sms/compose" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Name</th>
                    <th class="py-3 px-4 font-semibold border-b">Subject</th>
                    <th class="py-3 px-4 font-semibold border-b">Body</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($templates)): ?>
                    <?php foreach ($templates as $t): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($t['name'] ?? '') ?></td>
                        <td class="py-3 px-4"><?= e($t['subject'] ?? '') ?></td>
                        <td class="py-3 px-4 text-sm text-gray-600"><?= e(truncate($t['sms_content'] ?? '', 80)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="3" class="py-8 text-center text-gray-500">No SMS templates found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>