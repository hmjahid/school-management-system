<?php $pageTitle = 'Student Login'; ?>
<?php ob_start(); ?>

<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <a href="/" class="text-2xl font-bold text-blue-600"><?= e(config('school.name', 'School')) ?></a>
            <h2 class="mt-4 text-3xl font-bold text-gray-900">Student Login</h2>
            <p class="mt-2 text-gray-600">Use your admission number to sign in</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-8">
            <?php include __DIR__ . '/../partials/flash_messages.php'; ?>

            <form action="/student/login" method="POST" class="space-y-5">
                <?= csrf_field() ?>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Admission Number</label>
                    <input type="text" name="admission_number" value="<?= old('admission_number') ?>" required autofocus
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="STU-20240101-0001">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Your date of birth (YYYY-MM-DD) or password">
                    <p class="text-xs text-gray-500 mt-1">Default password is your date of birth (YYYY-MM-DD)</p>
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">Sign In</button>
            </form>
        </div>

        <p class="text-center mt-6 text-gray-600">
            <a href="/portal" class="text-blue-600 hover:underline">← Back to Portal</a>
        </p>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
