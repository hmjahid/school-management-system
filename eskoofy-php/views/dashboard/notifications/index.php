<?php $pageTitle = 'Notifications'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Notifications</h1>
        <?php if (($unreadCount ?? 0) > 0): ?>
        <p class="text-gray-500"><?= e($unreadCount) ?> unread</p>
        <?php endif; ?>
    </div>
    <div class="flex space-x-2">
        <?php if (($unreadCount ?? 0) > 0): ?>
        <form action="/dashboard/notifications/mark-all-read" method="POST">
            <?= csrf_field() ?>
            <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Mark All Read</button>
        </form>
        <?php endif; ?>
        <a href="/dashboard/notifications/preferences" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Preferences</a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="divide-y">
        <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $row): ?>
            <?php $unread = empty($row['opened_at']); ?>
            <div class="p-4 <?= $unread ? 'bg-blue-50' : '' ?> hover:bg-gray-50 flex justify-between items-start">
                <div class="flex-1">
                    <p class="font-medium <?= $unread ? 'text-gray-900' : 'text-gray-600' ?>"><?= e($row['type'] ?? 'Notification') ?></p>
                    <p class="text-sm text-gray-500 mt-1"><?= e($row['content'] ?? '') ?></p>
                    <p class="text-xs text-gray-400 mt-1"><?= e($row['created_at'] ?? '') ?></p>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <?php if ($unread): ?>
                    <a href="/dashboard/notifications/<?= e($row['id']) ?>/read" class="text-blue-600 hover:underline text-sm">Mark Read</a>
                    <?php else: ?>
                    <span class="text-gray-400 text-xs">Read</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="p-8 text-center text-gray-500">No notifications.</div>
        <?php endif; ?>
    </div>
</div>

<?php if ($lastPage > 1): ?>
<div class="mt-4 flex justify-center space-x-2">
    <?php for ($i = 1; $i <= $lastPage; $i++): ?>
    <a href="?page=<?= $i ?>" class="px-3 py-1 rounded-lg <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
