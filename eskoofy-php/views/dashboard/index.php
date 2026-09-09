<?php $pageTitle = 'Dashboard'; ?>
<?php ob_start(); ?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Welcome back, <?= e(auth()->user()->name ?? 'Admin') ?>!</h1>
    <p class="text-gray-500">Here's what's happening at your school today.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Students</p>
                <p class="text-3xl font-bold text-gray-800"><?= e($stats['total_students'] ?? 0) ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-2xl">👨‍🎓</div>
        </div>
        <p class="text-sm text-green-600 mt-2">↑ <?= e($stats['students_growth'] ?? '0') ?>% from last month</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Teachers</p>
                <p class="text-3xl font-bold text-gray-800"><?= e($stats['total_teachers'] ?? 0) ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center text-2xl">👨‍🏫</div>
        </div>
        <p class="text-sm text-green-600 mt-2">↑ <?= e($stats['teachers_growth'] ?? '0') ?>% from last month</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Classes</p>
                <p class="text-3xl font-bold text-gray-800"><?= e($stats['total_classes'] ?? 0) ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center text-2xl">🏫</div>
        </div>
        <p class="text-sm text-gray-500 mt-2">Across <?= e($stats['total_sections'] ?? 0) ?> sections</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Fees Collected</p>
                <p class="text-3xl font-bold text-gray-800"><?= e(config('currency.symbol', '$')) ?><?= number_format($stats['fees_collected'] ?? 0, 2) ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-lg flex items-center justify-center text-2xl">💰</div>
        </div>
        <p class="text-sm text-green-600 mt-2">↑ <?= e($stats['fees_growth'] ?? '0') ?>% from last month</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold mb-4">Today's Attendance</h3>
        <div class="flex items-center justify-center py-8">
            <div class="text-center">
                <div class="text-4xl font-bold text-green-600"><?= e($stats['today_attendance_rate'] ?? 0) ?>%</div>
                <p class="text-gray-500 mt-2">Attendance Rate</p>
                <div class="flex justify-center gap-8 mt-4">
                    <div>
                        <span class="text-green-600 font-bold"><?= e($stats['present_today'] ?? 0) ?></span>
                        <p class="text-xs text-gray-500">Present</p>
                    </div>
                    <div>
                        <span class="text-red-600 font-bold"><?= e($stats['absent_today'] ?? 0) ?></span>
                        <p class="text-xs text-gray-500">Absent</p>
                    </div>
                    <div>
                        <span class="text-yellow-600 font-bold"><?= e($stats['late_today'] ?? 0) ?></span>
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
                    <span class="font-bold"><?= e(config('currency.symbol', '$')) ?><?= number_format($stats['fees_collected'] ?? 0, 2) ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: <?= e($stats['fees_collection_rate'] ?? 0) ?>%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">Pending</span>
                    <span class="font-bold"><?= e(config('currency.symbol', '$')) ?><?= number_format($stats['fees_pending'] ?? 0, 2) ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-yellow-500 h-2 rounded-full" style="width: <?= e(100 - ($stats['fees_collection_rate'] ?? 0)) ?>%"></div>
                </div>
            </div>
            <div class="text-center pt-4">
                <span class="text-sm text-gray-500">Total Expected: </span>
                <span class="font-bold"><?= e(config('currency.symbol', '$')) ?><?= number_format($stats['total_fees'] ?? 0, 2) ?></span>
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
                        <?= e($activity->icon ?? '📋') ?>
                    </div>
                    <div>
                        <p class="text-sm"><?= e($activity->description) ?></p>
                        <p class="text-xs text-gray-400"><?= e($activity->created_at->diffForHumans()) ?></p>
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
                        <?= e($event->date->format('d M')) ?>
                    </div>
                    <div>
                        <p class="font-medium text-sm"><?= e($event->title) ?></p>
                        <p class="text-xs text-gray-500"><?= e($event->time ?? '') ?></p>
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