<?php $pageTitle = 'Events'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Events</h1>
    <a href="/dashboard/events/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Create Event</a>
</div>

<div class="space-y-4">
    <?php if (!empty($events)): ?>
        <?php foreach ($events as $event): ?>
        <div class="bg-white rounded-xl shadow-sm p-6 flex items-center">
            <div class="bg-blue-100 text-blue-600 px-4 py-2 rounded-lg text-center mr-6 min-w-[80px]">
                <div class="text-2xl font-bold"><?= e($event->date->format('d')) ?></div>
                <div class="text-xs"><?= e($event->date->format('M')) ?></div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold"><?= e($event->title) ?></h3>
                <p class="text-gray-600 text-sm"><?= e(Str::limit($event->description ?? '', 120)) ?></p>
                <?php if ($event->location ?? null): ?>
                    <p class="text-sm text-gray-500 mt-1">📍 <?= e($event->location) ?></p>
                <?php endif; ?>
            </div>
            <div class="flex space-x-2 ml-4">
                <a href="/dashboard/events/<?= e($event->id) ?>/edit" class="text-green-600 hover:underline text-sm">Edit</a>
                <form action="/dashboard/events/<?= e($event->id) ?>" method="POST" onsubmit="return confirm('Delete this event?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">No events found.</div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>