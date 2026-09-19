<?php $pageTitle = 'Exams'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Exams</h1>
    <a href="/dashboard/exams/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Create Exam</a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Name</th>
                    <th class="py-3 px-4 font-semibold border-b">Type</th>
                    <th class="py-3 px-4 font-semibold border-b">Class</th>
                    <th class="py-3 px-4 font-semibold border-b">Start Date</th>
                    <th class="py-3 px-4 font-semibold border-b">End Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Status</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($exams)): ?>
                    <?php foreach ($exams as $exam): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($exam['name']) ?></td>
                        <td class="py-3 px-4"><?= e(ucfirst($exam['type'] ?? 'Final')) ?></td>
                        <td class="py-3 px-4"><?= e($exam['batch_name'] ?? '-') ?></td>
                        <td class="py-3 px-4"><?= e(date('M d, Y', strtotime($exam['start_date']))) ?></td>
                        <td class="py-3 px-4"><?= e(date('M d, Y', strtotime($exam['end_date']))) ?></td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded-full <?= ($exam['is_published'] ?? false) ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                <?= ($exam['is_published'] ?? false) ? 'Published' : 'Draft' ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="/dashboard/exams/<?= e($exam['id']) ?>" class="text-blue-600 hover:underline text-sm">View</a>
                                <a href="/dashboard/exams/<?= e($exam['id']) ?>/edit" class="text-green-600 hover:underline text-sm">Edit</a>
                                <?php if (!($exam['is_published'] ?? false)): ?>
                                <form action="/dashboard/exams/<?= e($exam['id']) ?>/publish" method="POST" onsubmit="return confirm('Publish this exam? Results will be visible to students.')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-purple-600 hover:underline text-sm">Publish</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-500">No exams found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>