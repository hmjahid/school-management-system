<?php $pageTitle = 'Message Detail'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Message</h1>
    <a href="/dashboard/messages" class="text-gray-600 hover:text-gray-800">&larr; Back to Inbox</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="border-b pb-4 mb-4">
        <h2 class="text-xl font-bold"><?= e($message['subject'] ?? '') ?></h2>
        <div class="flex justify-between text-sm text-gray-500 mt-2">
            <span>From: <strong><?= e($message['sender_name'] ?? '') ?></strong></span>
            <span>To: <strong><?= e($message['receiver_name'] ?? '') ?></strong></span>
            <span><?= e($message['created_at'] ?? '') ?></span>
        </div>
    </div>
    <div class="prose max-w-none">
        <p class="whitespace-pre-wrap"><?= e($message['body'] ?? '') ?></p>
    </div>
    <div class="mt-6 pt-4 border-t flex justify-end space-x-2">
        <form action="/dashboard/messages/<?= e($message['id']) ?>" method="POST" onsubmit="return confirm('Delete?')">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
