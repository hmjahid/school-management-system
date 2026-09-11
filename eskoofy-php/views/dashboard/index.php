<?php $pageTitle = 'Dashboard'; ?>
<?php ob_start(); ?>

<?php $currentUser = auth()->user(); ?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Welcome back, <?= e($currentUser['name'] ?? 'Admin') ?>!</h1>
    <p class="text-gray-500">Here's what's happening at your school today.</p>
</div>

<?php
    $stats = [
        'total_students'        => $totalStudents ?? 0,
        'total_teachers'        => $totalTeachers ?? 0,
        'total_classes'         => $totalClasses ?? 0,
        'total_sections'        => $totalSections ?? 0,
        'students_growth'       => 0,
        'teachers_growth'       => 0,
        'fees_collected'        => $totalRevenue ?? 0,
        'fees_pending'          => $pendingDues ?? 0,
        'fees_growth'           => 0,
        'fees_collection_rate'  => $totalRevenue > 0 ? min(100, (int) round(100 * $totalRevenue / max(1, ($totalRevenue + $pendingDues)))) : 0,
        'total_fees'            => ($totalRevenue ?? 0) + ($pendingDues ?? 0),
        'today_attendance_rate' => $attendanceRate ?? 0,
        'present_today'         => $todayPresent ?? 0,
        'absent_today'          => ($todayTotal ?? 0) - ($todayPresent ?? 0),
        'late_today'            => 0,
    ];
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Students</p>
                <p class="text-3xl font-bold text-gray-800"><?= e($stats['total_students']) ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-2xl">👨‍🎓</div>
        </div>
        <p class="text-sm text-green-600 mt-2"><?= e($pendingAdmissions ?? 0) ?> pending admission(s)</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Teachers</p>
                <p class="text-3xl font-bold text-gray-800"><?= e($stats['total_teachers']) ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center text-2xl">👨‍🏫</div>
        </div>
        <p class="text-sm text-gray-500 mt-2"><?= e($stats['total_classes']) ?> classes</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Classes</p>
                <p class="text-3xl font-bold text-gray-800"><?= e($stats['total_classes']) ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center text-2xl">🏫</div>
        </div>
        <p class="text-sm text-gray-500 mt-2">Across <?= e($stats['total_sections']) ?> sections</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Fees Collected</p>
                <p class="text-3xl font-bold text-gray-800"><?= e(format_currency((float)$stats['fees_collected'])) ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-lg flex items-center justify-center text-2xl">💰</div>
        </div>
        <p class="text-sm text-gray-500 mt-2">Pending: <?= e(format_currency((float)$stats['fees_pending'])) ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold mb-4">Today's Attendance</h3>
        <div class="flex items-center justify-center py-8">
            <div class="text-center">
                <div class="text-4xl font-bold text-green-600"><?= e($stats['today_attendance_rate']) ?>%</div>
                <p class="text-gray-500 mt-2">Attendance Rate</p>
                <div class="flex justify-center gap-8 mt-4">
                    <div>
                        <span class="text-green-600 font-bold"><?= e($stats['present_today']) ?></span>
                        <p class="text-xs text-gray-500">Present</p>
                    </div>
                    <div>
                        <span class="text-red-600 font-bold"><?= e($stats['absent_today']) ?></span>
                        <p class="text-xs text-gray-500">Absent</p>
                    </div>
                    <div>
                        <span class="text-yellow-600 font-bold"><?= e($stats['late_today']) ?></span>
                        <p class="text-xs text-gray-500">Late</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold mb-4">Fee Collection Summary</h3>
        <div class="space-y-4">
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">Collected</span>
                    <span class="font-bold"><?= e(format_currency((float)$stats['fees_collected'])) ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: <?= e($stats['fees_collection_rate']) ?>%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">Pending</span>
                    <span class="font-bold"><?= e(format_currency((float)$stats['fees_pending'])) ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-yellow-500 h-2 rounded-full" style="width: <?= e(100 - $stats['fees_collection_rate']) ?>%"></div>
                </div>
            </div>
            <div class="text-center pt-4">
                <span class="text-sm text-gray-500">Total Expected: </span>
                <span class="font-bold"><?= e(format_currency((float)$stats['total_fees'])) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold mb-4">Recent Activity</h3>
        <div class="space-y-4">
            <?php if (!empty($recentActivity)): ?>
                <?php foreach ($recentActivity as $activity): ?>
                <div class="flex items-start">
                    <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm mr-3 flex-shrink-0">
                        📋
                    </div>
                    <div>
                        <p class="text-sm"><?= e($activity['description'] ?? '') ?></p>
                        <p class="text-xs text-gray-400"><?= e(time_ago($activity['created_at'] ?? 'now')) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php elseif (!empty($recentAdmissions)): ?>
                <?php foreach ($recentAdmissions as $adm): ?>
                <div class="flex items-start">
                    <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm mr-3 flex-shrink-0">
                        📄
                    </div>
                    <div>
                        <p class="text-sm">New admission: <?= e(($adm['first_name'] ?? '') . ' ' . ($adm['last_name'] ?? '')) ?></p>
                        <p class="text-xs text-gray-400"><?= e(time_ago($adm['submitted_at'] ?? $adm['created_at'] ?? 'now')) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">No recent activity</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold mb-4">Upcoming Events</h3>
        <div class="space-y-4">
            <?php if (!empty($upcomingEvents)): ?>
                <?php foreach ($upcomingEvents as $event): ?>
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <div class="bg-blue-100 text-blue-600 px-3 py-1 rounded text-sm font-bold mr-3">
                        <?= e(date('d M', strtotime($event['start_date'] ?? 'now'))) ?>
                    </div>
                    <div>
                        <p class="font-medium text-sm"><?= e($event['title']) ?></p>
                        <p class="text-xs text-gray-500"><?= e($event['time'] ?? '') ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">No upcoming events</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/dashboard.php'; ?>