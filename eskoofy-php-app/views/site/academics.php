<?php $pageTitle = 'Academics'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Academics</h1>
        <p class="text-blue-100 text-lg">Academic programs, curriculum & teaching methodology</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-2xl mb-4">📘</div>
                <h3 class="text-xl font-bold mb-2">Curriculum</h3>
                <p class="text-gray-600 text-sm">Our curriculum follows the national education framework with emphasis on STEM, languages, arts, and physical education.</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-2xl mb-4">🎯</div>
                <h3 class="text-xl font-bold mb-2">Methodology</h3>
                <p class="text-gray-600 text-sm">Interactive, project-based learning with continuous assessment. We blend traditional teaching with modern digital tools.</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-2xl mb-4">🏆</div>
                <h3 class="text-xl font-bold mb-2">Assessment</h3>
                <p class="text-gray-600 text-sm">Formative and summative assessments throughout the year with transparent grade reporting to parents.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-12">Classes Offered</h2>
        <div class="grid md:grid-cols-4 gap-6">
            <?php if (!empty($classes)): ?>
                <?php foreach ($classes as $class): ?>
                <div class="bg-white p-6 rounded-xl shadow-sm text-center">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full mx-auto mb-3 flex items-center justify-center text-lg font-bold"><?= e($class['name']) ?></div>
                    <p class="text-gray-500 text-sm"><?= e((int)($class['student_count'] ?? 0)) ?> students</p>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="col-span-4 text-center text-gray-500">No classes configured yet.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-12">Subjects</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php if (!empty($subjects)): ?>
                <?php foreach ($subjects as $subject): ?>
                <div class="bg-gray-50 p-4 rounded-lg text-center text-sm font-medium text-gray-700"><?= e($subject['name']) ?></div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="col-span-4 text-center text-gray-500">No subjects configured yet.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
