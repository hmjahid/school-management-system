<?php $pageTitle = 'Results'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Check Results</h1>
        <p class="text-blue-100 text-lg">View your exam results online</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-sm p-8">
            <h2 class="text-xl font-bold mb-6 text-center">Enter Your Details</h2>
            <form action="/results" method="GET" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Student ID or Roll Number</label>
                    <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Enter Student ID or Roll Number" required class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Exam</label>
                    <select name="exam_id" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select an exam</option>
                        <?php if (!empty($exams)): ?>
                            <?php foreach ($exams as $exam): ?>
                            <option value="<?= e($exam->id) ?>" <?= ($exam_id ?? '') == $exam->id ? 'selected' : '' ?>><?= e($exam->name) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">Check Results</button>
            </form>
        </div>

        <?php if (!empty($results)): ?>
        <div class="mt-8 bg-white rounded-xl shadow-sm p-8">
            <h2 class="text-xl font-bold mb-4">Results for <?= e($student->name ?? '') ?></h2>
            <p class="text-gray-500 text-sm mb-4">Class: <?= e($student->class->name ?? '') ?> | Roll: <?= e($student->roll ?? '') ?></p>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b">
                            <th class="py-3 px-4 font-semibold">Subject</th>
                            <th class="py-3 px-4 font-semibold text-center">Marks</th>
                            <th class="py-3 px-4 font-semibold text-center">Grade</th>
                            <th class="py-3 px-4 font-semibold text-center">GPA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-4"><?= e($result->subject->name ?? '') ?></td>
                            <td class="py-3 px-4 text-center"><?= e($result->marks) ?>/<?= e($result->subject->total_marks ?? 100) ?></td>
                            <td class="py-3 px-4 text-center"><?= e($result->grade ?? '-') ?></td>
                            <td class="py-3 px-4 text-center"><?= e($result->gpa ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 font-bold">
                            <td class="py-3 px-4">Total / Average</td>
                            <td class="py-3 px-4 text-center"><?= e($totalMarks ?? '') ?></td>
                            <td class="py-3 px-4 text-center"><?= e($overallGrade ?? '') ?></td>
                            <td class="py-3 px-4 text-center"><?= e($overallGpa ?? '') ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500">Position: <span class="font-bold"><?= e($position ?? '-') ?></span></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($search) && empty($results)): ?>
        <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
            <p class="text-yellow-700">No results found for the provided information. Please check and try again.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>