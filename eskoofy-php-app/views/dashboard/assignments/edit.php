<?php $pageTitle = 'Edit Assignment'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Edit Assignment</h1>
    <a href="/dashboard/assignments" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <form action="/dashboard/assignments/<?= e($assignment['id']) ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <input type="text" name="title" required maxlength="255" value="<?= e($assignment['title'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                <select name="subject_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($subjects as $subject): ?>
                    <option value="<?= e($subject['id']) ?>" <?= ($assignment['subject_id'] ?? 0) == $subject['id'] ? 'selected' : '' ?>><?= e($subject['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Batch *</label>
                <select name="batch_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($batches as $batch): ?>
                    <option value="<?= e($batch['id']) ?>" <?= ($assignment['batch_id'] ?? 0) == $batch['id'] ? 'selected' : '' ?>><?= e($batch['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Due date *</label>
                <input type="datetime-local" name="due_date" required value="<?= e(date('Y-m-d\TH:i', strtotime($assignment['due_date'] ?? 'now'))) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Total marks</label>
                <input type="number" name="total_marks" min="0" value="<?= e($assignment['total_marks'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Attachment</label>
            <input type="file" name="file" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <?php if (!empty($assignment['file_path'])): ?>
            <p class="text-xs text-gray-500 mt-1">Current file: <?= e($assignment['file_path']) ?></p>
            <?php endif; ?>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="allow_guardian_notes" value="0">
            <input type="checkbox" name="allow_guardian_notes" value="1" <?= !empty($assignment['allow_guardian_notes']) ? 'checked' : '' ?> class="rounded">
            Allow guardian notes
        </label>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"><?= e($assignment['description'] ?? '') ?></textarea>
        </div>
        <div class="flex justify-end gap-2">
            <form action="/dashboard/assignments/<?= e($assignment['id']) ?>" method="POST" class="inline mr-auto" onsubmit="return confirm('Delete this assignment?')">
                <?= csrf_field() ?>
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="text-red-600 hover:underline px-4 py-2">Delete</button>
            </form>
            <a href="/dashboard/assignments" class="text-gray-600 hover:text-gray-800 px-4 py-2">Cancel</a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Update</button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>