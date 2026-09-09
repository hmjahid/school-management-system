<?php $pageTitle = 'Search'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Search</h1>
        <form action="/search" method="GET" class="max-w-2xl mx-auto">
            <input type="text" name="q" value="<?= e($term ?? '') ?>" placeholder="Search news, notices, events..." class="w-full px-4 py-3 rounded-lg text-gray-800 focus:ring-2 focus:ring-blue-300">
        </form>
    </div>
</section>

<section class="py-16">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if ($term === ''): ?>
            <p class="text-center text-gray-500">Enter a search term above.</p>
        <?php else: ?>
            <?php $totalFound = count($results['news'] ?? []) + count($results['notices'] ?? []) + count($results['events'] ?? []) + count($results['pages'] ?? []); ?>
            <p class="text-gray-500 mb-6">Found <?= e($totalFound) ?> result(s) for "<strong><?= e($term) ?></strong>"</p>

            <?php if (!empty($results['news'])): ?>
                <h2 class="text-xl font-bold mb-3">📰 News</h2>
                <div class="space-y-3 mb-6">
                    <?php foreach ($results['news'] as $r): ?>
                    <a href="/news/<?= e($r['slug'] ?? '') ?>" class="block bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition">
                        <h3 class="font-bold"><?= e($r['title']) ?></h3>
                        <p class="text-xs text-gray-500"><?= e(date('M d, Y', strtotime($r['created_at'] ?? 'now'))) ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($results['notices'])): ?>
                <h2 class="text-xl font-bold mb-3">📢 Notices</h2>
                <div class="space-y-3 mb-6">
                    <?php foreach ($results['notices'] as $r): ?>
                    <div class="block bg-white p-4 rounded-lg shadow-sm">
                        <h3 class="font-bold"><?= e($r['title']) ?></h3>
                        <p class="text-xs text-gray-500"><?= e(truncate($r['content'] ?? '', 200)) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($results['events'])): ?>
                <h2 class="text-xl font-bold mb-3">🎉 Events</h2>
                <div class="space-y-3 mb-6">
                    <?php foreach ($results['events'] as $r): ?>
                    <div class="block bg-white p-4 rounded-lg shadow-sm">
                        <h3 class="font-bold"><?= e($r['title']) ?></h3>
                        <p class="text-xs text-gray-500"><?= e(date('M d, Y', strtotime($r['start_date'] ?? 'now'))) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($results['pages'])): ?>
                <h2 class="text-xl font-bold mb-3">📄 Pages</h2>
                <div class="space-y-3 mb-6">
                    <?php foreach ($results['pages'] as $r): ?>
                    <a href="/<?= e($r['slug']) ?>" class="block bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition">
                        <h3 class="font-bold"><?= e($r['title'] ?? $r['title_en'] ?? $r['slug']) ?></h3>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($totalFound === 0): ?>
                <p class="text-center text-gray-500">No results found. Try different keywords.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
