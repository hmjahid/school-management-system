<?php $pageTitle = 'Admit Cards'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Admit Cards</h1>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Generate Admit Cards</h2>
        <form action="/dashboard/admit-cards/generate" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Exam *</label>
                <select name="exam_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Select Exam</option>
                    <?php if (!empty($exams)): ?>
                        <?php foreach ($exams as $exam): ?>
                        <option value="<?= e($exam->id) ?>"><?= e($exam->name) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class *</label>
                <select name="class_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Select Class</option>
                    <?php if (!empty($classes)): ?>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?= e($class->id) ?>"><?= e($class->name) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="all_classes" value="1" class="rounded border-gray-300 text-blue-600 mr-2">
                    <span class="text-sm text-gray-700">Generate for all classes</span>
                </label>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Generate</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Recent Admit Cards</h2>
        <div class="space-y-3 max-h-96 overflow-y-auto">
            <?php if (!empty($admitCards)): ?>
                <?php foreach ($admitCards as $card): ?>
                <div class="flex justify-between items-center border-b pb-3">
                    <div>
                        <p class="font-medium text-sm"><?= e($card->student->name ?? '') ?></p>
                        <p class="text-xs text-gray-500"><?= e($card->exam->name ?? '') ?> | <?= e($card->created_at->format('M d, Y')) ?></p>
                    </div>
                    <a href="/dashboard/admit-cards/<?= e($card->id) ?>/print" target="_blank" class="text-blue-600 hover:underline text-sm">Print</a>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">No admit cards generated yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>