<?php $pageTitle = 'Classes'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Classes</h1>
    <a href="/dashboard/classes/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Add Class</a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">ID</th>
                    <th class="py-3 px-4 font-semibold border-b">Name</th>
                    <th class="py-3 px-4 font-semibold border-b">Sections</th>
                    <th class="py-3 px-4 font-semibold border-b">Students</th>
                    <th class="py-3 px-4 font-semibold border-b">Monthly Fee</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($classes)): ?>
                    <?php foreach ($classes as $class): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4"><?= e($class->id) ?></td>
                        <td class="py-3 px-4 font-medium"><?= e($class->name) ?></td>
                        <td class="py-3 px-4"><?= e($class->sections->count()) ?></td>
                        <td class="py-3 px-4"><?= e($class->students_count ?? 0) ?></td>
                        <td class="py-3 px-4"><?= e(config('currency.symbol', '$')) ?><?= number_format($class->monthly_fee ?? 0, 2) ?></td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="/dashboard/classes/<?= e($class->id) ?>" class="text-blue-600 hover:underline text-sm">View</a>
                                <a href="/dashboard/classes/<?= e($class->id) ?>/edit" class="text-green-600 hover:underline text-sm">Edit</a>
                                <form action="/dashboard/classes/<?= e($class->id) ?>" method="POST" onsubmit="return confirm('Are you sure?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-500">No classes found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>