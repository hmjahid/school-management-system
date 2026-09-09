<?php $pageTitle = 'Notification Preferences'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Notification Preferences</h1>
    <a href="/dashboard/notifications" class="text-gray-600 hover:text-gray-800">&larr; Back</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <form action="/dashboard/notifications/preferences" method="POST" class="space-y-6">
        <?= csrf_field() ?>
        <h2 class="text-lg font-bold">Email Notifications</h2>
        <div class="space-y-3">
            <label class="flex items-center">
                <input type="checkbox" name="email_attendance" value="1" <?= ($prefs['email_attendance'] ?? 1) ? 'checked' : '' ?> class="rounded border-gray-300 text-blue-600 mr-3">
                <span>Attendance alerts</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" name="email_results" value="1" <?= ($prefs['email_results'] ?? 1) ? 'checked' : '' ?> class="rounded border-gray-300 text-blue-600 mr-3">
                <span>Exam results published</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" name="email_fees" value="1" <?= ($prefs['email_fees'] ?? 1) ? 'checked' : '' ?> class="rounded border-gray-300 text-blue-600 mr-3">
                <span>Fee reminders</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" name="email_notices" value="1" <?= ($prefs['email_notices'] ?? 1) ? 'checked' : '' ?> class="rounded border-gray-300 text-blue-600 mr-3">
                <span>New notices and announcements</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" name="email_messages" value="1" <?= ($prefs['email_messages'] ?? 1) ? 'checked' : '' ?> class="rounded border-gray-300 text-blue-600 mr-3">
                <span>Internal messages</span>
            </label>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Save Preferences</button>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
