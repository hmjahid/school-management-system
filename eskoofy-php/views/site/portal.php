<?php $pageTitle = 'Student & Parent Portal'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Student &amp; Parent Portal</h1>
        <p class="text-blue-100 text-lg">Access results, attendance, fees and more</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-white rounded-xl shadow-sm p-8">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-2xl mb-4">🎓</div>
                <h2 class="text-2xl font-bold mb-2">Student Login</h2>
                <p class="text-gray-600 text-sm mb-4">Sign in to view your class routine, results, assignments and attendance.</p>
                <a href="/student/login" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Student Login</a>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-8">
                <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center text-2xl mb-4">👨‍👩‍👧</div>
                <h2 class="text-2xl font-bold mb-2">Parent Login</h2>
                <p class="text-gray-600 text-sm mb-4">Track your child's progress, pay fees online, and receive school updates.</p>
                <a href="/guardian/login" class="inline-block bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">Parent Login</a>
            </div>
        </div>

        <div class="mt-12 grid md:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="text-3xl mb-2">📊</div>
                <h4 class="font-bold">Results</h4>
                <p class="text-xs text-gray-500">Live exam results</p>
            </div>
            <div class="text-center">
                <div class="text-3xl mb-2">📅</div>
                <h4 class="font-bold">Attendance</h4>
                <p class="text-xs text-gray-500">Daily attendance</p>
            </div>
            <div class="text-center">
                <div class="text-3xl mb-2">💰</div>
                <h4 class="font-bold">Fees</h4>
                <p class="text-xs text-gray-500">Online fee payment</p>
            </div>
            <div class="text-center">
                <div class="text-3xl mb-2">📚</div>
                <h4 class="font-bold">Homework</h4>
                <p class="text-xs text-gray-500">Assignments &amp; notes</p>
            </div>
        </div>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
