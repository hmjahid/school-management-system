<?php $title = 'My account — Downloads'; $siteTitle = $title; ?>

<?php \App\Core\View::partial('account.partials.account_header', ['accountPage' => 'downloads', 'customer' => $customer]); ?>

<section class="max-w-5xl mx-auto px-4 pb-12">
    <h1 class="text-3xl font-extrabold mb-2">Downloads</h1>
    <p class="text-slate-500 mb-8">Product packages for your active licenses, plus user manuals and setup guides.</p>

    <h2 class="text-lg font-bold mb-3">Product packages</h2>
    <?php if (empty($packages)): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-6 text-sm text-slate-500 mb-8">
            No packages available for your licensed products yet. You’ll be emailed when a new release is published.
        </div>
    <?php else: ?>
        <div class="grid md:grid-cols-2 gap-4 mb-8">
            <?php foreach ($packages as $p): ?>
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs uppercase tracking-wide text-blue-600 font-semibold"><?= htmlspecialchars(ucfirst((string) $p['product'])) ?></div>
                            <div class="font-bold mt-1">v<?= htmlspecialchars($p['version']) ?></div>
                        </div>
                        <span class="text-xs text-slate-400"><?= number_format((float) ($p['size'] / 1048576), 2) ?> MB</span>
                    </div>
                    <?php if (!empty($p['notes'])): ?>
                        <p class="text-sm text-slate-500 mt-2"><?= nl2br(htmlspecialchars((string) $p['notes'])) ?></p>
                    <?php endif; ?>
                    <a href="/account/downloads/package/<?= (int) $p['id'] ?>" class="mt-4 inline-block bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2 rounded-lg">Download ZIP</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2 class="text-lg font-bold mb-3">User manual & setup guides</h2>
    <?php if (empty($documents)): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-6 text-sm text-slate-500">
            No documents published yet.
        </div>
    <?php else: ?>
        <div class="grid md:grid-cols-2 gap-4">
            <?php foreach ($documents as $d): ?>
                <div class="bg-white rounded-xl border border-slate-200 p-6 flex items-center justify-between gap-3">
                    <div>
                        <div class="font-semibold"><?= htmlspecialchars($d['title']) ?></div>
                        <div class="text-xs text-slate-400 capitalize mt-0.5"><?= htmlspecialchars(str_replace('_', ' ', (string) $d['kind'])) ?></div>
                    </div>
                    <a href="/account/downloads/document/<?= (int) $d['id'] ?>" class="shrink-0 bg-slate-900 hover:bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">Download</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>