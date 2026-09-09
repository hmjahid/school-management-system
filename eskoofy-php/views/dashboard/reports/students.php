<?php $pageTitle = 'Student Report'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Student Report</h1>
    <a href="/dashboard/reports" class="text-gray-600 hover:text-gray-800">← Back to Reports</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/reports/students" method="GET" class="flex flex-col sm:flex-row gap-4">
        <select name="class_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">All Classes</option>
            <?php if (!empty($classes)): ?>
                <?php foreach ($classes as $class): ?>
                <option value="<?= e($class->id) ?>" <?= ($class_id ?? '') == $class->id ? 'selected' : '' ?>><?= e($class->name) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <select name="status" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">All Status</option>
            <option value="active" <?= ($status ?? '') == 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($status ?? '') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Generate Report</button>
        <button type="button" onclick="window.print()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200">🖨️ Print</button>
    </form>
</div>

<div class="grid md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-blue-600"><?= e($report['total'] ?? 0) ?></div>
        <div class="text-sm text-gray-500">Total Students</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-green-600"><?= e($report['male'] ?? 0) ?></div>
        <div class="text-sm text-gray-500">Male</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-pink-600"><?= e($report['female'] ?? 0) ?></div>
        <div class="text-sm text-gray-500">Female</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-green-600"><?= e($report['active'] ?? 0) ?></div>
        <div class="text-sm text-gray-500">Active</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">ID</th>
                    <th class="py-3 px-4 font-semibold border-b">Name</th>
                    <th class="py-3 px-4 font-semibold border-b">Class</th>
                    <th class="py-3 px-4 font-semibold border-b">Section</th>
                    <th class="py-3 px-4 font-semibold border-b">Roll</th>
                    <th class="py-3 px-4 font-semibold border-b">Gender</th>
                    <th class="py-3 px-4 font-semibold border-b">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($students)): ?>
                    <?php foreach ($students as $student): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 text-sm"><?= e($student->student_id) ?></td>
                        <td class="py-3 px-4 font-medium"><?= e($student->name) ?></td>
                        <td class="py-3 px-4"><?= e($student->class->name ?? '') ?></td>
                        <td class="py-3 px-4"><?= e($student->section->name ?? '') ?></td>
                        <td class="py-3 px-4"><?= e($student->roll ?? '-') ?></td>
                        <td class="py-3 px-4"><?= e(ucfirst($student->gender ?? '')) ?></td>
                        <td class="py-3 px-4"><span class="px-2 py-1 text-xs rounded-full <?= $student->status == 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= e(ucfirst($student->status)) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="py-8 text-center text-gray-500">No students found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>