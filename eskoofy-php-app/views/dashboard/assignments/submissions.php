<?php $pageTitle = 'Assignment Submissions'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Submissions: <?= e($assignment['title'] ?? '') ?></h1>
        <p class="text-gray-500"><?= e($assignment['subject_name'] ?? '') ?> | Total marks: <?= e($assignment['total_marks'] ?? 'N/A') ?></p>
    </div>
    <a href="/dashboard/assignments/<?= e($assignment['id']) ?>" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Student</th>
                    <th class="py-3 px-4 font-semibold border-b">Submitted at</th>
                    <th class="py-3 px-4 font-semibold border-b">Status</th>
                    <th class="py-3 px-4 font-semibold border-b">Marks</th>
                    <th class="py-3 px-4 font-semibold border-b">Guardian notes</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($submissions)): ?>
                    <?php foreach ($submissions as $sub): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($sub['student_name'] ?? 'N/A') ?></td>
                        <td class="py-3 px-4"><?= !empty($sub['submitted_at']) ? e(date('d M Y H:i', strtotime($sub['submitted_at']))) : 'Not submitted' ?></td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded-full <?= ($sub['status'] ?? '') === 'graded' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' ?>">
                                <?= e(ucfirst(str_replace('_', ' ', $sub['status'] ?? 'submitted'))) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4"><?= $sub['marks'] !== null ? e((string) $sub['marks']) . ' / ' . e((string) $assignment['total_marks']) : '- / -' ?></td>
                        <td class="py-3 px-4 text-sm text-gray-600"><?= e($sub['guardian_notes'] ?? '—') ?></td>
                        <td class="py-3 px-4">
                            <?php if (($sub['status'] ?? '') !== 'graded'): ?>
                            <form action="/dashboard/assignments/submissions/<?= e($sub['id']) ?>/grade" method="POST" class="flex items-center gap-2">
                                <?= csrf_field() ?>
                                <input type="number" name="marks" required min="0" max="<?= e($assignment['total_marks'] ?: '') ?>" placeholder="Marks" class="w-20 border border-gray-300 rounded-lg px-2 py-1 text-sm">
                                <input type="text" name="feedback" maxlength="1000" placeholder="Feedback" class="w-40 border border-gray-300 rounded-lg px-2 py-1 text-sm">
                                <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-blue-700">Grade</button>
                            </form>
                            <?php else: ?>
                            <span class="text-sm text-gray-500"><?= e($sub['feedback'] ?? '') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="6" class="py-8 text-center text-gray-500">No submissions yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>