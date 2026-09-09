<?php $pageTitle = 'Student Detail'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <div>
    <h1 class="text-2xl font-bold text-gray-800"><?= e($student['name']) ?></h1>
    <p class="text-gray-500">ID: <?= e($student['admission_number'] ?? '') ?> | Class: <?= e($student['class_name'] ?? '') ?> | Roll: <?= e($student['roll_number'] ?? '') ?></p>
    </div>
    <div class="flex space-x-2">
        <a href="/dashboard/students/<?= e($student['id']) ?>/edit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Edit</a>
        <a href="/dashboard/students" class="text-gray-600 hover:text-gray-800">← Back</a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm mb-6">
    <div class="border-b">
        <nav class="flex -mb-px">
            <button class="tab-btn px-6 py-3 border-b-2 border-blue-600 text-blue-600 font-medium text-sm" data-tab="profile">Profile</button>
            <button class="tab-btn px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm" data-tab="attendance">Attendance</button>
            <button class="tab-btn px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm" data-tab="results">Results</button>
            <button class="tab-btn px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm" data-tab="fees">Fees</button>
        </nav>
    </div>

    <div id="tab-profile" class="tab-content p-6">
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <h3 class="font-bold mb-4">Personal Information</h3>
                <dl class="space-y-2">
                    <div class="flex"><dt class="w-40 text-gray-500">Name:</dt><dd class="font-medium"><?= e($student['name']) ?></dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Email:</dt><dd class="font-medium"><?= e($student['email'] ?? '-') ?></dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Phone:</dt><dd class="font-medium"><?= e($student['phone'] ?? '-') ?></dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Date of Birth:</dt><dd class="font-medium"><?= e(!empty($student['date_of_birth']) ? date('M d, Y', strtotime($student['date_of_birth'])) : '-') ?></dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Gender:</dt><dd class="font-medium"><?= e(ucfirst($student['gender'] ?? '-')) ?></dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Blood Group:</dt><dd class="font-medium"><?= e($student['blood_group'] ?? '-') ?></dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Religion:</dt><dd class="font-medium"><?= e($student['religion'] ?? '-') ?></dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Address:</dt><dd class="font-medium"><?= e($student['address'] ?? '-') ?></dd></div>
                </dl>
            </div>
            <div>
                <h3 class="font-bold mb-4">Parent Information</h3>
                <dl class="space-y-2">
                    <div class="flex"><dt class="w-40 text-gray-500">Father:</dt><dd class="font-medium"><?= e($student['father_name'] ?? '-') ?></dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Father Phone:</dt><dd class="font-medium"><?= e($student['father_phone'] ?? '-') ?></dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Mother:</dt><dd class="font-medium"><?= e($student['mother_name'] ?? '-') ?></dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Mother Phone:</dt><dd class="font-medium"><?= e($student['mother_phone'] ?? '-') ?></dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Guardian:</dt><dd class="font-medium"><?= e($student['guardian_name'] ?? '-') ?></dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Guardian Phone:</dt><dd class="font-medium"><?= e($student['guardian_phone'] ?? '-') ?></dd></div>
                </dl>

                <h3 class="font-bold mb-4 mt-6">Academic Information</h3>
                <dl class="space-y-2">
                    <div class="flex"><dt class="w-40 text-gray-500">Class:</dt><dd class="font-medium"><?= e($student['class_name'] ?? '-') ?></dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Section:</dt><dd class="font-medium"><?= e($student['section_name'] ?? '-') ?></dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Roll:</dt><dd class="font-medium"><?= e($student['roll_number'] ?? '-') ?></dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Status:</dt><dd class="font-medium"><span class="px-2 py-1 text-xs rounded-full <?= $student['status'] == 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= e(ucfirst($student['status'] ?? '')) ?></span></dd></div>
                </dl>
            </div>
        </div>
    </div>

    <div id="tab-attendance" class="tab-content p-6 hidden">
        <div class="mb-4 flex gap-4">
            <select id="attendance-month" class="border border-gray-300 rounded-lg px-4 py-2">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= date('m') == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m)) ?></option>
                <?php endfor; ?>
            </select>
            <select id="attendance-year" class="border border-gray-300 rounded-lg px-4 py-2">
                <?php for ($y = date('Y') - 2; $y <= date('Y'); $y++): ?>
                <option value="<?= $y ?>" <?= date('Y') == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="grid grid-cols-7 gap-2" id="attendance-calendar">
            <?php if (!empty($attendanceRecords)): ?>
                <?php foreach ($attendanceRecords as $record): ?>
                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-sm <?= $record['status'] == 'present' ? 'bg-green-100 text-green-700' : ($record['status'] == 'absent' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') ?>" title="<?= e(date('M d, Y', strtotime($record['date']))) ?>: <?= e(ucfirst($record['status'])) ?>">
                    <?= e(date('d', strtotime($record['date']))) ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="mt-4 flex gap-4 text-sm">
            <span class="flex items-center"><span class="w-3 h-3 bg-green-100 rounded mr-2"></span> Present</span>
            <span class="flex items-center"><span class="w-3 h-3 bg-red-100 rounded mr-2"></span> Absent</span>
            <span class="flex items-center"><span class="w-3 h-3 bg-yellow-100 rounded mr-2"></span> Late</span>
        </div>
    </div>

    <div id="tab-results" class="tab-content p-6 hidden">
        <?php if (!empty($results)): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b">
                        <th class="py-3 px-4 font-semibold">Exam</th>
                        <th class="py-3 px-4 font-semibold">Subject</th>
                        <th class="py-3 px-4 font-semibold text-center">Marks</th>
                        <th class="py-3 px-4 font-semibold text-center">Grade</th>
                        <th class="py-3 px-4 font-semibold text-center">GPA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4"><?= e($result['exam_name'] ?? '') ?></td>
                        <td class="py-3 px-4"><?= e($result['subject_name'] ?? '') ?></td>
                        <td class="py-3 px-4 text-center"><?= e($result['marks']) ?>/<?= e($result['total_marks'] ?? 100) ?></td>
                        <td class="py-3 px-4 text-center"><?= e($result['grade'] ?? '-') ?></td>
                        <td class="py-3 px-4 text-center"><?= e($result['gpa'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-gray-500 text-center py-8">No results available.</p>
        <?php endif; ?>
    </div>

    <div id="tab-fees" class="tab-content p-6 hidden">
        <?php if (!empty($feePayments)): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b">
                        <th class="py-3 px-4 font-semibold">Date</th>
                        <th class="py-3 px-4 font-semibold">Fee Type</th>
                        <th class="py-3 px-4 font-semibold text-center">Amount</th>
                        <th class="py-3 px-4 font-semibold text-center">Status</th>
                        <th class="py-3 px-4 font-semibold text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feePayments as $payment): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4"><?= e(date('M d, Y', strtotime($payment['created_at'] ?? 'now'))) ?></td>
                        <td class="py-3 px-4"><?= e($payment['fee_name'] ?? '') ?></td>
                        <td class="py-3 px-4 text-center"><?= e(format_currency((float)($payment['amount'] ?? 0))) ?></td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-1 text-xs rounded-full <?= ($payment['status'] ?? '') == 'paid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= e(ucfirst($payment['status'] ?? '')) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <a href="/dashboard/fee-payments/<?= e($payment['id']) ?>/receipt" class="text-blue-600 hover:underline text-sm">Receipt</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-gray-500 text-center py-8">No fee payments recorded.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('border-blue-600', 'text-blue-600');
            b.classList.add('border-transparent', 'text-gray-500');
        });
        this.classList.remove('border-transparent', 'text-gray-500');
        this.classList.add('border-blue-600', 'text-blue-600');
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        document.getElementById('tab-' + this.dataset.tab).classList.remove('hidden');
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>