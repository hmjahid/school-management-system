<?php $pageTitle = 'Career Applications'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Job Applications</h1>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-left">
        <thead><tr class="bg-gray-50">
            <th class="py-3 px-4 font-semibold border-b">Date</th>
            <th class="py-3 px-4 font-semibold border-b">Job</th>
            <th class="py-3 px-4 font-semibold border-b">Applicant</th>
            <th class="py-3 px-4 font-semibold border-b">Email</th>
            <th class="py-3 px-4 font-semibold border-b">Phone</th>
            <th class="py-3 px-4 font-semibold border-b">Status</th>
            <th class="py-3 px-4 font-semibold border-b">Actions</th>
        </tr></thead>
        <tbody>
            <?php if (!empty($applications)): ?>
                <?php foreach ($applications as $a): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4 text-sm"><?= e(date('M d, Y', strtotime($a['created_at'] ?? 'now'))) ?></td>
                    <td class="py-3 px-4 text-sm"><?= e($a['job_title'] ?? '-') ?></td>
                    <td class="py-3 px-4 font-medium"><?= e($a['name']) ?></td>
                    <td class="py-3 px-4 text-sm"><?= e($a['email']) ?></td>
                    <td class="py-3 px-4 text-sm"><?= e($a['phone'] ?? '-') ?></td>
                    <td class="py-3 px-4">
                        <form action="/dashboard/careers/applications/<?= e($a['id']) ?>/status" method="POST" class="inline">
                            <?= csrf_field() ?>
                            <select name="status" onchange="this.form.submit()" class="text-xs border border-gray-300 rounded px-2 py-1">
                                <?php foreach (['pending','reviewed','shortlisted','rejected','hired'] as $s): ?>
                                <option value="<?= e($s) ?>" <?= ($a['status'] ?? '') === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td class="py-3 px-4">
                        <?php if (!empty($a['resume_path'])): ?>
                        <a href="/<?= e($a['resume_path']) ?>" target="_blank" class="text-blue-600 hover:underline text-sm">Resume</a>
                        <?php endif; ?>
                        <form action="/dashboard/careers/applications/<?= e($a['id']) ?>" method="POST" class="inline ml-2" onsubmit="return confirm('Delete?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="py-8 text-center text-gray-500">No applications yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/dashboard.php'; ?>
