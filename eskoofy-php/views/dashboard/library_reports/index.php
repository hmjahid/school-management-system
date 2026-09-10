<?php $pageTitle = 'Library Reports'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Library Reports</h1>
</div>

<div class="grid md:grid-cols-3 gap-6">
    <a href="/dashboard/library-reports/currently-issued" class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition">
        <div class="text-3xl mb-2">📖</div>
        <h3 class="font-bold">Currently Issued</h3>
        <p class="text-sm text-gray-500">Books currently checked out</p>
    </a>
    <a href="/dashboard/library-reports/overdue" class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition">
        <div class="text-3xl mb-2">⏰</div>
        <h3 class="font-bold">Overdue</h3>
        <p class="text-sm text-gray-500">Books past due date</p>
    </a>
    <a href="/dashboard/library-reports/history" class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition">
        <div class="text-3xl mb-2">📚</div>
        <h3 class="font-bold">Issue History</h3>
        <p class="text-sm text-gray-500">Complete history of issues</p>
    </a>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
