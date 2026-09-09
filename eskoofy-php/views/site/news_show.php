<?php $pageTitle = $article->title ?? 'News Article'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <a href="/news" class="text-blue-200 hover:text-white text-sm mb-4 inline-block">← Back to News</a>
        <h1 class="text-3xl font-bold mb-4"><?= e($article->title ?? '') ?></h1>
        <div class="flex items-center justify-center text-sm text-blue-200 space-x-4">
            <span><?= e($article->created_at->format('F d, Y')) ?></span>
            <?php if ($article->category ?? null): ?>
                <span>•</span>
                <span><?= e($article->category) ?></span>
            <?php endif; ?>
            <?php if ($article->author ?? null): ?>
                <span>•</span>
                <span>By <?= e($article->author) ?></span>
            <?php endif; ?>
        </div>
    </div>
</section>

<article class="py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if ($article->image ?? null): ?>
        <div class="mb-8 rounded-xl overflow-hidden">
            <img src="/uploads/news/<?= e($article->image) ?>" alt="<?= e($article->title) ?>" class="w-full h-auto">
        </div>
        <?php endif; ?>

        <div class="prose prose-lg max-w-none">
            <?= nl2br(e($article->content ?? '')) ?>
        </div>

        <?php if ($article->attachments ?? null): ?>
        <div class="mt-8 border-t pt-8">
            <h3 class="text-lg font-bold mb-4">Attachments</h3>
            <div class="space-y-2">
                <?php foreach ($article->attachments as $attachment): ?>
                <a href="/uploads/news/attachments/<?= e($attachment) ?>" class="flex items-center text-blue-600 hover:underline" download>
                    📎 <?= e($attachment) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</article>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>