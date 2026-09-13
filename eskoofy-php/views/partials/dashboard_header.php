<?php $currentUser = auth()->user() ?? []; ?>
<?php $ui = static fn (string $key, string $fallback): string => (string) dashboard_ui($key, $fallback); ?>
<aside class="fixed left-0 top-0 h-full w-64 bg-gray-900 text-white overflow-y-auto">
    <div class="p-4 border-b border-gray-700">
        <a href="/dashboard" class="text-lg font-bold"><?= e(config('school.name', 'School')) ?></a>
        <p class="text-xs text-gray-400 mt-1"><?= e($ui('admin_panel', 'Admin Panel')) ?></p>
    </div>
    <nav class="p-4">
        <ul class="space-y-1">
            <li><a href="/dashboard" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📊 <?= e($ui('dashboard', 'Dashboard')) ?></a></li>
            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3"><?= e($ui('academic', 'Academic')) ?></span></li>
            <li><a href="/dashboard/students" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">👨‍🎓 <?= e($ui('students', 'Students')) ?></a></li>
            <li><a href="/dashboard/teachers" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">👨‍🏫 <?= e($ui('teachers', 'Teachers')) ?></a></li>
            <li><a href="/dashboard/classes" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🏫 <?= e($ui('classes', 'Classes')) ?></a></li>
            <li><a href="/dashboard/sections" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📋 <?= e($ui('sections', 'Sections')) ?></a></li>
            <li><a href="/dashboard/subjects" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📚 <?= e($ui('subjects', 'Subjects')) ?></a></li>
            <li><a href="/dashboard/batches" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🎓 <?= e($ui('batches', 'Batches')) ?></a></li>
            <li><a href="/dashboard/academic-sessions" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📅 <?= e($ui('sessions', 'Sessions')) ?></a></li>

            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3"><?= e($ui('management', 'Management')) ?></span></li>
            <li><a href="/dashboard/attendance" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">✅ <?= e($ui('attendance', 'Attendance')) ?></a></li>
            <li><a href="/dashboard/exams" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📝 <?= e($ui('exams', 'Exams')) ?></a></li>
            <li><a href="/dashboard/fees" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">💰 <?= e($ui('fees', 'Fees')) ?></a></li>
            <li><a href="/dashboard/fee-payments" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">💳 <?= e($ui('fee_payments', 'Fee Payments')) ?></a></li>
            <li><a href="/dashboard/admissions" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📄 <?= e($ui('admissions', 'Admissions')) ?></a></li>
            <li><a href="/dashboard/routines" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📆 <?= e($ui('class_routine', 'Routines')) ?></a></li>
            <li><a href="/dashboard/assignments" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📝 <?= e($ui('assignments', 'Assignments')) ?></a></li>

            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3"><?= e($ui('content', 'Content')) ?></span></li>
            <li><a href="/dashboard/notices" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📢 <?= e($ui('notices', 'Notices')) ?></a></li>
            <li><a href="/dashboard/news" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📰 <?= e($ui('news', 'News')) ?></a></li>
            <li><a href="/dashboard/events" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🎉 <?= e($ui('events', 'Events')) ?></a></li>
            <li><a href="/dashboard/announcements" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📣 <?= e($ui('announcements', 'Announcements')) ?></a></li>
            <li><a href="/dashboard/gallery" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🖼️ <?= e($ui('gallery', 'Gallery')) ?></a></li>
            <li><a href="/dashboard/testimonials" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">⭐ <?= e($ui('testimonials', 'Testimonials')) ?></a></li>

            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3"><?= e($ui('finance', 'Finance')) ?></span></li>
            <li><a href="/dashboard/expenses" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">💸 <?= e($ui('expenses', 'Expenses')) ?></a></li>
            <li><a href="/dashboard/payments" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🔗 <?= e($ui('payments', 'Payments')) ?></a></li>
            <li><a href="/dashboard/payment-gateways" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">⚙️ <?= e($ui('payment_gateways', 'Gateways')) ?></a></li>
            <li><a href="/dashboard/expense-categories" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📂 <?= e($ui('expense_categories', 'Expense Categories')) ?></a></li>

            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3"><?= e($ui('facilities', 'Facilities')) ?></span></li>
            <li><a href="/dashboard/transport/vehicles" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🚌 <?= e($ui('transport', 'Transport')) ?></a></li>
            <li><a href="/dashboard/vehicles" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🚗 <?= e($ui('vehicles', 'Vehicles')) ?></a></li>
            <li><a href="/dashboard/hostels" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🏠 <?= e($ui('hostels', 'Hostel')) ?></a></li>
            <li><a href="/dashboard/books" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📖 <?= e($ui('library', 'Library')) ?></a></li>

            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3"><?= e($ui('communications', 'Communication')) ?></span></li>
            <li><a href="/dashboard/sms" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📱 <?= e($ui('bulk_sms', 'SMS')) ?></a></li>

            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3"><?= e($ui('reports_documents', 'Reports & Documents')) ?></span></li>
            <li><a href="/dashboard/reports" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📊 <?= e($ui('reports', 'Reports')) ?></a></li>
            <li><a href="/dashboard/certificates" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📜 <?= e($ui('certificates', 'Certificates')) ?></a></li>
            <li><a href="/dashboard/admit-cards" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🎫 <?= e($ui('admit_cards', 'Admit Cards')) ?></a></li>
            <li><a href="/dashboard/id-cards" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🪪 <?= e($ui('student_id_cards', 'ID Cards')) ?></a></li>

            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3"><?= e($ui('payroll', 'HR & Payroll')) ?></span></li>
            <li><a href="/dashboard/salary-structures" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">💼 <?= e($ui('salary_structures', 'Salary Structures')) ?></a></li>
            <li><a href="/dashboard/payslips" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🧾 <?= e($ui('payslips', 'Payslips')) ?></a></li>
            <li><a href="/dashboard/leave-requests" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🏖️ <?= e($ui('leaves', 'Leave Requests')) ?></a></li>
            <li><a href="/dashboard/staff-attendance" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">👥 <?= e($ui('staff_attendance', 'Staff Attendance')) ?></a></li>

            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3"><?= e($ui('system', 'System')) ?></span></li>
            <li><a href="/dashboard/onboarding" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🚀 <?= e($ui('onboarding', 'Onboarding')) ?></a></li>
            <li><a href="/dashboard/cms" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📝 <?= e($ui('website_cms', 'CMS Pages')) ?></a></li>
            <li><a href="/dashboard/contact-submissions" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">✉️ <?= e($ui('inbox', 'Inbox')) ?></a></li>
            <li><a href="/dashboard/documents" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📂 <?= e($ui('documents', 'Documents')) ?></a></li>
            <li><a href="/dashboard/media" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🖼️ <?= e($ui('media_library', 'Media')) ?></a></li>
            <li><a href="/dashboard/bulk" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📤 <?= e($ui('bulk_import_export', 'Bulk Import/Export')) ?></a></li>
            <li><a href="/dashboard/communications" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📡 <?= e($ui('communications', 'Communications')) ?></a></li>
            <li><a href="/dashboard/help" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">❓ <?= e($ui('help_documentation', 'Help')) ?></a></li>
            <li><a href="/dashboard/permissions" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🔐 <?= e($ui('permissions', 'Permissions')) ?></a></li>
            <li><a href="/dashboard/bank-reconciliation" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🏦 <?= e($ui('bank_reconciliation', 'Bank Recon')) ?></a></li>
            <li><a href="/dashboard/progress-reports" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📊 <?= e($ui('progress_reports', 'Progress Reports')) ?></a></li>
            <li><a href="/dashboard/seat-plans" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🪑 <?= e($ui('seat_plans', 'Seat Plans')) ?></a></li>
            <li><a href="/dashboard/library-reports" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📚 <?= e($ui('library_reports', 'Library Reports')) ?></a></li>
            <li><a href="/dashboard/careers" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">💼 <?= e($ui('careers', 'Careers')) ?></a></li>
            <li><a href="/dashboard/guardians" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">👨‍👩‍👧 <?= e($ui('parents', 'Guardians')) ?></a></li>
            <li><a href="/dashboard/committee" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🏛️ <?= e($ui('committee_members', 'Committee')) ?></a></li>
            <li><a href="/dashboard/users" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">👤 <?= e($ui('users', 'Users')) ?></a></li>
            <li><a href="/dashboard/roles" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🔑 <?= e($ui('roles', 'Roles')) ?></a></li>
            <li><a href="/dashboard/settings" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">⚙️ <?= e($ui('school_settings', 'Settings')) ?></a></li>
            <li><a href="/dashboard/backups" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">💾 <?= e($ui('backups', 'Backup')) ?></a></li>
        </ul>
    </nav>
</aside>

<header class="ml-64 bg-white shadow-sm sticky top-0 z-40">
    <div class="flex justify-between items-center px-6 py-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-800"><?= e($pageTitle ?? 'Dashboard') ?></h1>
        </div>
        <div class="flex items-center space-x-4">
            <div class="text-right">
                <p class="text-sm font-medium text-gray-800"><?= e($currentUser['name'] ?? 'Admin') ?></p>
                <p class="text-xs text-gray-500"><?= e($currentUser['role'] ?? 'Administrator') ?></p>
            </div>
            <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">
                <?= strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)) ?>
            </div>
            <!-- Language Switcher -->
            <div class="flex items-center space-x-1 text-sm">
                <?php
                $supportedLocales = config('school.supported_locales', ['en', 'bn']);
                $currentLocale = current_locale();
                $localeLabels = ['en' => 'EN', 'bn' => 'বাং'];
                foreach ($supportedLocales as $loc):
                    $label = $localeLabels[$loc] ?? strtoupper($loc);
                    $isActive = ($loc === $currentLocale);
                ?>
                    <a href="/dashboard/locale/<?= e($loc) ?>" class="<?= $isActive ? 'text-blue-600 font-semibold' : 'text-gray-500 hover:text-blue-600' ?> px-2 py-1 rounded hover:bg-gray-100">
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <a href="/logout" class="text-gray-500 hover:text-red-600" title="Logout">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
            </a>
        </div>
    </div>
</header>