<?php $pageTitle = 'Gallery'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Photo Gallery</h1>
        <p class="text-blue-100 text-lg">Memories captured from our school events</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap justify-center gap-3 mb-12">
            <button class="gallery-filter px-4 py-2 rounded-full text-sm font-medium bg-blue-600 text-white" data-category="all">All</button>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                <button class="gallery-filter px-4 py-2 rounded-full text-sm font-medium bg-gray-200 text-gray-700 hover:bg-gray-300" data-category="<?= e($category->slug) ?>"><?= e($category->name) ?></button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="gallery-grid">
            <?php if (!empty($photos)): ?>
                <?php foreach ($photos as $photo): ?>
                <div class="gallery-item group relative overflow-hidden rounded-xl cursor-pointer" data-category="<?= e($photo->category->slug ?? 'all') ?>">
                    <div class="aspect-square bg-gray-200">
                        <img src="/uploads/gallery/<?= e($photo->image) ?>" alt="<?= e($photo->title) ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                    </div>
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition duration-300 flex items-center justify-center">
                        <span class="text-white font-bold opacity-0 group-hover:opacity-100 transition"><?= e($photo->title) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-500 text-lg">No photos available yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.querySelectorAll('.gallery-filter').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.gallery-filter').forEach(b => {
            b.classList.remove('bg-blue-600', 'text-white');
            b.classList.add('bg-gray-200', 'text-gray-700');
        });
        this.classList.remove('bg-gray-200', 'text-gray-700');
        this.classList.add('bg-blue-600', 'text-white');

        const category = this.dataset.category;
        document.querySelectorAll('.gallery-item').forEach(item => {
            if (category === 'all' || item.dataset.category === category) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>