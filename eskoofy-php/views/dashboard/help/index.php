<?php $pageTitle = 'Help'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Help &amp; Documentation</h1>
    <p class="text-gray-500">Quick tips for using Eskoofy</p>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-bold mb-2">📚 Getting Started</h3>
        <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
            <li>Run the Setup Checklist from the Onboarding page.</li>
            <li>Configure school info in Settings.</li>
            <li>Add academic sessions, classes, and subjects.</li>
            <li>Invite teachers and add students.</li>
        </ul>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-bold mb-2">👨‍🎓 Students</h3>
        <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
            <li>Add students individually or use Bulk Import.</li>
            <li>Assign them to a class and section.</li>
            <li>Roll numbers and admission numbers must be unique.</li>
        </ul>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-bold mb-2">💰 Fees &amp; Payments</h3>
        <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
            <li>Create fee structures per class.</li>
            <li>Enable payment gateways in Settings → Payment.</li>
            <li>Parents can pay online via the public Payments page.</li>
        </ul>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-bold mb-2">📊 Reports</h3>
        <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
            <li>Use Reports → Students for class lists.</li>
            <li>Reports → Fees for collection summaries.</li>
            <li>Export all reports as CSV.</li>
        </ul>
    </div>
</div>

<div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-6 text-center">
    <p class="text-blue-700">Need more help? Contact <a href="mailto:support@eskoofy.test" class="font-bold underline">support@eskoofy.test</a></p>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>
