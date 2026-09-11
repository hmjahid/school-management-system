<?php $pageTitle = 'Compose SMS Campaign'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Compose SMS Campaign</h1>
    <div class="flex gap-2">
        <a href="/dashboard/sms/templates" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition">Templates</a>
        <a href="/dashboard/sms/due-reminder" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition">Due reminder</a>
        <a href="/dashboard/sms" class="text-gray-600 hover:text-gray-800 px-4 py-2">&larr; Back</a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <form action="/dashboard/sms/preview" method="POST" class="space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Campaign name *</label>
            <input type="text" name="name" required maxlength="191" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Audience *</label>
            <div class="grid grid-cols-2 gap-2">
                <?php foreach ([
                    'all_users' => 'All users',
                    'students_class' => 'Students by class',
                    'students_section' => 'Students by section',
                    'students_individual' => 'Individual students',
                    'staff_role' => 'Staff by role',
                    'staff_individual' => 'Individual staff',
                ] as $val => $label): ?>
                <label class="flex items-center gap-2 text-sm border border-gray-200 rounded-lg px-3 py-2">
                    <input type="radio" name="audience_type" value="<?= $val ?>" <?= $val === 'all_users' ? 'checked' : '' ?> class="rounded-full"> <?= $label ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div id="audience-fields" class="space-y-3">
            <div data-for="students_class" class="hidden">
                <select name="school_class_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">All classes</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div data-for="students_section" class="hidden">
                <select name="section_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">All sections</option>
                    <?php foreach ($sections as $s): ?>
                    <option value="<?= e($s['id']) ?>"><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div data-for="staff_role" class="hidden">
                <select name="role_name" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">All staff roles</option>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= e($r['name']) ?>"><?= e($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div data-for="students_individual" class="hidden">
                <div class="border border-gray-200 rounded-lg p-3 max-h-48 overflow-y-auto">
                    <?php foreach ($students as $st): ?>
                    <label class="flex items-center gap-2 text-sm py-1">
                        <input type="checkbox" name="user_ids[]" value="<?= e($st['user_id']) ?>" class="rounded"> <?= e($st['name'] ?? ('Student #' . $st['id'])) ?> (<?= e($st['phone']) ?>)
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div data-for="staff_individual" class="hidden">
                <div class="border border-gray-200 rounded-lg p-3 max-h-48 overflow-y-auto">
                    <?php foreach ($users as $u): ?>
                    <label class="flex items-center gap-2 text-sm py-1">
                        <input type="checkbox" name="user_ids[]" value="<?= e($u['id']) ?>" class="rounded"> <?= e($u['name']) ?> (<?= e($u['phone']) ?>)
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
            <textarea name="message" rows="4" required maxlength="1000" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"></textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Schedule (optional)</label>
            <input type="datetime-local" name="scheduled_at" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="flex justify-end gap-2">
            <a href="/dashboard/sms" class="text-gray-600 hover:text-gray-800 px-4 py-2">Cancel</a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Preview &amp; send</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var radios = document.querySelectorAll('input[name="audience_type"]');
    var fields = document.querySelectorAll('#audience-fields [data-for]');
    function sync() {
        var val = document.querySelector('input[name="audience_type"]:checked').value;
        fields.forEach(function (el) {
            el.classList.toggle('hidden', el.dataset.for !== val);
        });
    }
    radios.forEach(function (r) { r.addEventListener('change', sync); });
    sync();
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>