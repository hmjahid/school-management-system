<?php $pageTitle = 'Contact Submissions'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Contact Submissions</h1>
    <a href="/dashboard/contact-submissions/export" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Export CSV</a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Type</th>
                    <th class="py-3 px-4 font-semibold border-b">Name</th>
                    <th class="py-3 px-4 font-semibold border-b">Email</th>
                    <th class="py-3 px-4 font-semibold border-b">Subject</th>
                    <th class="py-3 px-4 font-semibold border-b">Message</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($submissions)): ?>
                    <?php foreach ($submissions as $s): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e(date('M d, Y', strtotime($s['created_at'] ?? 'now'))) ?></td>
                        <td class="py-3 px-4"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700"><?= e($s['type']) ?></span></td>
                        <td class="py-3 px-4"><?= e($s['name']) ?></td>
                        <td class="py-3 px-4 text-sm"><?= e($s['email']) ?></td>
                        <td class="py-3 px-4 text-sm"><?= e(truncate($s['subject'] ?? '', 50)) ?></td>
                        <td class="py-3 px-4 text-sm text-gray-600"><?= e(truncate($s['message'] ?? '', 60)) ?></td>
                        <td class="py-3 px-4">
                            <form action="/dashboard/contact-submissions/<?= e($s['id']) ?>" method="POST" onsubmit="return confirm('Delete?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="py-8 text-center text-gray-500">No submissions yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/dashboard.php'; ?>
