<?php $adminTitle = 'Custom orders'; ?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <span class="text-sm text-slate-400"><?= count($requests) ?> request(s) · <?= (int) $unread ?> unread</span>
    <div class="flex flex-wrap gap-2">
        <a href="/admin/custom-requests" class="px-3 py-1.5 rounded-lg text-sm <?= $currentStatus === '' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-300 text-slate-600' ?>">All</a>
        <?php foreach ($statusColors as $cst => $color): ?>
            <a href="/admin/custom-requests?status=<?= $cst ?>" class="px-3 py-1.5 rounded-lg text-sm <?= $currentStatus === $cst ? 'bg-slate-900 text-white' : 'bg-white border border-slate-300 text-slate-600' ?>">
                <?= htmlspecialchars(str_replace('_', ' ', $cst)) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm min-w-[900px]">
        <thead class="bg-slate-50 text-left text-slate-400 text-xs uppercase">
            <tr>
                <th class="px-4 py-3">#</th>
                <th class="px-4 py-3">Contact</th>
                <th class="px-4 py-3">Product</th>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3">Subject / details</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Received</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($requests)): ?>
            <tr><td colspan="8" class="px-4 py-6 text-center text-slate-400">No custom orders yet.</td></tr>
        <?php else: foreach ($requests as $r): ?>
            <tr class="border-t border-slate-100 <?= $r['read_at'] === null ? 'bg-blue-50/60' : '' ?>">
                <td class="px-4 py-3 text-slate-400">#<?= (int) $r['id'] ?></td>
                <td class="px-4 py-3">
                    <div class="font-semibold"><?= htmlspecialchars($r['name']) ?></div>
                    <div class="text-xs text-slate-500"><?= htmlspecialchars($r['email']) ?></div>
                    <?php if (($r['phone'] ?? '') !== ''): ?>
                        <div class="text-xs text-slate-500"><?= htmlspecialchars($r['phone']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-700"><?= htmlspecialchars($r['product']) ?></span>
                </td>
                <td class="px-4 py-3 text-xs"><?= htmlspecialchars(str_replace('_', ' ', (string) $r['request_type'])) ?></td>
                <td class="px-4 py-3">
                    <div class="font-medium"><?= htmlspecialchars((string) ($r['subject'] ?? '')) ?></div>
                    <div class="text-xs text-slate-500 line-clamp-2 max-w-xs"><?= htmlspecialchars(mb_substr((string) $r['details'], 0, 160)) ?></div>
                    <?php if (($r['budget'] ?? '') !== ''): ?><div class="text-xs text-slate-500">Budget: <?= htmlspecialchars($r['budget']) ?></div><?php endif; ?>
                    <?php if (($r['timeline'] ?? '') !== ''): ?><div class="text-xs text-slate-500">Timeline: <?= htmlspecialchars($r['timeline']) ?></div><?php endif; ?>
                </td>
                <td class="px-4 py-3">
                    <form method="post" action="/admin/custom-requests/<?= (int) $r['id'] ?>/status" class="flex items-center gap-1">
                        <?= csrf_field() ?>
                        <select name="status" class="border border-slate-300 rounded-lg px-2 py-1 text-xs <?= $statusColors[$r['status']] ?? '' ?>">
                            <?php foreach (\App\Models\CustomRequest::STATUSES as $opt): ?>
                                <option value="<?= $opt ?>" <?= $r['status'] === $opt ? 'selected' : '' ?>><?= htmlspecialchars(str_replace('_', ' ', $opt)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="text-blue-600 text-xs hover:underline" title="Save status">Save</button>
                    </form>
                </td>
                <td class="px-4 py-3 text-xs text-slate-500"><?= $r['created_at'] ? date('Y-m-d H:i', strtotime((string) $r['created_at'])) : '—' ?></td>
                <td class="px-4 py-3 text-right space-x-3 text-sm whitespace-nowrap">
                    <?php if ($r['read_at'] === null): ?>
                        <form method="post" action="/admin/custom-requests/<?= (int) $r['id'] ?>/read" class="inline">
                            <?= csrf_field() ?>
                            <button class="text-blue-600 hover:underline">Mark read</button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="/admin/custom-requests/<?= (int) $r['id'] ?>/delete" class="inline" onsubmit="return confirm('Archive this request?')">
                        <?= csrf_field() ?>
                        <button class="text-red-600 hover:underline">Archive</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>