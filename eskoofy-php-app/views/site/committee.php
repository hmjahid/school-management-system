<?php $pageTitle = 'Committee'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Governing Committee</h1>
        <p class="text-blue-100 text-lg">Our distinguished members guiding the school</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-4 gap-6">
            <?php if (!empty($members)): ?>
                <?php foreach ($members as $member): ?>
                <div class="bg-white rounded-xl shadow-sm p-6 text-center">
                    <div class="w-24 h-24 bg-blue-100 text-blue-600 rounded-full mx-auto mb-4 flex items-center justify-center text-2xl font-bold">
                        <?php if (!empty($member['photo'])): ?>
                            <img src="/uploads/committee/<?= e($member['photo']) ?>" alt="<?= e($member['name']) ?>" class="w-full h-full object-cover rounded-full">
                        <?php else: ?>
                            <?= strtoupper(substr($member['name'] ?? '', 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <h3 class="font-bold"><?= e($member['name']) ?></h3>
                    <p class="text-sm text-blue-600"><?= e($member['designation']) ?></p>
                    <?php if (!empty($member['bio'])): ?>
                    <p class="text-xs text-gray-500 mt-2"><?= e(truncate($member['bio'], 80)) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="col-span-4 text-center text-gray-500">No committee members listed yet.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
