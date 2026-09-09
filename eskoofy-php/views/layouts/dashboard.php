<!DOCTYPE html>
<html lang="<?= e(config('app.locale', 'en')) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Dashboard') ?> - <?= e(config('school.name', 'School')) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../partials/dashboard_header.php'; ?>

    <div class="ml-64 p-6">
        <?php include __DIR__ . '/../partials/flash_messages.php'; ?>
        <?= $content ?? '' ?>
    </div>

    <script src="/assets/js/app.js"></script>
</body>
</html>