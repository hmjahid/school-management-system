<?php $pageTitle = 'Page Not Found'; ?>
<?php ob_start(); ?>
<div class="min-h-[60vh] flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-6xl font-bold text-gray-300 mb-4">404</h1>
        <h2 class="text-2xl font-semibold text-gray-700 mb-2">Page Not Found</h2>
        <p class="text-gray-500 mb-6">The page you're looking for doesn't exist or has been moved.</p>
        <a href="/" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">Go Home</a>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>