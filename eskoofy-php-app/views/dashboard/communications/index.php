<?php $pageTitle = 'Communications'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Communications Hub</h1>
    <p class="text-gray-500">Manage all school communications</p>
</div>

<div class="grid md:grid-cols-4 gap-6">
    <a href="/dashboard/messages" class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition">
        <div class="text-3xl mb-2">✉️</div>
        <h3 class="font-bold">Messages</h3>
        <p class="text-2xl font-bold text-blue-600 mt-2"><?= e($stats['messages']) ?></p>
    </a>
    <a href="/dashboard/notifications" class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition">
        <div class="text-3xl mb-2">🔔</div>
        <h3 class="font-bold">Notifications</h3>
        <p class="text-2xl font-bold text-blue-600 mt-2"><?= e($stats['notifications']) ?></p>
    </a>
    <a href="/dashboard/sms" class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition">
        <div class="text-3xl mb-2">📱</div>
        <h3 class="font-bold">SMS Campaigns</h3>
        <p class="text-2xl font-bold text-blue-600 mt-2"><?= e($stats['sms_campaigns']) ?></p>
    </a>
    <a href="/dashboard/announcements" class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition">
        <div class="text-3xl mb-2">📣</div>
        <h3 class="font-bold">Announcements</h3>
        <p class="text-2xl font-bold text-blue-600 mt-2"><?= e($stats['announcements']) ?></p>
    </a>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
