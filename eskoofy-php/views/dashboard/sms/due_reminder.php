<?php $pageTitle = 'Due Fee Reminder'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Due Fee Reminder</h1>
    <a href="/dashboard/sms" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Recipients with dues</div>
        <div class="text-2xl font-bold mt-2"><?= (int) $recipientCount ?></div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total outstanding</div>
        <div class="text-2xl font-bold mt-2 text-amber-600">$<?= number_format((float) $totalDue, 2) ?></div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl mb-6">
    <form action="/dashboard/sms/due-reminder" method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
            <textarea name="message" rows="4" required maxlength="1000" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"><?= e($defaultMessage) ?></textarea>
            <p class="text-xs text-gray-500 mt-1">Use {{amount}} as a placeholder for the due amount.</p>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Send reminder</button>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-4 border-b">
        <h3 class="font-bold">Recipients (first 50)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Student</th>
                    <th class="py-3 px-4 font-semibold border-b">Phone</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Outstanding</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recipients as $r): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4 font-medium"><?= e($r['name'] ?? '') ?></td>
                    <td class="py-3 px-4"><?= e($r['phone'] ?? '') ?></td>
                    <td class="py-3 px-4 text-right">$<?= number_format((float) $r['due'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>