<?php $pageTitle = 'Messages'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800"><?= ($sent ?? false) ? 'Sent Messages' : 'Inbox' ?></h1>
    <div class="flex space-x-2">
        <?php if (!($sent ?? false)): ?>
        <button onclick="document.getElementById('compose-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Compose</button>
        <?php endif; ?>
        <a href="/dashboard/messages" class="<?= !($sent ?? false) ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' ?> px-4 py-2 rounded-lg hover:bg-gray-200">Inbox</a>
        <a href="/dashboard/messages/sent" class="<?= ($sent ?? false) ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' ?> px-4 py-2 rounded-lg hover:bg-gray-200">Sent</a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b w-8"></th>
                    <th class="py-3 px-4 font-semibold border-b"><?= ($sent ?? false) ? 'To' : 'From' ?></th>
                    <th class="py-3 px-4 font-semibold border-b">Subject</th>
                    <th class="py-3 px-4 font-semibold border-b">Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                    <tr class="border-b hover:bg-gray-50 <?= !($sent ?? false) && !($row['is_read'] ?? 0) ? 'bg-blue-50' : '' ?>">
                        <td class="py-3 px-4">
                            <?php if (!($row['is_read'] ?? 0)): ?>
                            <span class="w-2 h-2 bg-blue-600 rounded-full inline-block"></span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 font-medium"><?= e(($sent ?? false) ? ($row['receiver_name'] ?? '') : ($row['sender_name'] ?? '')) ?></td>
                        <td class="py-3 px-4">
                            <a href="/dashboard/messages/<?= e($row['id']) ?>" class="hover:text-blue-600 <?= !($sent ?? false) && !($row['is_read'] ?? 0) ? 'font-bold' : '' ?>"><?= e($row['subject'] ?? '') ?></a>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e($row['created_at'] ?? '') ?></td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="/dashboard/messages/<?= e($row['id']) ?>" class="text-blue-600 hover:underline text-sm">View</a>
                                <form action="/dashboard/messages/<?= e($row['id']) ?>" method="POST" onsubmit="return confirm('Delete?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="py-8 text-center text-gray-500">No messages.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="compose-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-lg">
        <h2 class="text-xl font-bold mb-4">Compose Message</h2>
        <form action="/dashboard/messages" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To *</label>
                <select name="receiver_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Select Recipient</option>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                        <option value="<?= e($user['id']) ?>"><?= e($user['name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                <input type="text" name="subject" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                <textarea name="body" rows="5" required class="w-full border border-gray-300 rounded-lg px-4 py-2"></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('compose-modal').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Send</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
