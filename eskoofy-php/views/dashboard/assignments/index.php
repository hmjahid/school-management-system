<?php $pageTitle = 'Assignments'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Assignments</h1>
    <a href="/dashboard/assignments/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Create Assignment</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/assignments" method="GET" class="flex flex-col sm:flex-row gap-4">
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Search assignments..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        <select name="class_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">All Classes</option>
            <?php if (!empty($classes)): ?>
                <?php foreach ($classes as $class): ?>
                <option value="<?= e($class['id']) ?>" <?= ($class_id ?? '') == $class['id'] ? 'selected' : '' ?>><?= e($class['name']) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
    </form>
</div>

<div class="space-y-4">
    <?php if (!empty($assignments)): ?>
        <?php foreach ($assignments as $assignment): ?>
        <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <h3 class="text-lg font-bold mb-1">
                        <a href="/dashboard/assignments/<?= e($assignment['id']) ?>" class="text-blue-600 hover:underline"><?= e($assignment['title']) ?></a>
                    </h3>
                    <div class="flex flex-wrap gap-2 text-sm text-gray-500 mb-2">
                        <span>📚 <?= e($assignment['subject']['name'] ?? '') ?></span>
                        <span>🏫 <?= e($assignment['class']['name'] ?? '') ?></span>
                        <span>📅 Due: <?= e(date('M d, Y', strtotime($assignment['due_date']))) ?></span>
                    </div>
                    <p class="text-gray-600 text-sm"><?= e(truncate($assignment['description'] ?? '', 150)) ?></p>
                </div>
                <div class="text-right ml-4">
                    <span class="text-sm font-bold text-blue-600"><?= e($assignment['submissions_count'] ?? 0) ?> submissions</span>
                    <p class="text-xs text-gray-500 mt-1"><?= e($assignment['total_marks'] ?? 100) ?> marks</p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
            No assignments found.
        </div>
    <?php endif; ?>
</div>

<?php if (isset($paginator) && $paginator['hasPages']()): ?>
    <div class="mt-6"><?php include __DIR__ . '/../../partials/pagination.php'; ?></div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>