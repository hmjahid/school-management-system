<?php $pageTitle = 'Exam Report'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Exam Results Report</h1>
    <div class="flex gap-2">
        <a href="/dashboard/reports" class="text-gray-600 hover:text-gray-800">← Back</a>
        <button onclick="window.print()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">🖨️ Print</button>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/reports/exams" method="GET" class="flex flex-col sm:flex-row gap-4">
        <select name="exam_id" required class="border border-gray-300 rounded-lg px-4 py-2">
            <option value="">Select Exam</option>
            <?php if (!empty($exams)): ?>
                <?php foreach ($exams as $exam): ?>
                <option value="<?= e($exam->id) ?>" <?= ($exam_id ?? '') == $exam->id ? 'selected' : '' ?>><?= e($exam->name) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <select name="class_id" class="border border-gray-300 rounded-lg px-4 py-2">
            <option value="">All Classes</option>
            <?php if (!empty($classes)): ?>
                <?php foreach ($classes as $class): ?>
                <option value="<?= e($class->id) ?>" <?= ($class_id ?? '') == $class->id ? 'selected' : '' ?>><?= e($class->name) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Generate</button>
    </form>
</div>

<?php if (!empty($report)): ?>
<div class="grid md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-blue-600"><?= e($report['total_students'] ?? 0) ?></div>
        <div class="text-sm text-gray-500">Total Students</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-green-600"><?= e($report['pass_rate'] ?? 0) ?>%</div>
        <div class="text-sm text-gray-500">Pass Rate</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-purple-600"><?= e($report['average_gpa'] ?? 0) ?></div>
        <div class="text-sm text-gray-500">Average GPA</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <div class="text-2xl font-bold text-yellow-600"><?= e($report['highest_gpa'] ?? 0) ?></div>
        <div class="text-sm text-gray-500">Highest GPA</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Rank</th>
                    <th class="py-3 px-4 font-semibold border-b">Student</th>
                    <th class="py-3 px-4 font-semibold border-b">Class</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">Total Marks</th>
                    <th class="py-3 px-4 font-semibold border-b text-center">GPA</th>
                    <th class="py-3 px-4 font-semibold border-b">Grade</th>
                    <th class="py-3 px-4 font-semibold border-b">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($results)): ?>
                    <?php foreach ($results as $index => $result): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-bold"><?= e($index + 1) ?></td>
                        <td class="py-3 px-4 font-medium"><?= e($result['student_name'] ?? '') ?></td>
                        <td class="py-3 px-4"><?= e($result['class'] ?? '') ?></td>
                        <td class="py-3 px-4 text-center"><?= e($result['total_marks'] ?? 0) ?></td>
                        <td class="py-3 px-4 text-center font-bold"><?= e($result['gpa'] ?? 0) ?></td>
                        <td class="py-3 px-4"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700"><?= e($result['grade'] ?? '') ?></span></td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded-full <?= ($result['is_pass'] ?? false) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= ($result['is_pass'] ?? false) ? 'Pass' : 'Fail' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="py-8 text-center text-gray-500">No results found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>