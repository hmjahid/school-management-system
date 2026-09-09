<?php $pageTitle = 'Leave Requests'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Leave Requests</h1>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Staff</th>
                    <th class="py-3 px-4 font-semibold border-b">Type</th>
                    <th class="py-3 px-4 font-semibold border-b">From</th>
                    <th class="py-3 px-4 font-semibold border-b">To</th>
                    <th class="py-3 px-4 font-semibold border-b">Days</th>
                    <th class="py-3 px-4 font-semibold border-b">Reason</th>
                    <th class="py-3 px-4 font-semibold border-b">Status</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($requests)): ?>
                    <?php foreach ($requests as $request): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($request->staff->name ?? '') ?></td>
                        <td class="py-3 px-4"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700"><?= e(ucfirst($request->type ?? '')) ?></span></td>
                        <td class="py-3 px-4 text-sm"><?= e($request->start_date->format('M d, Y')) ?></td>
                        <td class="py-3 px-4 text-sm"><?= e($request->end_date->format('M d, Y')) ?></td>
                        <td class="py-3 px-4 text-center"><?= e($request->days ?? 0) ?></td>
                        <td class="py-3 px-4 text-sm text-gray-500"><?= e(Str::limit($request->reason ?? '', 50)) ?></td>
                        <td class="py-3 px-4">
                            <?php
                            $statusColors = ['pending' => 'bg-yellow-100 text-yellow-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700'];
                            ?>
                            <span class="px-2 py-1 text-xs rounded-full <?= $statusColors[$request->status ?? 'pending'] ?>"><?= e(ucfirst($request->status ?? 'pending')) ?></span>
                        </td>
                        <td class="py-3 px-4">
                            <?php if (($request->status ?? '') == 'pending'): ?>
                            <div class="flex space-x-2">
                                <form action="/dashboard/payroll/leave-requests/<?= e($request->id) ?>/approve" method="POST" onsubmit="return confirm('Approve?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-green-600 hover:underline text-sm">Approve</button>
                                </form>
                                <form action="/dashboard/payroll/leave-requests/<?= e($request->id) ?>/reject" method="POST" onsubmit="return confirm('Reject?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Reject</button>
                                </form>
                            </div>
                            <?php else: ?>
                            <span class="text-gray-400 text-sm">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="py-8 text-center text-gray-500">No leave requests found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>