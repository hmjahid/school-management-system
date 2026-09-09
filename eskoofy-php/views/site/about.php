<?php $pageTitle = 'About Us'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">About Us</h1>
        <p class="text-blue-100 text-lg">Learn about our school's history, mission, and vision</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl font-bold mb-6">Our Story</h2>
                <p class="text-gray-600 mb-4">
                    <?= e($school->about ?? 'Founded with a vision to provide quality education, our school has been serving the community for over two decades. We believe in nurturing not just academic excellence but also character development and life skills.') ?>
                </p>
                <p class="text-gray-600">
                    <?= e($school->history ?? 'Our journey began with a small group of dedicated educators and has grown into a renowned institution known for its commitment to holistic education.') ?>
                </p>
            </div>
            <div class="bg-gray-200 rounded-xl h-80">
                <?php if ($school->image): ?>
                    <img src="/uploads/school/<?= e($school->image) ?>" alt="School" class="w-full h-full object-cover rounded-xl">
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 gap-12">
            <div class="bg-white p-8 rounded-xl shadow-sm">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-2xl mb-4">🎯</div>
                <h3 class="text-2xl font-bold mb-4">Our Mission</h3>
                <p class="text-gray-600"><?= e($school->mission ?? 'To provide quality education that empowers students to become responsible, creative, and compassionate global citizens. We strive to create a nurturing environment where every student can reach their full potential.') ?></p>
            </div>
            <div class="bg-white p-8 rounded-xl shadow-sm">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-2xl mb-4">👁️</div>
                <h3 class="text-2xl font-bold mb-4">Our Vision</h3>
                <p class="text-gray-600"><?= e($school->vision ?? 'To be a leading educational institution that shapes future leaders through innovative teaching methods, character building, and a commitment to excellence in all endeavors.') ?></p>
            </div>
        </div>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-12">Our Values</h2>
        <div class="grid md:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="text-4xl mb-4">📚</div>
                <h4 class="text-lg font-bold mb-2">Excellence</h4>
                <p class="text-gray-600 text-sm">Striving for the highest standards in everything we do.</p>
            </div>
            <div class="text-center">
                <div class="text-4xl mb-4">🤝</div>
                <h4 class="text-lg font-bold mb-2">Integrity</h4>
                <p class="text-gray-600 text-sm">Acting with honesty and strong moral principles.</p>
            </div>
            <div class="text-center">
                <div class="text-4xl mb-4">💡</div>
                <h4 class="text-lg font-bold mb-2">Innovation</h4>
                <p class="text-gray-600 text-sm">Embracing new ideas and creative approaches.</p>
            </div>
            <div class="text-center">
                <div class="text-4xl mb-4">🌍</div>
                <h4 class="text-lg font-bold mb-2">Community</h4>
                <p class="text-gray-600 text-sm">Building a supportive and inclusive environment.</p>
            </div>
        </div>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>