<?php $pageTitle = 'Admissions'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Admission Applications</h1>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/admissions" method="GET" class="flex flex-col sm:flex-row gap-4">
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Search..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        <select name="status" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">All Status</option>
            <option value="pending" <?= ($status ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="approved" <?= ($status ?? '') == 'approved' ? 'selected' : '' ?>>Approved</option>
            <option value="rejected" <?= ($status ?? '') == 'rejected' ? 'selected' : '' ?>>Rejected</option>
            <option value="enrolled" <?= ($status ?? '') == 'enrolled' ? 'selected' : '' ?>>Enrolled</option>
        </select>
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">ID</th>
                    <th class="py-3 px-4 font-semibold border-b">Name</th>
                    <th class="py-3 px-4 font-semibold border-b">Class</th>
                    <th class="py-3 px-4 font-semibold border-b">Father's Name</th>
                    <th class="py-3 px-4 font-semibold border-b">Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Status</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($admissions)): ?>
                    <?php foreach ($admissions as $admission): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4"><?= e($admission->id) ?></td>
                        <td class="py-3 px-4 font-medium"><?= e($admission->first_name) ?> <?= e($admission->last_name) ?></td>
                        <td class="py-3 px-4"><?= e($admission->class->name ?? '') ?></td>
                        <td class="py-3 px-4"><?= e($admission->father_name ?? '') ?></td>
                        <td class="py-3 px-4"><?= e($admission->created_at->format('M d, Y')) ?></td>
                        <td class="py-3 px-4">
                            <?php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-700',
                                'approved' => 'bg-green-100 text-green-700',
                                'rejected' => 'bg-red-100 text-red-700',
                                'enrolled' => 'bg-blue-100 text-blue-700',
                            ];
                            ?>
                            <span class="px-2 py-1 text-xs rounded-full <?= $statusColors[$admission->status] ?? 'bg-gray-100 text-gray-700' ?>">
                                <?= e(ucfirst($admission->status)) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <a href="/dashboard/admissions/<?= e($admission->id) ?>" class="text-blue-600 hover:underline text-sm">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-500">No admission applications found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>