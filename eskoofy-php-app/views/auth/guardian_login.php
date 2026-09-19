<?php $pageTitle = 'Parent Login'; ?>
<?php ob_start(); ?>

<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <a href="/" class="text-2xl font-bold text-green-600"><?= e(config('school.name', 'School')) ?></a>
            <h2 class="mt-4 text-3xl font-bold text-gray-900">Parent Login</h2>
            <p class="mt-2 text-gray-600">Sign in to track your child's progress</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-8">
            <?php include __DIR__ . '/../partials/flash_messages.php'; ?>

            <form action="/guardian/login" method="POST" class="space-y-5">
                <?= csrf_field() ?>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" value="<?= old('email') ?>" required autofocus
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                        placeholder="parent@example.com">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                        placeholder="••••••••">
                </div>

                <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 transition">Sign In</button>
            </form>
        </div>

        <p class="text-center mt-6 text-gray-600">
            <a href="/portal" class="text-green-600 hover:underline">← Back to Portal</a>
        </p>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
