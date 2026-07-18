<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::find(1);
auth()->login($user);
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

function testGet(string $uri, string $name, \Illuminate\Contracts\Http\Kernel $kernel, $user): void {
    try {
        $req = \Illuminate\Http\Request::create($uri, 'GET');
        $req->setUserResolver(fn () => $user);
        $response = $kernel->handle($req);
        $status = $response->getStatusCode();
        $len = strlen($response->getContent());
        $hasError = str_contains($response->getContent(), 'Exception') || str_contains($response->getContent(), 'Whoops') || str_contains($response->getContent(), 'error-page');
        $flag = $status === 200 && !$hasError ? 'OK' : "FAIL($status)";
        echo sprintf("%-45s %s  %dB\n", $name, $flag, $len);
    } catch (\Throwable $e) {
        echo sprintf("%-45s EXCEPTION  %s\n", $name, substr($e->getMessage(), 0, 60));
    }
}

echo "=== Dashboard & Core ===\n";
testGet('/dashboard', 'Dashboard home', $kernel, $user);
testGet('/dashboard/attendance', 'Attendance list', $kernel, $user);
testGet('/dashboard/attendance/create', 'Attendance create', $kernel, $user);
testGet('/dashboard/attendance/bulk', 'Attendance bulk', $kernel, $user);
testGet('/dashboard/staff-attendance', 'Staff attendance', $kernel, $user);
testGet('/dashboard/staff-attendance/report', 'Staff attendance report', $kernel, $user);

echo "\n=== Students & Teachers ===\n";
testGet('/dashboard/students', 'Students list', $kernel, $user);
testGet('/dashboard/teachers', 'Teachers list', $kernel, $user);
testGet('/dashboard/staff', 'Staff directory', $kernel, $user);
testGet('/dashboard/classes', 'Classes', $kernel, $user);
testGet('/dashboard/parents', 'Parents/Guardians', $kernel, $user);

echo "\n=== Fees & Finance ===\n";
testGet('/dashboard/fees', 'Fees list', $kernel, $user);
testGet('/dashboard/fees/create', 'Fee create', $kernel, $user);
testGet('/dashboard/expenses', 'Expenses list', $kernel, $user);
testGet('/dashboard/expenses/create', 'Expense create', $kernel, $user);
testGet('/dashboard/ledger', 'Ledger', $kernel, $user);
testGet('/dashboard/ledger/journal', 'Journal entry', $kernel, $user);
testGet('/dashboard/ledger/cashbook', 'Cashbook', $kernel, $user);
testGet('/dashboard/ledger/bankbook', 'Bankbook', $kernel, $user);
testGet('/dashboard/reports/income-statement', 'Income statement', $kernel, $user);
testGet('/dashboard/reports/balance-sheet', 'Balance sheet', $kernel, $user);
testGet('/dashboard/reports/cash-flow', 'Cash flow', $kernel, $user);
testGet('/dashboard/reports', 'Reports home', $kernel, $user);

echo "\n=== HR ===\n";
testGet('/dashboard/leaves', 'Leaves list', $kernel, $user);
testGet('/dashboard/leaves/create', 'Leave create', $kernel, $user);
testGet('/dashboard/payroll/structures', 'Salary structures', $kernel, $user);
testGet('/dashboard/payroll/generate', 'Generate payslips', $kernel, $user);
testGet('/dashboard/payroll/payslips', 'Payslips', $kernel, $user);

echo "\n=== Transport ===\n";
testGet('/dashboard/transport/vehicles', 'Vehicles', $kernel, $user);
testGet('/dashboard/transport/vehicles/create', 'Vehicle create', $kernel, $user);
testGet('/dashboard/transport/routes', 'Routes', $kernel, $user);
testGet('/dashboard/transport/routes/create', 'Route create', $kernel, $user);
testGet('/dashboard/transport/assignments', 'Assignments', $kernel, $user);

echo "\n=== SMS & Communication ===\n";
testGet('/dashboard/sms', 'SMS campaigns', $kernel, $user);
testGet('/dashboard/sms/compose', 'SMS compose', $kernel, $user);
testGet('/dashboard/sms/templates', 'SMS templates', $kernel, $user);
testGet('/dashboard/notifications', 'Notifications', $kernel, $user);
testGet('/dashboard/notifications/preferences', 'Notification prefs', $kernel, $user);

echo "\n=== Admissions ===\n";
testGet('/dashboard/admissions', 'Admissions list', $kernel, $user);
testGet('/dashboard/exams', 'Exams', $kernel, $user);
testGet('/dashboard/exams/create', 'Exam create', $kernel, $user);
testGet('/dashboard/events', 'Events', $kernel, $user);
testGet('/dashboard/events/create', 'Event create', $kernel, $user);

echo "\n=== CMS & Website ===\n";
testGet('/dashboard/cms/pages', 'CMS pages', $kernel, $user);
testGet('/dashboard/news', 'News', $kernel, $user);
testGet('/dashboard/news/create', 'News create', $kernel, $user);
testGet('/dashboard/gallery', 'Gallery', $kernel, $user);
testGet('/dashboard/announcements', 'Announcements', $kernel, $user);
testGet('/dashboard/documents', 'Documents', $kernel, $user);
testGet('/dashboard/contact-submissions', 'Contact submissions', $kernel, $user);

echo "\n=== System ===\n";
testGet('/dashboard/settings', 'Settings', $kernel, $user);
testGet('/dashboard/backup', 'Backups', $kernel, $user);
testGet('/dashboard/activity', 'Activity log', $kernel, $user);
testGet('/dashboard/bulk', 'Bulk import/export', $kernel, $user);

echo "\n=== Public Site ===\n";
testGet('/', 'Public home', $kernel, $user);
testGet('/about', 'Public about', $kernel, $user);
testGet('/academics', 'Public academics', $kernel, $user);
testGet('/admissions', 'Public admissions', $kernel, $user);
testGet('/admissions/apply', 'Public apply', $kernel, $user);
testGet('/admissions/status', 'Public status', $kernel, $user);
testGet('/transport', 'Public transport', $kernel, $user);
testGet('/results', 'Public results', $kernel, $user);
testGet('/news', 'Public news', $kernel, $user);
testGet('/gallery', 'Public gallery', $kernel, $user);
testGet('/events', 'Public events', $kernel, $user);
testGet('/contact', 'Public contact', $kernel, $user);
testGet('/faculty', 'Public faculty', $kernel, $user);
testGet('/robots.txt', 'Robots', $kernel, $user);
testGet('/sitemap.xml', 'Sitemap', $kernel, $user);