<?php $pageTitle = 'Student Life'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Student Life</h1>
        <p class="text-blue-100 text-lg">Clubs, activities and achievements beyond the classroom</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-2xl mb-4">🎨</div>
                <h3 class="text-xl font-bold mb-2">Arts & Culture</h3>
                <p class="text-gray-600 text-sm">Annual art exhibitions, music recitals and drama productions give every student a stage to shine.</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-2xl mb-4">⚽</div>
                <h3 class="text-xl font-bold mb-2">Sports</h3>
                <p class="text-gray-600 text-sm">Cricket, football, basketball, athletics and swimming — we encourage fitness and team spirit.</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-2xl mb-4">🤖</div>
                <h3 class="text-xl font-bold mb-2">STEM & Robotics</h3>
                <p class="text-gray-600 text-sm">Hands-on robotics, programming, and science fair participation foster innovation.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-12">Featured Students</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <?php if (!empty($recentStudents)): ?>
                <?php foreach ($recentStudents as $student): ?>
                <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                    <div class="w-20 h-20 bg-blue-100 text-blue-600 rounded-full mx-auto mb-3 flex items-center justify-center text-2xl font-bold">
                        <?= strtoupper(substr($student['name'] ?? '', 0, 1)) ?>
                    </div>
                    <h4 class="font-bold text-sm"><?= e($student['name'] ?? '') ?></h4>
                    <p class="text-xs text-gray-500"><?= e($student['class_name'] ?? '') ?></p>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="col-span-4 text-center text-gray-500">No student highlights available.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
