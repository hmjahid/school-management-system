<?php $pageTitle = $row['title'] ?? 'News Article'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <a href="/news" class="text-blue-200 hover:text-white text-sm mb-4 inline-block">← Back to News</a>
        <h1 class="text-3xl font-bold mb-4"><?= e($row['title'] ?? '') ?></h1>
        <div class="flex items-center justify-center text-sm text-blue-200 space-x-4">
            <span><?= e(date('F d, Y', strtotime($row['created_at'] ?? 'now'))) ?></span>
            <?php if (!empty($row['category'])): ?>
                <span>•</span>
                <span><?= e($row['category']) ?></span>
            <?php endif; ?>
            <?php if (!empty($row['author'])): ?>
                <span>•</span>
                <span>By <?= e($row['author']) ?></span>
            <?php endif; ?>
        </div>
    </div>
</section>

<article class="py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (!empty($row['image'])): ?>
        <div class="mb-8 rounded-xl overflow-hidden">
            <img src="/uploads/news/<?= e($row['image']) ?>" alt="<?= e($row['title']) ?>" class="w-full h-auto">
        </div>
        <?php endif; ?>

        <div class="prose prose-lg max-w-none">
            <?= nl2br(e($row['content'] ?? '')) ?>
        </div>
    </div>
</article>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>