<!DOCTYPE html>
<html lang="<?= e(config('app.locale', 'en')) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Home') ?> - <?= e(config('school.name', 'School Name')) ?></title>
    <meta name="description" content="<?= e($metaDescription ?? config('school.description', '')) ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <main class="flex-1">
        <?= $content ?? '' ?>
    </main>

    <?php include __DIR__ . '/../partials/footer.php'; ?>

    <script src="/assets/js/app.js"></script>
</body>
</html>