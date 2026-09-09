<?php $pageTitle = 'Reset Password'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?= e(config('school.name', 'School')) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-md">
        <h1 class="text-2xl font-bold text-center mb-2">Reset Password</h1>
        <p class="text-gray-500 text-center text-sm mb-6">Enter your new password below.</p>

        <?php include __DIR__ . '/../partials/flash_messages.php'; ?>

        <form action="/reset-password" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" name="password" required minlength="8" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" placeholder="At least 8 characters">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" required minlength="8" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" placeholder="Repeat password">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Reset Password</button>
        </form>
        <p class="text-center text-sm text-gray-500 mt-4">
            <a href="/login" class="text-blue-600 hover:underline">Back to Login</a>
        </p>
    </div>
</body>
</html>
