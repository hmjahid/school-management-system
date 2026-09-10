<?php $adminTitle = 'Payments'; ?>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-400 text-xs uppercase">
            <tr><th class="px-4 py-3">Reference</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Gateway</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Created</th><th class="px-4 py-3"></th></tr>
        </thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
            <tr class="border-t border-slate-100">
                <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars($p['reference']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars((string) ($p['customer_name'] ?? '')) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($p['gateway']) ?></td>
                <td class="px-4 py-3 text-right"><?= htmlspecialchars($p['currency']) ?> <?= number_format((float) $p['amount'], 2) ?></td>
                <td class="px-4 py-3">
                    <form method="post" action="/admin/payments/<?= (int) $p['id'] ?>
            <?= csrf_field() ?>" class="flex gap-1 items-center">
                        <select name="status" class="border border-slate-300 rounded-lg px-2 py-1 text-xs">
                            <?php foreach (['pending', 'paid', 'failed', 'refunded'] as $status): ?>
                                <option value="<?= $status ?>" <?= $p['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="text-blue-600 text-xs hover:underline">save</button>
                    </form>
                </td>
                <td class="px-4 py-3 text-xs"><?= htmlspecialchars($p['created_at']) ?></td>
                <td class="px-4 py-3 text-xs"><?= $p['license_id'] ? '<a href="/admin/licenses/' . (int) $p['license_id'] . '" class="text-blue-600">license</a>' : '' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>