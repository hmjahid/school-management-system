<?php $pageTitle = 'Permissions'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Permissions &amp; Roles</h1>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Permissions</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead><tr class="border-b"><th class="py-2">Name</th><th class="py-2">Guard</th></tr></thead>
                <tbody>
                    <?php if (!empty($permissions)): ?>
                        <?php foreach ($permissions as $p): ?>
                        <tr class="border-b"><td class="py-2"><?= e($p['name']) ?></td><td class="py-2 text-gray-500"><?= e($p['guard_name'] ?? 'web') ?></td></tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2" class="py-4 text-center text-gray-500">No permissions yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Roles</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead><tr class="border-b"><th class="py-2">Name</th><th class="py-2">Guard</th></tr></thead>
                <tbody>
                    <?php if (!empty($roles)): ?>
                        <?php foreach ($roles as $r): ?>
                        <tr class="border-b"><td class="py-2"><?= e($r['name']) ?></td><td class="py-2 text-gray-500"><?= e($r['guard_name'] ?? 'web') ?></td></tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2" class="py-4 text-center text-gray-500">No roles yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/dashboard.php'; ?>
