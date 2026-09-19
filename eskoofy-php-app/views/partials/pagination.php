<?php if ($paginator['hasPages']()): ?>
<nav class="flex items-center justify-between mt-6">
    <div class="text-sm text-gray-600">
        Showing <?= $paginator['firstItem']() ?> to <?= $paginator['lastItem']() ?> of <?= $paginator['total']() ?> entries
    </div>
    <div class="flex space-x-1">
        <?php if ($paginator['onFirstPage']()): ?>
            <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 rounded cursor-not-allowed">Previous</span>
        <?php else: ?>
            <a href="<?= $paginator['previousPageUrl']() ?>" class="px-3 py-2 text-sm text-gray-700 bg-white border rounded hover:bg-gray-50">Previous</a>
        <?php endif; ?>

        <?php foreach ($paginator['getUrlRange'](max(1, $paginator['currentPage']() - 2), min($paginator['lastPage'](), $paginator['currentPage']() + 2)) as $page => $url): ?>
            <?php if ($page == $paginator['currentPage']()): ?>
                <span class="px-3 py-2 text-sm text-white bg-blue-600 rounded"><?= $page ?></span>
            <?php else: ?>
                <a href="<?= $url ?>" class="px-3 py-2 text-sm text-gray-700 bg-white border rounded hover:bg-gray-50"><?= $page ?></a>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($paginator['hasMorePages']()): ?>
            <a href="<?= $paginator['nextPageUrl']() ?>" class="px-3 py-2 text-sm text-gray-700 bg-white border rounded hover:bg-gray-50">Next</a>
        <?php else: ?>
            <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 rounded cursor-not-allowed">Next</span>
        <?php endif; ?>
    </div>
</nav>
<?php endif; ?>