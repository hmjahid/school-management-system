<?php $title = 'Log in'; $siteTitle = $title; ?>

<section class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white rounded-2xl border border-slate-200 p-8">
        <h1 class="text-2xl font-bold mb-1">Welcome back</h1>
        <p class="text-sm text-slate-500 mb-6">Log in to manage your licenses.</p>

        <form method="post" action="/login<?= isset($_GET['redirect']) ? '?redirect=' . urlencode((string) $_GET['redirect']) : '' ?>" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-semibold mb-1">Email</label>
                <input type="email" name="email" required maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Password</label>
                <input type="password" name="password" required maxlength="72" class="w-full border border-slate-300 rounded-lg px-4 py-2">
            </div>
            <button type="submit" class="w-full bg-slate-900 hover:bg-blue-600 text-white py-3 rounded-lg font-semibold">Log In</button>
        </form>

        <p class="text-sm text-slate-500 mt-6 text-center">No account yet? <a href="/register" class="text-blue-600 font-semibold">Create one</a></p>
    </div>
</section>