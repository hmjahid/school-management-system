<?php $pageTitle = 'Events'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Events</h1>
        <p class="text-blue-100 text-lg">Upcoming and past school events</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (!empty($events)): ?>
            <div class="space-y-6">
                <?php foreach ($events as $event): ?>
                <div class="bg-white rounded-xl shadow-sm p-6 flex flex-col md:flex-row gap-6">
                    <div class="flex-shrink-0 text-center bg-blue-50 rounded-lg p-4 min-w-[100px]">
                        <div class="text-3xl font-bold text-blue-600"><?= e($event->date->format('d')) ?></div>
                        <div class="text-sm text-gray-500"><?= e($event->date->format('M Y')) ?></div>
                        <div class="text-xs text-gray-400 mt-1"><?= e($event->time ?? '') ?></div>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-bold mb-2"><?= e($event->title) ?></h2>
                        <p class="text-gray-600 mb-3"><?= e($event->description) ?></p>
                        <?php if ($event->location ?? null): ?>
                            <p class="text-sm text-gray-500">📍 <?= e($event->location) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($event->image ?? null): ?>
                    <div class="flex-shrink-0 w-32 h-32 bg-gray-200 rounded-lg overflow-hidden">
                        <img src="/uploads/events/<?= e($event->image) ?>" alt="<?= e($event->title) ?>" class="w-full h-full object-cover">
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <p class="text-gray-500 text-lg">No events scheduled at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>