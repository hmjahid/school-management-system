<?php $pageTitle = 'Seat Plans'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Seat Plans</h1>
    <p class="text-gray-500">Generate exam seating arrangements</p>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead><tr class="bg-gray-50"><th class="py-3 px-4 font-semibold border-b">Exam</th><th class="py-3 px-4 font-semibold border-b">Date</th><th class="py-3 px-4 font-semibold border-b">Action</th></tr></thead>
            <tbody>
                <?php if (!empty($exams)): ?>
                    <?php foreach ($exams as $e): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($e['name']) ?></td>
                        <td class="py-3 px-4 text-sm"><?= e(date('M d, Y', strtotime($e['exam_date'] ?? 'now'))) ?></td>
                        <td class="py-3 px-4"><a href="/dashboard/seat-plans/<?= e($e['id']) ?>/generate" class="text-blue-600 hover:underline text-sm">Generate</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="py-8 text-center text-gray-500">No exams found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/dashboard.php'; ?>
