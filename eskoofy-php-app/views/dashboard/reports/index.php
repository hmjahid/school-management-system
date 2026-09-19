<?php $pageTitle = 'Reports'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Reports</h1>
</div>

<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
    <a href="/dashboard/reports/students" class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition text-center">
        <div class="text-4xl mb-3">👨‍🎓</div>
        <h3 class="font-bold text-lg">Student Reports</h3>
        <p class="text-gray-500 text-sm mt-1">Student lists, demographics, and analytics</p>
    </a>
    <a href="/dashboard/reports/fees" class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition text-center">
        <div class="text-4xl mb-3">💰</div>
        <h3 class="font-bold text-lg">Fee Reports</h3>
        <p class="text-gray-500 text-sm mt-1">Collection summary, outstanding fees, and revenue</p>
    </a>
    <a href="/dashboard/reports/attendance" class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition text-center">
        <div class="text-4xl mb-3">✅</div>
        <h3 class="font-bold text-lg">Attendance Reports</h3>
        <p class="text-gray-500 text-sm mt-1">Daily, monthly, and yearly attendance analysis</p>
    </a>
    <a href="/dashboard/reports/exams" class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition text-center">
        <div class="text-4xl mb-3">📝</div>
        <h3 class="font-bold text-lg">Exam Reports</h3>
        <p class="text-gray-500 text-sm mt-1">Results analysis, pass rates, and grade distribution</p>
    </a>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>