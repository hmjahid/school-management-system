<?php $pageTitle = 'Edit Exam'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Edit Exam: <?= e($exam->name) ?></h1>
    <a href="/dashboard/exams/<?= e($exam->id) ?>" class="text-gray-600 hover:text-gray-800">← Back to Exam</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6">
    <form action="/dashboard/exams/<?= e($exam->id) ?>" method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Exam Name *</label>
                <input type="text" name="name" value="<?= e($exam->name) ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                <select name="type" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <?php foreach (['midterm', 'final', 'quiz', 'monthly', 'custom'] as $type): ?>
                    <option value="<?= $type ?>" <?= ($exam->type ?? '') == $type ? 'selected' : '' ?>><?= ucfirst($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class *</label>
                <select name="class_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <?php if (!empty($classes)): ?>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?= e($class->id) ?>" <?= $exam->class_id == $class->id ? 'selected' : '' ?>><?= e($class->name) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Session</label>
                <select name="academic_session_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Session</option>
                    <?php if (!empty($sessions)): ?>
                        <?php foreach ($sessions as $session): ?>
                        <option value="<?= e($session->id) ?>" <?= ($exam->academic_session_id ?? '') == $session->id ? 'selected' : '' ?>><?= e($session->name) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
                <input type="date" name="start_date" value="<?= e($exam->start_date->format('Y-m-d')) ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">End Date *</label>
                <input type="date" name="end_date" value="<?= e($exam->end_date->format('Y-m-d')) ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"><?= e($exam->description ?? '') ?></textarea>
            </div>
        </div>
        <div class="flex justify-end space-x-4">
            <a href="/dashboard/exams/<?= e($exam->id) ?>" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Update Exam</button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>