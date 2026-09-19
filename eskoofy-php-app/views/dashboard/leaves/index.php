<?php $pageTitle = 'Leaves'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Leave Requests</h1>
    <a href="/dashboard/leaves/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ New Request</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/leaves" method="GET" class="flex flex-col sm:flex-row gap-4">
        <select name="status" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">All statuses</option>
            <?php foreach (['pending', 'approved', 'rejected', 'cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= ($status ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button></noscript>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Teacher</th>
                    <th class="py-3 px-4 font-semibold border-b">Type</th>
                    <th class="py-3 px-4 font-semibold border-b">Dates</th>
                    <th class="py-3 px-4 font-semibold border-b">Days</th>
                    <th class="py-3 px-4 font-semibold border-b">Status</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $leave): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($leave['teacher_name'] ?? '—') ?></td>
                        <td class="py-3 px-4"><?= e($leave['leave_type_bn'] ?? $leave['leave_type'] ?? '') ?></td>
                        <td class="py-3 px-4">
                            <?= e(date('M j, Y', strtotime($leave['from_date']))) ?> &rarr; <?= e(date('M j, Y', strtotime($leave['to_date']))) ?>
                        </td>
                        <td class="py-3 px-4"><?= e((string) ($leave['days'] ?? 1)) ?></td>
                        <td class="py-3 px-4">
                            <?php $st = $leave['status'] ?? 'pending'; ?>
                            <span class="px-2 py-1 text-xs rounded-full <?= $st === 'approved' ? 'bg-green-100 text-green-700' : ($st === 'rejected' ? 'bg-red-100 text-red-700' : ($st === 'cancelled' ? 'bg-gray-100 text-gray-600' : 'bg-amber-100 text-amber-700')) ?>">
                                <?= e(ucfirst($st)) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <a href="/dashboard/leaves/<?= e($leave['id']) ?>" class="text-blue-600 hover:underline text-sm">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="6" class="py-8 text-center text-gray-500">No leave requests found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>