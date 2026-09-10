<?php $adminTitle = 'Messages'; ?>

<div class="grid lg:grid-cols-2 gap-4">
    <?php foreach ($messages as $m): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-6 <?= $m['read_at'] ? 'opacity-70' : 'border-blue-300' ?>">
            <div class="flex items-center justify-between mb-2">
                <div class="font-semibold"><?= htmlspecialchars($m['name']) ?> <span class="text-slate-400 font-normal">· <?= htmlspecialchars($m['email']) ?></span></div>
                <?php if (!$m['read_at']): ?>
                    <form method="post" action="/admin/messages/<?= (int) $m['id'] ?>/read"><?= csrf_field() ?><button class="text-xs text-blue-600 hover:underline">mark read</button></form>
                <?php endif; ?>
            </div>
            <div class="text-sm text-slate-500 mb-2"><?= htmlspecialchars((string) ($m['subject'] ?? '')) ?></div>
            <p class="text-sm whitespace-pre-line"><?= htmlspecialchars($m['message']) ?></p>
            <div class="text-xs text-slate-400 mt-3"><?= htmlspecialchars($m['created_at']) ?></div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($messages)): ?>
        <div class="col-span-2 bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-400">No messages yet.</div>
    <?php endif; ?>
</div>