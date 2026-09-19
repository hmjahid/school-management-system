<?php $pageTitle = 'Student ID Cards'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Student ID Cards</h1>
    <div class="flex gap-2">
        <a href="/dashboard/id-cards/batch/create" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition">Batch generate</a>
        <a href="/dashboard/id-cards/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ New ID Card</a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/id-cards" method="GET" class="flex flex-col sm:flex-row gap-4">
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Search student or card number..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">#</th>
                    <th class="py-3 px-4 font-semibold border-b">Student</th>
                    <th class="py-3 px-4 font-semibold border-b">Card Number</th>
                    <th class="py-3 px-4 font-semibold border-b">Issue Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Expiry</th>
                    <th class="py-3 px-4 font-semibold border-b">Status</th>
                    <th class="py-3 px-4 font-semibold border-b text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $i => $card): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4"><?= $i + 1 ?></td>
                        <td class="py-3 px-4 font-medium"><?= e($card['student_name'] ?? '') ?></td>
                        <td class="py-3 px-4"><?= e($card['id_card_number'] ?? '') ?></td>
                        <td class="py-3 px-4"><?= e(date('d M Y', strtotime($card['issue_date'] ?? 'now'))) ?></td>
                        <td class="py-3 px-4"><?= !empty($card['expiry_date']) ? e(date('d M Y', strtotime($card['expiry_date']))) : '—' ?></td>
                        <td class="py-3 px-4">
                            <?php $st = $card['status'] ?? 'active'; ?>
                            <span class="px-2 py-1 text-xs rounded-full <?= $st === 'revoked' ? 'bg-red-100 text-red-700' : ($st === 'expired' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') ?>">
                                <?= e(ucfirst($st)) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right space-x-2">
                            <a href="/dashboard/id-cards/<?= e($card['id']) ?>" class="text-blue-600 hover:underline text-sm">Show</a>
                            <a href="/dashboard/id-cards/<?= e($card['id']) ?>/preview" class="text-blue-600 hover:underline text-sm" target="_blank">Preview</a>
                            <a href="/dashboard/id-cards/<?= e($card['id']) ?>/print" class="text-blue-600 hover:underline text-sm" target="_blank">Print</a>
                            <a href="/dashboard/id-cards/<?= e($card['id']) ?>/edit" class="text-gray-600 hover:underline text-sm">Edit</a>
                            <form action="/dashboard/id-cards/<?= e($card['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Delete this ID card?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="7" class="py-8 text-center text-gray-500">No ID cards found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (($lastPage ?? 1) > 1): ?>
    <div class="p-4 border-t flex justify-between items-center text-sm">
        <span>Page <?= e((string) $page) ?> of <?= e((string) $lastPage) ?></span>
        <div class="flex gap-2">
            <?php if (($page ?? 1) > 1): ?><a href="/dashboard/id-cards?page=<?= (int) $page - 1 ?>" class="text-blue-600 hover:underline">Prev</a><?php endif; ?>
            <?php if (($page ?? 1) < ($lastPage ?? 1)): ?><a href="/dashboard/id-cards?page=<?= (int) $page + 1 ?>" class="text-blue-600 hover:underline">Next</a><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>