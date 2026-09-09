<?php $pageTitle = 'News'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">News</h1>
    <a href="/dashboard/news/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Create News</a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Title</th>
                    <th class="py-3 px-4 font-semibold border-b">Category</th>
                    <th class="py-3 px-4 font-semibold border-b">Status</th>
                    <th class="py-3 px-4 font-semibold border-b">Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($news)): ?>
                    <?php foreach ($news as $article): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($article->title) ?></td>
                        <td class="py-3 px-4"><?= e($article->category ?? '-') ?></td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded-full <?= ($article->is_published ?? false) ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                <?= ($article->is_published ?? false) ? 'Published' : 'Draft' ?>
                            </span>
                        </td>
                        <td class="py-3 px-4"><?= e($article->created_at->format('M d, Y')) ?></td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="/dashboard/news/<?= e($article->id) ?>/edit" class="text-green-600 hover:underline text-sm">Edit</a>
                                <form action="/dashboard/news/<?= e($article->id) ?>" method="POST" onsubmit="return confirm('Delete this article?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-500">No news articles found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>