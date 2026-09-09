<?php $pageTitle = 'News'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">News & Updates</h1>
        <p class="text-blue-100 text-lg">Stay informed with the latest from our school</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-3 gap-8">
            <?php if (!empty($news)): ?>
                <?php foreach ($news as $article): ?>
                <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition">
                    <div class="h-48 bg-gray-200">
                        <?php if ($article->image): ?>
                            <img src="/uploads/news/<?= e($article->image) ?>" alt="<?= e($article->title) ?>" class="w-full h-full object-cover">
                        <?php endif; ?>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center text-sm text-gray-500 mb-2">
                            <span><?= e($article->created_at->format('M d, Y')) ?></span>
                            <?php if ($article->category): ?>
                                <span class="mx-2">•</span>
                                <span class="text-blue-600"><?= e($article->category) ?></span>
                            <?php endif; ?>
                        </div>
                        <h2 class="text-xl font-bold mb-2"><?= e($article->title) ?></h2>
                        <p class="text-gray-600 text-sm mb-4"><?= e(Str::limit($article->content, 150)) ?></p>
                        <a href="/news/<?= e($article->slug) ?>" class="text-blue-600 font-semibold hover:underline">Read More →</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-3 text-center py-12">
                    <p class="text-gray-500 text-lg">No news articles available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if (isset($paginator) && $paginator->hasPages()): ?>
            <?php include __DIR__ . '/../partials/pagination.php'; ?>
        <?php endif; ?>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>