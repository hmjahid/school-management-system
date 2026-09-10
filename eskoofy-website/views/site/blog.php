<?php $title = __('blog.title'); $siteTitle = $title; ?>

<section class="max-w-7xl mx-auto px-4 py-16">
    <div class="text-center mb-10">
        <h1 class="text-4xl font-extrabold"><?= __('blog.title') ?></h1>
        <p class="text-slate-500 mt-2"><?= __('blog.sub') ?></p>
    </div>

    <?php if (!empty($categories)): ?>
    <div class="flex flex-wrap gap-2 justify-center mb-10">
        <a href="/blog" class="text-xs px-3 py-1.5 rounded-full border <?= empty($activeCategory) ? 'bg-slate-900 text-white border-slate-900' : 'border-slate-300 text-slate-600 hover:border-slate-500' ?>"><?= __('blog.all') ?></a>
        <?php foreach ($categories as $cat): ?>
            <a href="/blog/category/<?= htmlspecialchars($cat['slug']) ?>"
               class="text-xs px-3 py-1.5 rounded-full border <?= !empty($activeCategory) && (int) $activeCategory['id'] === (int) $cat['id'] ? 'bg-slate-900 text-white border-slate-900' : 'border-slate-300 text-slate-600 hover:border-slate-500' ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
        <div class="text-center text-slate-500"><?= __('blog.empty') ?></div>
    <?php else: ?>
        <div class="grid md:grid-cols-3 gap-8">
            <?php foreach ($posts as $post): ?>
                <a href="/blog/<?= htmlspecialchars($post['slug']) ?>" class="bg-white rounded-2xl border border-slate-200 esk-card-hover flex flex-col overflow-hidden">
                    <?php if (!empty($post['featured_image'])): ?>
                        <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="" class="h-44 w-full object-cover">
                    <?php else: ?>
                        <div class="h-44 bg-gradient-to-r from-slate-900 to-blue-700 flex items-center justify-center text-white font-extrabold text-xs uppercase tracking-widest"><?= __('brand.name') ?></div>
                    <?php endif; ?>
                    <div class="p-6 flex flex-col flex-1">
                        <div class="flex items-center gap-3 text-xs text-slate-500 mb-2">
                            <?php if (!empty($post['category_name'])): ?>
                                <span class="text-blue-600 font-semibold"><?= htmlspecialchars($post['category_name']) ?></span>
                            <?php endif; ?>
                            <span><?= date('M j, Y', strtotime((string) $post['published_at'])) ?></span>
                        </div>
                        <h2 class="font-bold text-lg mb-2 group-hover:text-blue-600"><?= htmlspecialchars($post['title']) ?></h2>
                        <?php if (!empty($post['excerpt'])): ?>
                            <p class="text-sm text-slate-600 flex-1"><?= htmlspecialchars($post['excerpt']) ?></p>
                        <?php endif; ?>
                        <span class="mt-4 text-sm font-semibold text-blue-600"><?= __('blog.read_more') ?> →</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($lastPage > 1): ?>
        <div class="flex items-center justify-center gap-4 mt-12 text-sm font-semibold">
            <?php if ($currentPage > 1): ?>
                <a href="/blog?page=<?= $currentPage - 1 ?>" class="px-4 py-2 rounded-lg border border-slate-300 hover:border-slate-500">← <?= __('blog.prev') ?></a>
            <?php endif; ?>
            <span class="text-slate-500"><?= $currentPage ?> / <?= $lastPage ?></span>
            <?php if ($currentPage < $lastPage): ?>
                <a href="/blog?page=<?= $currentPage + 1 ?>" class="px-4 py-2 rounded-lg border border-slate-300 hover:border-slate-500"><?= __('blog.next') ?> →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</section>