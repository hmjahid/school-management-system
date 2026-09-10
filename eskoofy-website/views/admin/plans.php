<?php $adminTitle = 'Plans'; ?>

<div class="flex items-center justify-between mb-4">
    <span class="text-sm text-slate-400"><?= count($plans) ?> plan(s)</span>
    <a href="/admin/plans/create" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm">New plan</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-400 text-xs uppercase">
            <tr><th class="px-4 py-3">Product</th><th class="px-4 py-3">Name</th><th class="px-4 py-3">Slug</th><th class="px-4 py-3">Period</th><th class="px-4 py-3 text-right">Price</th><th class="px-4 py-3">Active</th><th class="px-4 py-3"></th></tr>
        </thead>
        <tbody>
        <?php foreach ($plans as $plan): ?>
            <tr class="border-t border-slate-100">
                <td class="px-4 py-3"><?= htmlspecialchars($plan['product']) ?></td>
                <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($plan['name']) ?></td>
                <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars($plan['slug']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($plan['period']) ?></td>
                <td class="px-4 py-3 text-right">$<?= number_format((float) $plan['price'], 2) ?></td>
                <td class="px-4 py-3"><?= $plan['active'] ? 'yes' : 'no' ?></td>
                <td class="px-4 py-3"><a href="/admin/plans/<?= (int) $plan['id'] ?>/edit" class="text-blue-600 hover:underline">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>