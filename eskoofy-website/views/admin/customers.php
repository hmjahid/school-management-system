<?php $adminTitle = 'Customers'; ?>

<div class="flex items-center justify-between mb-4">
    <form method="get" action="/admin/customers" class="flex gap-2">
        <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search name or email…" class="border border-slate-300 rounded-lg px-4 py-2 text-sm w-64">
        <button class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm">Search</button>
    </form>
    <span class="text-sm text-slate-400"><?= count($customers) ?> customer(s)</span>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-400 text-xs uppercase">
            <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Company</th><th class="px-4 py-3 text-right">Licenses</th><th class="px-4 py-3 text-right">Total spent</th><th class="px-4 py-3">Status</th></tr>
        </thead>
        <tbody>
        <?php foreach ($customers as $c): ?>
            <tr class="border-t border-slate-100">
                <td class="px-4 py-3"><a href="/admin/customers/<?= (int) $c['id'] ?>" class="text-blue-600 font-semibold hover:underline"><?= htmlspecialchars($c['name']) ?></a></td>
                <td class="px-4 py-3"><?= htmlspecialchars($c['email']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars((string) ($c['company'] ?? '')) ?></td>
                <td class="px-4 py-3 text-right"><?= (int) $c['license_count'] ?></td>
                <td class="px-4 py-3 text-right">$<?= number_format((float) $c['total_spent'], 2) ?></td>
                <td class="px-4 py-3"><span class="text-xs px-2 py-1 rounded-full <?= $c['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= $c['status'] ?></span></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($customers)): ?>
            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No customers found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>