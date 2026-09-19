<?php $pageTitle = 'Analytics'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Analytics</h1>
    <a href="/dashboard/reports" class="text-gray-600 hover:text-gray-800">&larr; Back to Reports</a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Fee target (monthly)</div>
        <div class="text-2xl font-bold mt-2">$<?= number_format((float) $feeTarget, 2) ?></div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Collected this month</div>
        <div class="text-2xl font-bold mt-2 text-emerald-600">$<?= number_format((float) $feeCollected, 2) ?></div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Target reached</div>
        <div class="text-2xl font-bold mt-2"><?= e((string) $feeTargetPercent) ?>%</div>
        <div class="h-2 bg-gray-200 rounded-full mt-3 overflow-hidden">
            <div class="h-full <?= $feeTargetPercent >= 75 ? 'bg-emerald-500' : 'bg-amber-500' ?>" style="width: <?= min(100, (int) $feeTargetPercent) ?>%"></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Active classes (attendance)</div>
        <div class="text-2xl font-bold mt-2"><?= count($attendanceByClass) ?></div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Student growth (12 months)</h2>
        <div class="flex items-end gap-2 h-40">
            <?php foreach ($studentGrowth as $i => $count): ?>
            <div class="flex-1 flex flex-col items-center gap-1">
                <div class="w-full bg-blue-500 rounded-t" style="height: <?= max(2, min(100, $count > 0 ? $count * 8 : 2)) ?>%"></div>
                <span class="text-[10px] text-gray-400 rotate-0 whitespace-nowrap"><?= e(date('m/y', strtotime($months[$i]))) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Revenue vs expenses (12 months)</h2>
        <?php $maxChartValue = max(1, max(array_merge($revenue, $expenses))); ?>
        <div class="relative h-40">
            <?php foreach (range(0, 4) as $g): ?>
            <div class="absolute left-0 right-0 border-t border-gray-100" style="top: <?= $g * 25 ?>%"></div>
            <?php endforeach; ?>
            <svg viewBox="0 0 600 160" class="w-full h-40" preserveAspectRatio="none">
                <polyline fill="none" stroke="#10b981" stroke-width="2" points="<?php
                    foreach ($revenue as $i => $v) {
                        $x = $i * 50 + 25;
                        $y = 160 - round(150 * $v / $maxChartValue);
                        echo $x . ',' . $y . ' ';
                    }
                ?>"></polyline>
                <polyline fill="none" stroke="#ef4444" stroke-width="2" points="<?php
                    foreach ($expenses as $i => $v) {
                        $x = $i * 50 + 25;
                        $y = 160 - round(150 * $v / $maxChartValue);
                        echo $x . ',' . $y . ' ';
                    }
                ?>"></polyline>
            </svg>
        </div>
        <div class="flex gap-4 mt-2 text-xs">
            <span class="flex items-center gap-1"><span class="inline-block w-3 h-0.5 bg-emerald-500"></span> Revenue</span>
            <span class="flex items-center gap-1"><span class="inline-block w-3 h-0.5 bg-red-500"></span> Expenses</span>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b">
            <h2 class="text-lg font-bold">Attendance by class (last 30 days)</h2>
        </div>
        <?php if (!empty($attendanceByClass)): ?>
        <div class="divide-y">
            <?php foreach ($attendanceByClass as $row): ?>
            <div class="px-6 py-3 flex items-center justify-between gap-4">
                <span class="font-medium"><?= e($row['class_name']) ?></span>
                <div class="flex items-center gap-3 flex-1">
                    <div class="h-2 bg-gray-200 rounded-full flex-1 overflow-hidden">
                        <div class="h-full <?= (float) $row['rate'] >= 75 ? 'bg-emerald-500' : ((float) $row['rate'] >= 50 ? 'bg-amber-500' : 'bg-red-500') ?>" style="width: <?= min(100, (float) $row['rate']) ?>%"></div>
                    </div>
                    <span class="text-sm font-semibold w-12 text-right"><?= e((string) $row['rate']) ?>%</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="p-8 text-center text-gray-500">No attendance data in the last 30 days.</div>
        <?php endif; ?>
    </div>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b">
            <h2 class="text-lg font-bold">Teacher workload (top 10)</h2>
        </div>
        <?php if (!empty($teacherWorkload)): ?>
        <div class="divide-y">
            <?php foreach ($teacherWorkload as $row): ?>
            <div class="px-6 py-3 flex items-center justify-between">
                <span class="font-medium"><?= e($row['teacher_name']) ?></span>
                <span class="text-sm text-gray-500"><?= e((string) $row['classes_count']) ?> classes</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="p-8 text-center text-gray-500">No class-teacher assignments found.</div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>