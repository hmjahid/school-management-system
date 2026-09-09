<?php $pageTitle = 'Profile'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">My Profile</h1>
</div>

<div class="grid md:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="w-24 h-24 bg-blue-600 text-white rounded-full flex items-center justify-center text-3xl font-bold mx-auto mb-4">
            <?= strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) ?>
        </div>
        <h3 class="text-lg font-bold"><?= e(auth()->user()->name ?? '') ?></h3>
        <p class="text-gray-500 text-sm"><?= e(auth()->user()->email ?? '') ?></p>
        <span class="inline-block mt-2 px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-700"><?= e(ucfirst(auth()->user()->role ?? '')) ?></span>
    </div>

    <div class="md:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-bold mb-4">Personal Information</h2>
            <form action="/dashboard/profile" method="POST" enctype="multipart/form-data" class="space-y-4">
                <?= csrf_field() ?>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" name="name" value="<?= e(auth()->user()->name ?? '') ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="email" value="<?= e(auth()->user()->email ?? '') ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" name="phone" value="<?= e(auth()->user()->phone ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Photo</label>
                        <input type="file" name="photo" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Update Profile</button>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-bold mb-4">Change Password</h2>
            <form action="/dashboard/profile/password" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Password *</label>
                    <input type="password" name="current_password" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password *</label>
                        <input type="password" name="password" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password *</label>
                        <input type="password" name="password_confirmation" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded-lg hover:bg-gray-900">Change Password</button>
            </form>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>