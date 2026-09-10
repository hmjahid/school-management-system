<?php $title = $post['title']; $siteTitle = $title; ?>

<section class="max-w-3xl mx-auto px-4 py-16">
    <a href="/blog" class="text-sm text-blue-600 hover:underline">← <?= __('blog.back') ?></a>

    <h1 class="text-4xl font-extrabold mt-6"><?= htmlspecialchars($post['title']) ?></h1>

    <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500 mt-4">
        <?php if (!empty($post['category_name'])): ?>
            <a href="/blog/category/<?= htmlspecialchars($post['category_slug']) ?>" class="text-blue-600 font-semibold"><?= htmlspecialchars($post['category_name']) ?></a>
        <?php endif; ?>
        <span><?= __('blog.published_on') ?> <?= date('M j, Y', strtotime((string) $post['published_at'])) ?></span>
        <span>·</span>
        <span><?= __('blog.views') ?>: <?= (int) $post['views'] ?></span>
    </div>

    <?php if (!empty($post['featured_image'])): ?>
        <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="" class="mt-8 w-full rounded-2xl h-64 object-cover">
    <?php endif; ?>

    <?php if (!empty($post['excerpt'])): ?>
        <p class="mt-8 text-lg text-slate-600 leading-relaxed"><?= htmlspecialchars($post['excerpt']) ?></p>
    <?php endif; ?>

    <article class="mt-6 prose prose-slate max-w-none text-slate-700 leading-relaxed">
        <?= $post['content'] ?>
    </article>

    <?php if (!empty($recent)): ?>
        <div class="mt-16 border-t border-slate-200 pt-8">
            <h2 class="text-lg font-bold mb-4"><?= __('blog.recent') ?></h2>
            <ul class="space-y-3 text-sm">
                <?php foreach ($recent as $r): ?>
                    <li>
                        <a href="/blog/<?= htmlspecialchars($r['slug']) ?>" class="text-blue-600 hover:underline"><?= htmlspecialchars($r['title']) ?></a>
                        <span class="text-slate-400">· <?= date('M j, Y', strtotime((string) $r['published_at'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</section>