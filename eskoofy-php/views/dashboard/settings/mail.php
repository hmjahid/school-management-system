<?php $pageTitle = 'Mail Settings'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <a href="/dashboard/settings" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    <h1 class="text-2xl font-bold text-gray-800">Mail Settings</h1>
</div>

<div class="bg-white rounded-xl shadow-sm p-8 mb-6">
    <form action="/dashboard/settings/mail" method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mail Driver</label>
                <select name="mail_driver" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="mail">PHP Mail</option>
                    <option value="smtp">SMTP</option>
                    <option value="sendmail">Sendmail</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Address</label>
                <input type="email" name="mail_from" value="<?= e($settings['mail_from'] ?? 'noreply@eskoofy.test') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Host</label>
                <input type="text" name="smtp_host" value="<?= e($settings['smtp_host'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Port</label>
                <input type="text" name="smtp_port" value="<?= e($settings['smtp_port'] ?? '587') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Save</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm p-8">
    <h2 class="text-lg font-bold mb-4">Send Test Email</h2>
    <form action="/dashboard/settings/mail/test" method="POST" class="flex gap-2">
        <?= csrf_field() ?>
        <input type="email" name="email" placeholder="test@example.com" required class="flex-1 border border-gray-300 rounded-lg px-4 py-2">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Send Test</button>
    </form>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
