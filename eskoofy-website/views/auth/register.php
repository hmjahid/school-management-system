<?php $title = 'Create account'; $siteTitle = $title; ?>

<section class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white rounded-2xl border border-slate-200 p-8">
        <h1 class="text-2xl font-bold mb-1">Create your account</h1>
        <p class="text-sm text-slate-500 mb-6">One account for purchases, licenses and renewals.</p>

        <form method="post" action="/register" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-semibold mb-1">Full name</label>
                <input name="name" required maxlength="120" class="w-full border border-slate-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Email</label>
                <input type="email" name="email" required maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Password</label>
                <input type="password" name="password" required minlength="8" maxlength="72" class="w-full border border-slate-300 rounded-lg px-4 py-2">
                <p class="text-xs text-slate-400 mt-1">At least 8 characters.</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">Company (optional)</label>
                    <input name="company" maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Country (optional)</label>
                    <input name="country" maxlength="120" class="w-full border border-slate-300 rounded-lg px-4 py-2">
                </div>
            </div>
            <button type="submit" class="w-full bg-slate-900 hover:bg-blue-600 text-white py-3 rounded-lg font-semibold">Register</button>
        </form>

        <p class="text-sm text-slate-500 mt-6 text-center">Already registered? <a href="/login" class="text-blue-600 font-semibold">Log in</a></p>
    </div>
</section>