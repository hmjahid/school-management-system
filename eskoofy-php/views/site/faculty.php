<?php $pageTitle = 'Faculty'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Our Faculty</h1>
        <p class="text-blue-100 text-lg">Meet our dedicated teachers and subject experts</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-3 gap-8">
            <?php if (!empty($teachers)): ?>
                <?php foreach ($teachers as $teacher): ?>
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="h-56 bg-gray-200">
                        <?php if (!empty($teacher['photo'])): ?>
                            <img src="/uploads/users/<?= e($teacher['photo']) ?>" alt="<?= e($teacher['name']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-5xl text-gray-400 font-bold"><?= strtoupper(substr($teacher['name'] ?? '', 0, 1)) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="p-6">
                        <h3 class="text-lg font-bold"><?= e($teacher['name'] ?? '') ?></h3>
                        <p class="text-sm text-blue-600 mb-2"><?= e($teacher['qualification'] ?? '') ?></p>
                        <?php if (!empty($teacher['subjects'])): ?>
                        <p class="text-xs text-gray-500 mb-2">Subjects: <?= e($teacher['subjects']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($teacher['email'])): ?>
                        <p class="text-xs text-gray-500">📧 <?= e($teacher['email']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="col-span-3 text-center text-gray-500">No faculty members listed.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
