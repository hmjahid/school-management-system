<?php $pageTitle = 'Notices'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Notices</h1>
        <p class="text-blue-100 text-lg">Important notices and announcements</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (!empty($notices)): ?>
            <?php foreach ($notices as $notice): ?>
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6 <?= !empty($notice['pinned']) ? 'border-l-4 border-yellow-500' : '' ?>">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <?php if (!empty($notice['pinned'])): ?>
                                <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded mr-2">📌 Pinned</span>
                            <?php endif; ?>
                            <span class="text-sm text-gray-500"><?= e(date('M d, Y', strtotime($notice['created_at'] ?? 'now'))) ?></span>
                        </div>
                        <h2 class="text-xl font-bold mb-2"><?= e($notice['title']) ?></h2>
                        <p class="text-gray-600"><?= nl2br(e($notice['content'] ?? '')) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-12">
                <p class="text-gray-500 text-lg">No notices available at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>