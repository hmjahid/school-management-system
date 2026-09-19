<?php $adminTitle = 'Clear cache'; ?>

<div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6">
    <h2 class="font-bold mb-1">Frontend cache</h2>
    <p class="text-sm text-slate-500 mb-5">Clears the local file caches (<code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">storage/cache</code>, compiled views), resets the PHP opcode cache, and bumps the cache-busting version used for frontend assets. Safe to run at any time.</p>
    <form method="post" action="/admin/cache/clear" onsubmit="return confirm('Clear the frontend cache now?')">
        <?= csrf_field() ?>
        <button class="bg-blue-600 hover:bg-blue-500 text-white font-semibold px-6 py-3 rounded-lg">Clear cache</button>
    </form>
</div>