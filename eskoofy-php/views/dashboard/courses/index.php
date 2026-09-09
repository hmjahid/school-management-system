<?php $pageTitle = 'Courses'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Courses</h1>
    <button onclick="document.getElementById('create-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Add Course</button>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/courses" method="GET" class="flex flex-col sm:flex-row gap-4">
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Search courses..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Name</th>
                    <th class="py-3 px-4 font-semibold border-b">Code</th>
                    <th class="py-3 px-4 font-semibold border-b">Class</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Subjects</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Status</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($row['name'] ?? '') ?></td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($row['code'] ?? '') ?></td>
                        <td class="py-3 px-4"><?= e($row['class_name'] ?? '-') ?></td>
                        <td class="py-3 px-4 text-center"><?= e($row['subject_count'] ?? 0) ?></td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-1 text-xs rounded-full <?= ($row['status'] ?? '') === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= e(ucfirst($row['status'] ?? 'active')) ?></span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <button onclick='editCourse(<?= e(json_encode($row)) ?>)' class="text-green-600 hover:underline text-sm">Edit</button>
                                <form action="/dashboard/courses/<?= e($row['id']) ?>" method="POST" onsubmit="return confirm('Delete?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="py-8 text-center text-gray-500">No courses found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($lastPage > 1): ?>
    <div class="p-6 flex justify-center space-x-2">
        <?php for ($i = 1; $i <= $lastPage; $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= e($search ?? '') ?>" class="px-3 py-1 rounded-lg <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<div id="create-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Add Course</h2>
        <form action="/dashboard/courses" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="POST">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Course Name *</label>
                <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Code *</label>
                <input type="text" name="code" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCourse(course) {
    document.getElementById('create-modal').querySelector('h2').textContent = 'Edit Course';
    const form = document.getElementById('create-modal').querySelector('form');
    form.action = '/dashboard/courses/' + course.id;
    form.querySelector('input[name=_method]').value = 'PUT';
    form.querySelector('input[name=name]').value = course.name || '';
    form.querySelector('input[name=code]').value = course.code || '';
    form.querySelector('textarea[name=description]').value = course.description || '';
    form.querySelector('select[name=status]').value = course.status || 'active';
    document.getElementById('create-modal').classList.remove('hidden');
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
