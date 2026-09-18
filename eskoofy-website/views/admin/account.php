<?php $adminTitle = 'Your account'; ?>

<div class="grid lg:grid-cols-2 gap-6 items-start">
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-bold text-lg mb-1">Profile</h2>
        <p class="text-sm text-slate-500 mb-5">Your admin name and login email.</p>
        <form method="post" action="/admin/account/profile" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="text-sm font-medium text-slate-700 block mb-1">Full name</label>
                <input type="text" name="name" value="<?= old('name', htmlspecialchars((string) ($admin['name'] ?? ''), ENT_QUOTES)) ?>" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700 block mb-1">Email address</label>
                <input type="email" name="email" value="<?= old('email', htmlspecialchars((string) ($admin['email'] ?? ''), ENT_QUOTES)) ?>" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
            </div>
            <div class="flex justify-end pt-1">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2.5 rounded-xl text-sm font-semibold">Save profile</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-bold text-lg mb-1">Change password</h2>
        <p class="text-sm text-slate-500 mb-5">Use at least 8 characters.</p>
        <form method="post" action="/admin/account/password" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="text-sm font-medium text-slate-700 block mb-1">Current password</label>
                <input type="password" name="current_password" autocomplete="current-password" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700 block mb-1">New password</label>
                <input type="password" name="new_password" autocomplete="new-password" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700 block mb-1">Confirm new password</label>
                <input type="password" name="new_password_confirmation" autocomplete="new-password" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
            </div>
            <div class="flex justify-end pt-1">
                <button type="submit" class="bg-slate-900 hover:bg-blue-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold">Update password</button>
            </div>
        </form>
    </div>
</div>

<div class="mt-6 bg-slate-50 border border-slate-200 rounded-xl p-5 text-sm text-slate-600">
    Membership admin <span class="font-mono text-xs bg-slate-100 rounded px-1.5 py-0.5"><?= htmlspecialchars((string) ($admin['email'] ?? '')) ?></span>
    since <?= htmlspecialchars(substr((string) ($admin['created_at'] ?? '—'), 0, 10)) ?>.
</div>