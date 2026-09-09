<?php $pageTitle = 'Assignment Detail'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800"><?= e($assignment->title) ?></h1>
        <p class="text-gray-500"><?= e($assignment->subject->name ?? '') ?> | Due: <?= e($assignment->due_date->format('M d, Y H:i')) ?></p>
    </div>
    <a href="/dashboard/assignments" class="text-gray-600 hover:text-gray-800">← Back</a>
</div>

<div class="grid md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-blue-600"><?= e($assignment->total_marks ?? 100) ?></div>
        <div class="text-gray-500">Total Marks</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-green-600"><?= e($assignment->submissions_count ?? 0) ?></div>
        <div class="text-gray-500">Submissions</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="text-3xl font-bold text-purple-600"><?= e($assignment->avg_marks ?? '-') ?></div>
        <div class="text-gray-500">Average Marks</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <h2 class="text-lg font-bold mb-4">Description</h2>
    <div class="prose prose-sm max-w-none">
        <?= nl2br(e($assignment->description ?? '')) ?>
    </div>
    <?php if ($assignment->attachment ?? null): ?>
    <div class="mt-4">
        <a href="/uploads/assignments/<?= e($assignment->attachment) ?>" class="text-blue-600 hover:underline" download>📎 Download Attachment</a>
    </div>
    <?php endif; ?>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-lg font-bold">Submissions (<?= e($submissions->count() ?? 0) ?>)</h2>
    </div>
    <?php if (!empty($submissions)): ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Student</th>
                    <th class="py-3 px-4 font-semibold border-b">Submitted</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Marks</th>
                    <th class="py-3 px-4 font-semibold border-b">Status</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $submission): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4"><?= e($submission->student->name ?? '') ?></td>
                    <td class="py-3 px-4 text-sm"><?= e($submission->submitted_at->format('M d, Y H:i')) ?></td>
                    <td class="py-3 px-4 text-center font-bold"><?= e($submission->marks ?? '-') ?></td>
                    <td class="py-3 px-4">
                        <span class="px-2 py-1 text-xs rounded-full <?= ($submission->status ?? '') == 'graded' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                            <?= e(ucfirst($submission->status ?? 'pending')) ?>
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        <a href="/dashboard/assignments/submissions/<?= e($submission->id) ?>" class="text-blue-600 hover:underline text-sm">Grade</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="p-8 text-center text-gray-500">No submissions yet.</div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>