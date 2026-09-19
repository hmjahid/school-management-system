<?php $pageTitle = 'Careers'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Career Opportunities</h1>
        <p class="text-blue-100 text-lg">Join our team and make a difference</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (!empty($jobs)): ?>
            <div class="space-y-6">
                <?php foreach ($jobs as $job): ?>
                <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-xl font-bold mb-2"><?= e($job['title']) ?></h2>
                            <div class="flex flex-wrap gap-2 mb-3">
                                <span class="bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded-full"><?= e($job['location'] ?? 'General') ?></span>
                                <span class="bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full"><?= e($job['type'] ?? 'Full-time') ?></span>
                                <?php if (!empty($job['salary_min']) || !empty($job['salary_max'])): ?>
                                    <span class="bg-gray-100 text-gray-700 text-xs px-3 py-1 rounded-full">💰 <?= e(format_currency((float)($job['salary_min'] ?? 0))) ?> - <?= e(format_currency((float)($job['salary_max'] ?? 0))) ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-gray-600 text-sm mb-4"><?= e(truncate($job['description'] ?? '', 200)) ?></p>
                            <div class="flex items-center text-sm text-gray-500 space-x-4">
                                <?php if (!empty($job['deadline'])): ?>
                                <span>📅 Deadline: <?= e(date('M d, Y', strtotime($job['deadline']))) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($job['location'])): ?>
                                    <span>📍 <?= e($job['location']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="/careers/<?= e($job['id']) ?>" class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition whitespace-nowrap">Apply Now</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12 bg-white rounded-xl shadow-sm">
                <p class="text-gray-500 text-lg">No open positions at the moment. Check back later!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>