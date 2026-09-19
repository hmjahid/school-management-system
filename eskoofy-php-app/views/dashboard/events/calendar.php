<?php $pageTitle = 'School Calendar'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">School Calendar — <?= e($anchor) ?></h1>
    <div class="flex gap-2">
        <a href="/dashboard/events/calendar?month=<?= e(date('Y-m', strtotime($month . '-01 -1 month'))) ?>" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">&larr; Prev</a>
        <a href="/dashboard/events/calendar?month=<?= date('Y-m') ?>" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Today</a>
        <a href="/dashboard/events/calendar?month=<?= e(date('Y-m', strtotime($month . '-01 +1 month'))) ?>" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Next &rarr;</a>
        <a href="/dashboard/events" class="text-gray-600 hover:text-gray-800 px-4 py-2">&larr; Back</a>
    </div>
</div>

<div class="flex gap-4 flex-wrap text-xs mb-4">
    <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 bg-blue-500 rounded"></span> Events</span>
    <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 bg-red-500 rounded"></span> Government holidays</span>
    <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 bg-green-500 rounded"></span> Academic</span>
    <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 bg-orange-500 rounded"></span> School activities</span>
</div>

<div class="bg-white rounded-xl shadow-sm p-4">
    <div class="grid grid-cols-7 gap-1 mb-1">
        <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $d): ?>
        <div class="text-center text-xs font-semibold text-gray-500 py-1"><?= $d ?></div>
        <?php endforeach; ?>
    </div>
    <div class="grid grid-cols-7 gap-1">
        <?php
        $cursor = strtotime($start);
        $endTs = strtotime($end);
        $anchorTs = strtotime($month . '-01');
        while ($cursor <= $endTs):
            $inMonth = date('m', $cursor) === date('m', $anchorTs);
            $isToday = date('Y-m-d', $cursor) === date('Y-m-d');
            $key = date('Y-m-d', $cursor);
            $items = $byDay[$key] ?? [];
        ?>
        <div class="min-h-[90px] border rounded-lg p-1 <?= $inMonth ? 'bg-white' : 'bg-gray-50' ?>">
            <div class="flex justify-between items-center">
                <span class="text-xs font-semibold <?= $isToday ? 'inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white' : 'text-gray-500' ?>"><?= date('j', $cursor) ?></span>
            </div>
            <div class="space-y-1 mt-1">
                <?php foreach ($items as $item): ?>
                    <?php if ($item['type'] === 'event'): ?>
                    <a href="/dashboard/events/<?= e($item['id']) ?>/edit" class="block bg-blue-100 text-blue-800 text-[10px] rounded px-1 py-0.5 truncate hover:bg-blue-200"><?= e($item['title']) ?></a>
                    <?php elseif ($item['type'] === 'holiday'): ?>
                    <div class="bg-red-100 text-red-800 text-[10px] rounded px-1 py-0.5 truncate">🏖️ <?= e($item['title']) ?></div>
                    <?php elseif ($item['type'] === 'academic'): ?>
                    <div class="bg-green-100 text-green-800 text-[10px] rounded px-1 py-0.5 truncate">📝 <?= e($item['title']) ?></div>
                    <?php else: ?>
                    <div class="bg-orange-100 text-orange-800 text-[10px] rounded px-1 py-0.5 truncate">🏫 <?= e($item['title']) ?></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php $cursor = strtotime('+1 day', $cursor); endwhile; ?>
    </div>
</div>

<?php if (!empty($upcomingHolidays) || !empty($upcomingEvents)): ?>
<div class="grid md:grid-cols-2 gap-6 mt-6">
    <?php if (!empty($upcomingHolidays)): ?>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold mb-3">Upcoming Holidays</h3>
        <div class="space-y-2">
            <?php foreach ($upcomingHolidays as $h): ?>
            <div class="flex justify-between items-center border-b pb-2">
                <span class="text-sm"><?= e($h['name']) ?></span>
                <span class="text-xs text-gray-500"><?= e(date('M d', strtotime($h['date']))) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($upcomingEvents)): ?>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold mb-3">Upcoming Events</h3>
        <div class="space-y-2">
            <?php foreach ($upcomingEvents as $ev): ?>
            <div class="flex justify-between items-center border-b pb-2">
                <a href="/dashboard/events/<?= e($ev['id']) ?>/edit" class="text-sm text-blue-600 hover:underline"><?= e($ev['title']) ?></a>
                <span class="text-xs text-gray-500"><?= e(date('M d', strtotime($ev['start_date']))) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>