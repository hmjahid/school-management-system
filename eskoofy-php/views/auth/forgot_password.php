<?php $pageTitle = 'Forgot Password'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?= e(config('school.name', 'School')) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-md">
        <h1 class="text-2xl font-bold text-center mb-2">Forgot Password</h1>
        <p class="text-gray-500 text-center text-sm mb-6">Enter your email address and we'll send you a password reset link.</p>

        <?php include __DIR__ . '/../partials/flash_messages.php'; ?>

        <form action="/forgot-password" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" name="email" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" placeholder="you@example.com">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Send Reset Link</button>
        </form>
        <p class="text-center text-sm text-gray-500 mt-4">
            <a href="/login" class="text-blue-600 hover:underline">Back to Login</a>
        </p>
    </div>
</body>
</html>
