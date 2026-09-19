<?php $pageTitle = 'SMS'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">SMS Campaigns</h1>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Send SMS</h2>
        <form action="/dashboard/sms/send" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Recipients *</label>
                <input type="text" name="recipients" placeholder="e.g. +14155552671, +447700900123, 01700000000" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Comma-separated phone numbers.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                <textarea name="message" rows="5" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" maxlength="1600" placeholder="Type your message"></textarea>
                <p class="text-xs text-gray-500 mt-1">Characters: <span id="char-count">0</span>/1600</p>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">Send SMS</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">SMS History</h2>
        <div class="space-y-3 max-h-96 overflow-y-auto">
            <?php if (!empty($history)): ?>
                <?php foreach ($history as $sms): ?>
                <div class="border-b pb-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500"><?= e(date('M d, H:i', strtotime($sms['created_at'] ?? ''))) ?></span>
                        <span class="px-2 py-0.5 text-xs rounded-full <?= ($sms['status'] ?? '') == 'sent' ? 'bg-green-100 text-green-700' : (($sms['status'] ?? '') == 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>"><?= e(ucfirst($sms['status'] ?? '')) ?></span>
                    </div>
                    <p class="text-sm mt-1"><?= e(truncate($sms['message'] ?? '', 80)) ?></p>
                    <p class="text-xs text-gray-400 mt-1">
                        Gateway: <?= e($sms['gateway'] ?? 'log') ?> &middot; Recipients: <?= e(count(array_filter(array_map('trim', explode(',', $sms['recipients'] ?? ''))))) ?>
                    </p>
                    <?php if (!empty($sms['sms_status'])): ?>
                        <details class="mt-1">
                            <summary class="text-xs text-blue-600 cursor-pointer">Delivery details</summary>
                            <pre class="text-xs text-gray-500 mt-1 whitespace-pre-wrap"><?= e(json_encode(json_decode($sms['sms_status'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                        </details>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">No SMS history yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.querySelector('textarea[name="message"]').addEventListener('input', function() {
    document.getElementById('char-count').textContent = this.value.length;
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>