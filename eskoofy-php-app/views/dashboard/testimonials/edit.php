<?php $pageTitle = 'Edit Testimonial'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Edit Testimonial</h1>
    <a href="/dashboard/testimonials" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <form action="/dashboard/testimonials/<?= e($testimonial['id']) ?>" method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Student *</label>
            <select name="student_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                <?php foreach ($students as $student): ?>
                <option value="<?= e($student['id']) ?>" <?= ($student['id'] == ($testimonial['student_id'] ?? 0)) ? 'selected' : '' ?>><?= e($student['name']) ?> (<?= e($student['admission_number']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                <select name="testimonial_type" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($types as $t): ?>
                    <option value="<?= $t ?>" <?= ($testimonial['testimonial_type'] ?? '') === $t ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $t)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                <input type="text" name="name" required maxlength="255" value="<?= e($testimonial['name'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Issue date *</label>
                <input type="date" name="issue_date" value="<?= e($testimonial['issue_date'] ?? date('Y-m-d')) ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <?php foreach (['draft', 'issued', 'revoked'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= ($testimonial['status'] ?? '') === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Body / content</label>
            <?php $bodyStr = is_array($testimonial['body'] ?? null) ? implode("\n", $testimonial['body']) : (string) ($testimonial['body'] ?? ''); ?>
            <textarea name="body" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"><?= e($bodyStr) ?></textarea>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Author name</label>
                <input type="text" name="author_name" maxlength="255" value="<?= e($testimonial['author_name'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Author designation</label>
                <input type="text" name="author_designation" maxlength="255" value="<?= e($testimonial['author_designation'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
            <select name="rating" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                <?php for ($r = 1; $r <= 5; $r++): ?>
                <option value="<?= $r ?>" <?= (int) ($testimonial['rating'] ?? 5) === $r ? 'selected' : '' ?>><?= $r ?> star<?= $r > 1 ? 's' : '' ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="flex justify-end gap-2">
            <a href="/dashboard/testimonials" class="text-gray-600 hover:text-gray-800 px-4 py-2">Cancel</a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Update</button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>