<?php $title = 'Account settings'; $siteTitle = $title; ?>

<?php \App\Core\View::partial('account.partials.account_header', ['accountPage' => $accountPage ?? 'settings', 'customer' => $customer]); ?>

<section class="max-w-6xl mx-auto px-4 pb-12">
    <div class="grid lg:grid-cols-5 gap-6 items-start">

        <div class="lg:col-span-3 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h2 class="font-bold text-lg mb-1">Profile</h2>
                <p class="text-sm text-slate-500 mb-5">Your public details and contact information.</p>
                <form method="post" action="/account/settings/profile" class="grid md:grid-cols-2 gap-4">
                    <?= csrf_field() ?>
                    <div>
                        <label class="text-sm font-medium text-slate-700 block mb-1">Full name</label>
                        <input type="text" name="name" value="<?= old('name', htmlspecialchars((string) ($customer['name'] ?? ''), ENT_QUOTES)) ?>" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700 block mb-1">Email address</label>
                        <input type="email" name="email" value="<?= old('email', htmlspecialchars((string) ($customer['email'] ?? ''), ENT_QUOTES)) ?>" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700 block mb-1">Company / School</label>
                        <input type="text" name="company" value="<?= old('company', htmlspecialchars((string) ($customer['company'] ?? ''), ENT_QUOTES)) ?>" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700 block mb-1">Country</label>
                        <input type="text" name="country" value="<?= old('country', htmlspecialchars((string) ($customer['country'] ?? ''), ENT_QUOTES)) ?>" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" placeholder="e.g. Bangladesh">
                    </div>
                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2.5 rounded-xl text-sm font-semibold">Save profile</button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h2 class="font-bold text-lg mb-1">Change password</h2>
                <p class="text-sm text-slate-500 mb-5">Use at least 8 characters. You'll stay signed in on this device.</p>
                <form method="post" action="/account/settings/password" class="grid md:grid-cols-2 gap-4">
                    <?= csrf_field() ?>
                    <div class="md:col-span-2">
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
                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit" class="bg-slate-900 hover:bg-blue-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold">Update password</button>
                    </div>
                </form>
            </div>
        </div>

        <aside class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h2 class="font-bold text-lg mb-4">Language</h2>
                <form method="post" action="/account/settings/preferences">
                    <?= csrf_field() ?>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 border border-slate-200 rounded-xl px-4 py-3 cursor-pointer hover:border-blue-400">
                            <input type="radio" name="locale" value="en" class="accent-blue-600" <?= ($customer['locale'] ?? 'en') === 'en' ? 'checked' : '' ?>>
                            <span class="text-sm font-medium">English</span>
                        </label>
                        <label class="flex items-center gap-3 border border-slate-200 rounded-xl px-4 py-3 cursor-pointer hover:border-blue-400">
                            <input type="radio" name="locale" value="bn" class="accent-blue-600" <?= ($customer['locale'] ?? 'en') === 'bn' ? 'checked' : '' ?>>
                            <span class="text-sm font-medium">বাংলা</span>
                        </label>
                    </div>
                    <button type="submit" class="mt-4 w-full bg-blue-600 hover:bg-blue-500 text-white px-6 py-2.5 rounded-xl text-sm font-semibold">Save language</button>
                </form>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6">
                <h3 class="font-bold mb-2">Account security</h3>
                <ul class="space-y-2 text-sm text-slate-600">
                    <li class="flex gap-2"><span class="text-green-600 font-bold">✓</span> Monthly and yearly licenses auto-renew through your saved gateway.</li>
                    <li class="flex gap-2"><span class="text-green-600 font-bold">✓</span> Your API key is shown only here and can be regenerated anytime.</li>
                    <li class="flex gap-2"><span class="text-green-600 font-bold">✓</span> Cancel or renew anytime from your <a href="/account/licenses" class="text-blue-600 hover:underline">licenses page</a>.</li>
                </ul>
            </div>
        </aside>
    </div>
</section>