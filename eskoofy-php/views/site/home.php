<?php $pageTitle = 'Home'; ?>
<?php ob_start(); ?>

<section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-6"><?= e(config('school.name', 'School Name')) ?></h1>
        <p class="text-xl text-blue-100 mb-8 max-w-3xl mx-auto"><?= e(config('school.motto', 'Empowering Minds, Shaping Futures')) ?></p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="/admission" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50 transition">Apply for Admission</a>
            <a href="/about" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition">Learn More</a>
        </div>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <div class="text-3xl font-bold text-blue-600 mb-2"><?= e($stats['total_students'] ?? '500+') ?></div>
                <div class="text-gray-600">Students</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <div class="text-3xl font-bold text-blue-600 mb-2"><?= e($stats['total_teachers'] ?? '30+') ?></div>
                <div class="text-gray-600">Teachers</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <div class="text-3xl font-bold text-blue-600 mb-2"><?= e($stats['total_classes'] ?? '15+') ?></div>
                <div class="text-gray-600">Classes</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <div class="text-3xl font-bold text-blue-600 mb-2"><?= e($stats['years'] ?? '20+') ?></div>
                <div class="text-gray-600">Years of Excellence</div>
            </div>
        </div>
    </div>
</section>

<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-12">Latest News</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <?php if (!empty($latestNews)): ?>
                <?php foreach ($latestNews as $news): ?>
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="h-48 bg-gray-200">
                        <?php if (!empty($news['image'])): ?>
                            <img src="/uploads/news/<?= e($news['image']) ?>" alt="<?= e($news['title']) ?>" class="w-full h-full object-cover">
                        <?php endif; ?>
                    </div>
                    <div class="p-6">
                        <span class="text-sm text-blue-600"><?= e(date('M d, Y', strtotime($news['created_at'] ?? 'now'))) ?></span>
                        <h3 class="text-lg font-bold mt-2 mb-2"><?= e($news['title']) ?></h3>
                        <p class="text-gray-600 text-sm"><?= e(truncate($news['content'] ?? '', 120)) ?></p>
                        <a href="/news/<?= e($news['slug'] ?? '') ?>" class="text-blue-600 text-sm font-semibold mt-3 inline-block hover:underline">Read More →</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-gray-500 col-span-3">No news available at the moment.</p>
            <?php endif; ?>
        </div>
        <div class="text-center mt-8">
            <a href="/news" class="text-blue-600 font-semibold hover:underline">View All News →</a>
        </div>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-12">Upcoming Events</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <?php if (!empty($upcomingEvents)): ?>
                <?php foreach ($upcomingEvents as $event): ?>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-100 text-blue-600 px-3 py-1 rounded-lg text-sm font-bold mr-3">
                            <?= e(date('d', strtotime($event['start_date'] ?? $event['date'] ?? 'now'))) ?>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500"><?= e(date('M Y', strtotime($event['start_date'] ?? $event['date'] ?? 'now'))) ?></div>
                            <div class="text-xs text-gray-400"><?= e($event['time'] ?? '') ?></div>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold mb-2"><?= e($event['title']) ?></h3>
                    <p class="text-gray-600 text-sm"><?= e(truncate($event['description'] ?? '', 100)) ?></p>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-gray-500 col-span-3">No upcoming events.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="py-16 bg-blue-600 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold mb-4">Ready to Join Us?</h2>
        <p class="text-blue-100 mb-8 max-w-2xl mx-auto">Start your educational journey with us. Our admission process is simple and straightforward.</p>
        <a href="/admission" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50 transition">Start Admission Process</a>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>