@php
    $schoolName = $siteSettings?->localized_school_name ?? config('app.name', 'SchoolEase');
    $schoolTagline = $siteSettings?->localized_tagline ?? __('School management');
    $logoUrl = $siteSettings?->logo_url;
@endphp

<div class="flex h-[4.25rem] flex-shrink-0 items-center gap-3 border-b border-slate-200/80 px-4">
    <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="" class="h-9 w-9 shrink-0 rounded-lg object-cover ring-1 ring-slate-200">
        @else
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white shadow-sm">
                {{ strtoupper(substr($schoolName, 0, 1)) }}
            </span>
        @endif
        <div class="min-w-0">
            <p class="truncate text-sm font-bold text-slate-900">{{ $schoolName }}</p>
            <p class="truncate text-[0.65rem] font-medium uppercase tracking-wide text-slate-500">{{ __('Admin panel') }}</p>
        </div>
    </a>
</div>

<nav class="admin-sidebar-nav flex flex-1 flex-col overflow-y-auto px-3 py-4">
    <p class="mb-2 px-3 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400">{{ __('Main') }}</p>
    <div class="space-y-0.5">
        <x-admin-nav-link :href="route('dashboard')" route-is="dashboard" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z\'/></svg>'">
            {{ __('Dashboard') }}
        </x-admin-nav-link>

        @php
            $u = auth()->user();
            $canFees = $u && ($u->hasAnyRole(['admin', 'accountant']) || $u->hasAnyPermission(['collect_fees', 'view_financial_reports', 'manage_fee_categories', 'manage_fee_types']));
        @endphp

        <p class="mb-2 mt-5 px-3 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400">{{ __('Academic') }}</p>

        @can('viewAny', App\Models\Student::class)
            <x-admin-nav-link :href="route('dashboard.students')" route-is="dashboard.students*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z\'/></svg>'">{{ __('Students') }}</x-admin-nav-link>
        @endcan
        @can('viewAny', App\Models\Teacher::class)
            <x-admin-nav-link :href="route('dashboard.teachers')" route-is="dashboard.teachers*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z\'/></svg>'">{{ __('Teachers') }}</x-admin-nav-link>
        @endcan
        @can('viewAny', App\Models\Guardian::class)
            <x-admin-nav-link :href="route('dashboard.parents')" route-is="dashboard.parents*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z\' clip-rule=\'evenodd\'/></svg>'">{{ __('Parents') }}</x-admin-nav-link>
        @endcan
        @can('viewAny', App\Models\SchoolClass::class)
            <x-admin-nav-link :href="route('dashboard.classes')" route-is="dashboard.classes*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z\'/></svg>'">{{ __('Classes') }}</x-admin-nav-link>
        @endcan
        @can('viewAny', App\Models\Attendance::class)
            <x-admin-nav-link :href="route('dashboard.attendance')" route-is="dashboard.attendance*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z\' clip-rule=\'evenodd\'/></svg>'">{{ __('Attendance') }}</x-admin-nav-link>
            <x-admin-nav-link :href="route('dashboard.attendance.bulk')" route-is="dashboard.attendance.bulk*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M9 2a1 1 0 000 2h2a1 1 0 100-2H9z\'/><path fill-rule=\'evenodd\' d=\'M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z\' clip-rule=\'evenodd\'/></svg>'" class="pl-9">{{ __('Bulk mark') }}</x-admin-nav-link>
        @endcan
        @can('manage_teacher_attendance')
            <x-admin-nav-link :href="route('dashboard.staff-attendance.index')" route-is="dashboard.staff-attendance*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a7 7 0 017 7H1v-1a7 7 0 015-6.7z\'/></svg>'">{{ __('Staff attendance') }}</x-admin-nav-link>
        @endcan
        @can('send_bulk_sms')
            <x-admin-nav-link :href="route('dashboard.sms.index')" route-is="dashboard.sms*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0L9 8.586l4.293-4.293a1 1 0 111.414 1.414L10.414 10l4.293 4.293a1 1 0 01-1.414 1.414L9 11.414l-2.293 2.293a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414z\'/></svg>'">{{ __('Bulk SMS') }}</x-admin-nav-link>
        @endcan
        @if(auth()->user()->hasAnyRole(['admin','staff','teacher']))
            <x-admin-nav-link :href="route('dashboard.leaves.index')" route-is="dashboard.leaves*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z\' clip-rule=\'evenodd\'/></svg>'">{{ __('Leaves') }}</x-admin-nav-link>
        @endif
        @can('view_teacher_salaries')
            <x-admin-nav-link :href="route('dashboard.payroll.payslips')" route-is="dashboard.payroll.*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z\'/><path fill-rule=\'evenodd\' d=\'M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z\' clip-rule=\'evenodd\'/></svg>'">{{ __('Payroll') }}</x-admin-nav-link>
        @endcan
        @if(auth()->user()?->hasRole('admin'))
            <x-admin-nav-link :href="route('dashboard.staff')" route-is="dashboard.staff" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a7 7 0 017 7H1v-1a7 7 0 015-6.7z\'/></svg>'">{{ __('Staff directory') }}</x-admin-nav-link>
        @endif
        @if (auth()->user()?->can('view_admissions'))
            <x-admin-nav-link :href="route('dashboard.admissions.index')" route-is="dashboard.admissions*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M7 2a1 1 0 00-1 1v1H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H8V3a1 1 0 00-1-1z\'/><path d=\'M6 10a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1z\'/></svg>'">{{ __('Admissions') }}</x-admin-nav-link>
        @endif
        @can('viewAny', App\Models\Exam::class)
            <x-admin-nav-link :href="route('dashboard.exams')" route-is="dashboard.exams*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z\' clip-rule=\'evenodd\'/></svg>'">{{ __('Exams') }}</x-admin-nav-link>
        @endcan
        @can('viewAny', App\Models\Event::class)
            <x-admin-nav-link :href="route('dashboard.events')" route-is="dashboard.events*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z\' clip-rule=\'evenodd\'/></svg>'">{{ __('Events') }}</x-admin-nav-link>
        @endcan
        @if ($canFees)
            <x-admin-nav-link :href="route('dashboard.fees')" route-is="dashboard.fees*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z\'/><path fill-rule=\'evenodd\' d=\'M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z\' clip-rule=\'evenodd\'/></svg>'">{{ __('Fees') }}</x-admin-nav-link>
            @can('manage_vehicles')
                <x-admin-nav-link :href="route('dashboard.transport.vehicles.index')" route-is="dashboard.transport.*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z\'/><path d=\'M3 4a1 1 0 00-1 1v3a1 1 0 001 1h1l1.6 4.4A2 2 0 007.4 15h7.2a2 2 0 001.8-1.6L17 9h2a1 1 0 100-2h-3.28l-.6-2H11v2h2.28l1.2 4H8.52L7.4 6.6A2 2 0 005.6 5H3z\'/></svg>'">{{ __('Transport') }}</x-admin-nav-link>
            @endcan
            @can('manage_expenses')
                <x-admin-nav-link :href="route('dashboard.expenses.index')" route-is="dashboard.expenses*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M3 4a2 2 0 00-2 2v8a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2H3zm5 5a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z\' clip-rule=\'evenodd\'/></svg>'">{{ __('Expenses') }}</x-admin-nav-link>
            @endcan
            @can('manage_chart_of_accounts')
                <x-admin-nav-link :href="route('dashboard.ledger.index')" route-is="dashboard.ledger*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-2a1 1 0 01-1-1V6H6a2 2 0 01-2-2z\'/></svg>'">{{ __('Ledger') }}</x-admin-nav-link>
                <x-admin-nav-link :href="route('dashboard.reports.income-statement')" route-is="dashboard.reports.income-statement" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z\'/></svg>'">{{ __('Income statement') }}</x-admin-nav-link>
                <x-admin-nav-link :href="route('dashboard.reports.balance-sheet')" route-is="dashboard.reports.balance-sheet" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M3 3h14v3H3V3zm0 5h14v3H3V8zm0 5h14v3H3v-3z\'/></svg>'">{{ __('Balance sheet') }}</x-admin-nav-link>
                <x-admin-nav-link :href="route('dashboard.reports.cash-flow')" route-is="dashboard.reports.cash-flow" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M5 3a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2H5zm9 4a1 1 0 10-2 0v3.586L9.707 8.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L14 10.586V7z\' clip-rule=\'evenodd\'/></svg>'">{{ __('Cash flow') }}</x-admin-nav-link>
            @endcan
        @endif

        @if ($isAdmin ?? auth()->user()?->hasRole('admin'))
            <p class="mb-2 mt-5 px-3 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400">{{ __('System') }}</p>
            @can('view_audit_log')
                <x-admin-nav-link :href="route('dashboard.activity.index')" route-is="dashboard.activity*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M9 2a1 1 0 000 2h2a1 1 0 100-2H9z\'/><path fill-rule=\'evenodd\' d=\'M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z\' clip-rule=\'evenodd\'/></svg>'">{{ __('Activity log') }}</x-admin-nav-link>
            @endcan
            @can('backup_database')
                <x-admin-nav-link :href="route('dashboard.backup.index')" route-is="dashboard.backup*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z\'/></svg>'">{{ __('Backups') }}</x-admin-nav-link>
            @endcan

            <p class="mb-2 mt-5 px-3 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400">{{ __('Website') }}</p>

            <details class="group" @if (request()->routeIs('dashboard.cms.*') || request()->routeIs('dashboard.contact-submissions') || request()->routeIs('dashboard.news.*') || request()->routeIs('dashboard.gallery.*') || request()->routeIs('dashboard.announcements.*') || request()->routeIs('dashboard.documents.*')) open @endif>
                <summary class="admin-nav-link cursor-pointer list-none [&::-webkit-details-marker]:hidden {{ request()->routeIs('dashboard.cms.*') || request()->routeIs('dashboard.news.*') || request()->routeIs('dashboard.gallery.*') ? 'admin-nav-link--active' : '' }}">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center opacity-80">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.083 9h1.946c.089-1.546.383-2.97.837-4.118A6.004 6.004 0 004.083 9zM10 2a8 8 0 100 16 8 8 0 000-16zm0 2c-.076 0-.232.032-.465.262-.238.234-.497.623-.737 1.182-.389.907-.673 2.142-.766 3.556h3.936c-.093-1.414-.377-2.649-.766-3.556-.24-.56-.5-.948-.737-1.182C10.232 4.032 10.076 4 10 4zm3.971 5c-.089-1.546-.383-2.97-.837-4.118A6.004 6.004 0 0115.917 9h-1.946zm-2.003 2H8.032c.093 1.414.377 2.649.766 3.556.24.56.5.948.737 1.182.233.23.389.262.465.262.076 0 .232-.032.465-.262.238-.234.498-.623.737-1.182.389-.907.673-2.142.766-3.556zm1.166 4.118c.454-1.147.748-2.572.837-4.118h1.946a6.004 6.004 0 01-2.783 4.118zm-6.268 0C6.412 13.97 6.118 12.546 6.03 11H4.083a6.004 6.004 0 002.783 4.118z" clip-rule="evenodd"/></svg>
                    </span>
                    <span class="flex-1 truncate">{{ __('Website CMS') }}</span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </summary>
                <div class="ml-4 mt-1 space-y-0.5 border-l border-slate-200 pl-3">
                    <a href="{{ route('dashboard.cms.pages') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.cms.pages') ? 'font-semibold text-brand-700' : 'text-slate-600 hover:text-brand-600' }}">{{ __('All pages') }}</a>
                    <a href="{{ route('dashboard.news.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.news.*') ? 'font-semibold text-brand-700' : 'text-slate-600 hover:text-brand-600' }}">{{ __('News & events') }}</a>
                    <a href="{{ route('dashboard.gallery.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.gallery.*') ? 'font-semibold text-brand-700' : 'text-slate-600 hover:text-brand-600' }}">{{ __('Gallery') }}</a>
                    <a href="{{ route('dashboard.announcements.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.announcements.*') ? 'font-semibold text-brand-700' : 'text-slate-600 hover:text-brand-600' }}">{{ __('Announcements') }}</a>
                    <a href="{{ route('dashboard.documents.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.documents.*') ? 'font-semibold text-brand-700' : 'text-slate-600 hover:text-brand-600' }}">{{ __('Documents') }}</a>
                    <a href="{{ route('dashboard.contact-submissions') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.contact-submissions') ? 'font-semibold text-brand-700' : 'text-slate-600 hover:text-brand-600' }}">{{ __('Form submissions') }}</a>
                </div>
            </details>

            <p class="mb-2 mt-5 px-3 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400">{{ __('System') }}</p>

            <x-admin-nav-link :href="route('dashboard.settings')" route-is="dashboard.settings" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z\' clip-rule=\'evenodd\'/></svg>'">{{ __('School settings') }}</x-admin-nav-link>
            <x-admin-nav-link :href="route('dashboard.reports')" route-is="dashboard.reports*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z\'/></svg>'">{{ __('Reports') }}</x-admin-nav-link>
            <x-admin-nav-link :href="route('dashboard.bulk')" route-is="dashboard.bulk*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z\' clip-rule=\'evenodd\'/></svg>'">{{ __('Bulk import / export') }}</x-admin-nav-link>
        @endif
    </div>
</nav>

<div class="mt-auto border-t border-slate-200/80 p-4">
    <form method="post" action="{{ route('logout') }}">
        @csrf
        <x-button type="submit" variant="danger" class="w-full">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/></svg>
            {{ __('Logout') }}
        </x-button>
    </form>
</div>
