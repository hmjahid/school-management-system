<?php $pageTitle = 'Seat Plan'; ?>
<?php ob_start(); ?>

<div class="mb-6 print:hidden">
    <a href="/dashboard/seat-plans" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    <button onclick="window.print()" class="ml-4 text-sm bg-blue-600 text-white px-3 py-1 rounded">Print</button>
</div>

<div class="bg-white rounded-xl shadow-sm p-8">
    <div class="text-center mb-6 pb-4 border-b">
        <h1 class="text-2xl font-bold">Seat Plan — <?= e($exam['name']) ?></h1>
        <p class="text-gray-500"><?= e(date('M d, Y', strtotime($exam['exam_date'] ?? 'now'))) ?></p>
    </div>

    <table class="w-full text-left text-sm">
        <thead><tr class="bg-gray-50 border-b"><th class="py-2 px-3">Room</th><th class="py-2 px-3">Seat</th><th class="py-2 px-3">Admission</th><th class="py-2 px-3">Name</th><th class="py-2 px-3">Class</th><th class="py-2 px-3">Roll</th></tr></thead>
        <tbody>
            <?php if (!empty($seating)): ?>
                <?php foreach ($seating as $s): ?>
                <tr class="border-b">
                    <td class="py-2 px-3 font-medium"><?= e($s['room']) ?></td>
                    <td class="py-2 px-3"><?= e($s['seat']) ?></td>
                    <td class="py-2 px-3 font-mono text-xs"><?= e($s['student']['admission_number']) ?></td>
                    <td class="py-2 px-3"><?= e($s['student']['name']) ?></td>
                    <td class="py-2 px-3"><?= e($s['student']['class_name'] ?? '') ?></td>
                    <td class="py-2 px-3"><?= e($s['student']['roll_number'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="py-4 text-center text-gray-500">No students enrolled.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
