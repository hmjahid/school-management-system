<?php $pageTitle = 'Visitor Logs'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Visitor Logs</h1>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-blue-600"><?= e(number_format((float) ($totalVisits ?? 0))) ?></div>
        <div class="text-xs text-gray-500 mt-1">Total Visits</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-green-600"><?= e(number_format((float) ($uniqueVisitors ?? 0))) ?></div>
        <div class="text-xs text-gray-500 mt-1">Unique Visitors</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-purple-600"><?= e(number_format((float) ($todayVisits ?? 0))) ?></div>
        <div class="text-xs text-gray-500 mt-1">Today</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-orange-600"><?= e(number_format((float) ($authenticatedVisits ?? 0))) ?></div>
        <div class="text-xs text-gray-500 mt-1">Signed-in</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/visitor-logs" method="GET" class="flex flex-col sm:flex-row gap-4">
        <input type="date" name="date_from" value="<?= e($date_from ?? '') ?>" class="border border-gray-300 rounded-lg px-4 py-2 text-sm">
        <input type="date" name="date_to" value="<?= e($date_to ?? '') ?>" class="border border-gray-300 rounded-lg px-4 py-2 text-sm">
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Search URL, IP or user..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm">
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 text-sm">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b text-sm">Time</th>
                    <th class="py-3 px-4 font-semibold border-b text-sm">URL</th>
                    <th class="py-3 px-4 font-semibold border-b text-sm">IP</th>
                    <th class="py-3 px-4 font-semibold border-b text-sm">Method</th>
                    <th class="py-3 px-4 font-semibold border-b text-sm">User</th>
                    <th class="py-3 px-4 font-semibold border-b text-sm">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e(date('M d, H:i:s', strtotime($row['created_at'] ?? 'now'))) ?></td>
                        <td class="py-3 px-4 text-sm font-medium max-w-md truncate"><?= e($row['url'] ?? '-') ?></td>
                        <td class="py-3 px-4 text-sm"><?= e($row['ip'] ?? '-') ?></td>
                        <td class="py-3 px-4 text-sm">
                            <span class="px-2 py-0.5 text-xs rounded-full <?= ($row['method'] ?? 'GET') === 'GET' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>"><?= e($row['method'] ?? 'GET') ?></span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($row['user_name'] ?? ($row['user_id'] ? '#' . $row['user_id'] : 'Guest')) ?></td>
                        <td class="py-3 px-4">
                            <form action="/dashboard/visitor-logs/<?= e($row['id']) ?>" method="POST" onsubmit="return confirm('Delete this log?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="py-8 text-center text-gray-500">No visitor logs found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>