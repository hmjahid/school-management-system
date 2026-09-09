<?php $pageTitle = 'Login'; ?>
<?php ob_start(); ?>

<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <a href="/" class="text-2xl font-bold text-blue-600"><?= e(config('school.name', 'School')) ?></a>
            <h2 class="mt-4 text-3xl font-bold text-gray-900">Sign In</h2>
            <p class="mt-2 text-gray-600">Enter your credentials to access your account</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-8">
            <?php include __DIR__ . '/../partials/flash_messages.php'; ?>

            <form action="/login" method="POST" class="space-y-5">
                <?= csrf_field() ?>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" value="<?= old('email') ?>" required autofocus
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="you@example.com">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="••••••••">
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>
                    <a href="/password/reset" class="text-sm text-blue-600 hover:underline">Forgot password?</a>
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">Sign In</button>
            </form>
        </div>

        <p class="text-center mt-6 text-gray-600">
            Don't have an account? <a href="/register" class="text-blue-600 font-semibold hover:underline">Sign Up</a>
        </p>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>