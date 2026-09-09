<?php $currentUser = auth()->user(); ?>
<aside class="fixed left-0 top-0 h-full w-64 bg-gray-900 text-white overflow-y-auto">
    <div class="p-4 border-b border-gray-700">
        <a href="/dashboard" class="text-lg font-bold"><?= e(config('school.name', 'School')) ?></a>
        <p class="text-xs text-gray-400 mt-1">Admin Panel</p>
    </div>
    <nav class="p-4">
        <ul class="space-y-1">
            <li><a href="/dashboard" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📊 Dashboard</a></li>
            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3">Academic</span></li>
            <li><a href="/dashboard/students" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">👨‍🎓 Students</a></li>
            <li><a href="/dashboard/teachers" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">👨‍🏫 Teachers</a></li>
            <li><a href="/dashboard/classes" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🏫 Classes</a></li>
            <li><a href="/dashboard/sections" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📋 Sections</a></li>
            <li><a href="/dashboard/subjects" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📚 Subjects</a></li>
            <li><a href="/dashboard/batches" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🎓 Batches</a></li>
            <li><a href="/dashboard/academic-sessions" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📅 Sessions</a></li>

            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3">Management</span></li>
            <li><a href="/dashboard/attendance" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">✅ Attendance</a></li>
            <li><a href="/dashboard/exams" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📝 Exams</a></li>
            <li><a href="/dashboard/fees" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">💰 Fees</a></li>
            <li><a href="/dashboard/fee-payments" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">💳 Fee Payments</a></li>
            <li><a href="/dashboard/admissions" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📄 Admissions</a></li>
            <li><a href="/dashboard/routines" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📆 Routines</a></li>
            <li><a href="/dashboard/assignments" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📝 Assignments</a></li>

            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3">Content</span></li>
            <li><a href="/dashboard/notices" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📢 Notices</a></li>
            <li><a href="/dashboard/news" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📰 News</a></li>
            <li><a href="/dashboard/events" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🎉 Events</a></li>
            <li><a href="/dashboard/announcements" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📣 Announcements</a></li>
            <li><a href="/dashboard/galleries" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🖼️ Gallery</a></li>
            <li><a href="/dashboard/testimonials" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">⭐ Testimonials</a></li>

            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3">Finance</span></li>
            <li><a href="/dashboard/expenses" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">💸 Expenses</a></li>
            <li><a href="/dashboard/payments" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🔗 Payments</a></li>
            <li><a href="/dashboard/payment-gateways" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">⚙️ Gateways</a></li>
            <li><a href="/dashboard/expense-categories" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📂 Expense Categories</a></li>

            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3">Facilities</span></li>
            <li><a href="/dashboard/transport" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🚌 Transport</a></li>
            <li><a href="/dashboard/vehicles" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🚗 Vehicles</a></li>
            <li><a href="/dashboard/hostels" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🏠 Hostel</a></li>
            <li><a href="/dashboard/library" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📖 Library</a></li>

            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3">Communication</span></li>
            <li><a href="/dashboard/sms" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📱 SMS</a></li>

            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3">Reports & Documents</span></li>
            <li><a href="/dashboard/reports" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📊 Reports</a></li>
            <li><a href="/dashboard/certificates" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">📜 Certificates</a></li>
            <li><a href="/dashboard/admit-cards" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🎫 Admit Cards</a></li>
            <li><a href="/dashboard/id-cards" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🪪 ID Cards</a></li>

            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3">HR & Payroll</span></li>
            <li><a href="/dashboard/payroll/salary-structures" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">💼 Salary Structures</a></li>
            <li><a href="/dashboard/payroll/payslips" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🧾 Payslips</a></li>
            <li><a href="/dashboard/payroll/leave-requests" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🏖️ Leave Requests</a></li>
            <li><a href="/dashboard/payroll/staff-attendance" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">👥 Staff Attendance</a></li>

            <li class="pt-2"><span class="text-xs text-gray-500 uppercase tracking-wider px-3">System</span></li>
            <li><a href="/dashboard/guardians" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">👨‍👩‍👧 Guardians</a></li>
            <li><a href="/dashboard/committee" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🏛️ Committee</a></li>
            <li><a href="/dashboard/users" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">👤 Users</a></li>
            <li><a href="/dashboard/roles" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">🔑 Roles</a></li>
            <li><a href="/dashboard/settings" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">⚙️ Settings</a></li>
            <li><a href="/dashboard/backup" class="flex items-center px-3 py-2 text-sm rounded hover:bg-gray-800">💾 Backup</a></li>
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
                <p class="text-sm font-medium text-gray-800"><?= e($currentUser->name ?? 'Admin') ?></p>
                <p class="text-xs text-gray-500"><?= e($currentUser->role ?? 'Administrator') ?></p>
            </div>
            <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">
                <?= strtoupper(substr($currentUser->name ?? 'A', 0, 1)) ?>
            </div>
            <a href="/logout" class="text-gray-500 hover:text-red-600" title="Logout">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
            </a>
        </div>
    </div>
</header>