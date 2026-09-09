<?php $pageTitle = 'Privacy Policy'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Privacy Policy</h1>
    </div>
</section>

<section class="py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 bg-white rounded-xl shadow-sm p-8">
        <h2 class="text-2xl font-bold mb-4"><?= e($content['title'] ?? 'Privacy Policy') ?></h2>
        <div class="prose max-w-none">
            <?php if (is_array($content['content'] ?? null)): ?>
                <?php foreach (($content['content'] ?? []) as $block): ?>
                    <?php if (is_array($block)): ?>
                        <h3 class="text-lg font-bold mt-4 mb-2"><?= e($block['heading'] ?? '') ?></h3>
                        <p class="text-gray-700 mb-3"><?= nl2br(e($block['body'] ?? '')) ?></p>
                    <?php else: ?>
                        <p class="text-gray-700 mb-3"><?= nl2br(e((string)$block)) ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-700 mb-3"><?= nl2br(e((string)($content['content'] ?? ''))) ?></p>
            <?php endif; ?>
        </div>
        <p class="text-xs text-gray-400 mt-8">Last updated: <?= e(date('F d, Y')) ?></p>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
