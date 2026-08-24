@php
    $schoolName = $siteSettings?->localized_school_name ?? config('app.name', 'SchoolEase');
    $schoolTagline = $siteSettings?->localized_tagline ?? __('dashboard.admin_panel');
    $logoUrl = $siteSettings?->logo_url;
@endphp

<div class="flex h-[4.25rem] flex-shrink-0 items-center gap-3 border-b border-slate-200/80 px-4 dark:border-slate-700/80">
    <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $schoolName }}" class="h-9 w-9 shrink-0 rounded-lg object-cover ring-1 ring-slate-200 dark:ring-slate-600">
        @else
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white shadow-sm">
                {{ strtoupper(substr($schoolName, 0, 1)) }}
            </span>
        @endif
        <div class="min-w-0">
            @if (! $logoUrl)
                <p class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">{{ $schoolName }}</p>
            @endif
            <p class="truncate text-[0.65rem] font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('dashboard.admin_panel') }}</p>
        </div>
    </a>
</div>

<nav class="admin-sidebar-nav flex flex-1 flex-col overflow-y-auto px-3 py-4" data-no-loading>
    {{-- Collapsible search input --}}
    <div class="relative mb-4" data-sidebar-search>
        <button type="button" data-sidebar-search-toggle class="flex w-full items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-left text-sm text-slate-400 transition hover:border-slate-300 dark:border-slate-600 dark:bg-slate-700/50 dark:text-slate-500 dark:hover:border-slate-500">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <span class="flex-1 truncate">{{ __('dashboard.search_menu') }}</span>
        </button>
        <input type="text" data-sidebar-search-input placeholder="{{ __('dashboard.type_to_search') }}" class="hidden w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500">
    </div>

    <script>
    (function(){
        var toggle = document.querySelector('[data-sidebar-search-toggle]');
        var input = document.querySelector('[data-sidebar-search-input]');
        if (!toggle || !input) return;
        toggle.addEventListener('click', function(){
            toggle.classList.add('hidden');
            input.classList.remove('hidden');
            input.focus();
        });
        input.addEventListener('blur', function(){
            if (!input.value.trim()) {
                input.classList.add('hidden');
                toggle.classList.remove('hidden');
                input.value = '';
                filterSidebar('');
            }
        });
        input.addEventListener('keydown', function(e){
            if (e.key === 'Escape') {
                input.value = '';
                input.blur();
            }
        });
        input.addEventListener('input', function(){
            filterSidebar(this.value.toLowerCase().trim());
        });
        function filterSidebar(q) {
            var nav = document.querySelector('.admin-sidebar-nav');
            if (!nav) return;
            var items = nav.querySelectorAll('a, details');
            var headers = nav.querySelectorAll('p.text-\\[0\\.65rem\\]');
            items.forEach(function(item){
                if (!q) { item.style.display = ''; return; }
                var text = item.textContent.toLowerCase();
                item.style.display = text.includes(q) ? '' : 'none';
            });
            headers.forEach(function(h){
                if (!q) { h.style.display = ''; return; }
                var next = h.nextElementSibling;
                if (!next) {
                    var sibling = h.nextElementSibling;
                    var anyVisible = false;
                    while (sibling && !sibling.matches('p.text-\\[0\\.65rem\\]')) {
                        if (sibling.style.display !== 'none') anyVisible = true;
                        sibling = sibling.nextElementSibling;
                    }
                    h.style.display = anyVisible ? '' : 'none';
                    return;
                }
                var children = next.querySelectorAll(':scope > a, :scope > details');
                var anyVisible = false;
                children.forEach(function(c){ if (c.style.display !== 'none') anyVisible = true; });
                h.style.display = anyVisible ? '' : 'none';
            });
            var details = nav.querySelectorAll('details');
            details.forEach(function(d){
                if (q) {
                    var subItems = d.querySelectorAll('a');
                    var hasMatch = false;
                    subItems.forEach(function(a){ if (a.style.display !== 'none') hasMatch = true; });
                    if (hasMatch) { d.open = true; d.style.display = ''; }
                    else { d.style.display = 'none'; }
                } else {
                    d.style.display = '';
                }
            });
        }
    })();
    </script>

    <p class="mb-2 px-3 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ __('dashboard.main') }}</p>
    <div class="space-y-0.5">
        <x-admin-nav-link :href="route('dashboard')" route-is="dashboard" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z\'/></svg>'">
            {{ __('dashboard.dashboard') }}
        </x-admin-nav-link>

        <x-admin-nav-link :href="route('messages.index')" route-is="messages*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z\'/><path d=\'M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z\'/></svg>'">
            {{ __('dashboard.messages') }}
        </x-admin-nav-link>

        @can('send_bulk_sms')
            <x-admin-nav-link :href="route('dashboard.sms.index')" route-is="dashboard.sms*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0L9 8.586l4.293-4.293a1 1 0 111.414 1.414L10.414 10l4.293 4.293a1 1 0 01-1.414 1.414L9 11.414l-2.293 2.293a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414z\'/></svg>'">{{ __('dashboard.bulk_sms') }}</x-admin-nav-link>
        @endcan

        @php
            $u = auth()->user();
            $canFees = $u && ($u->hasAnyRole(['admin', 'accountant']) || $u->hasAnyPermission(['collect_fees', 'view_financial_reports', 'manage_fee_categories', 'manage_fee_types']));
        @endphp

        <p class="mb-2 mt-5 px-3 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ __('dashboard.academic') }}</p>

        {{-- People --}}
        <details class="group" @if (request()->routeIs('dashboard.students*') || request()->routeIs('dashboard.teachers*') || request()->routeIs('dashboard.parents*') || request()->routeIs('dashboard.staff') || request()->routeIs('dashboard.users*')) open @endif>
            <summary class="admin-nav-link cursor-pointer list-none [&::-webkit-details-marker]:hidden {{ request()->routeIs('dashboard.students*') || request()->routeIs('dashboard.teachers*') || request()->routeIs('dashboard.parents*') || request()->routeIs('dashboard.staff') || request()->routeIs('dashboard.users*') ? 'admin-nav-link--active' : '' }}">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center opacity-80">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/></svg>
                </span>
                <span class="flex-1 truncate">{{ __('dashboard.people') }}</span>
                <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-90 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </summary>
            <div class="ml-4 mt-1 space-y-0.5 border-l border-slate-200 pl-3 dark:border-slate-700">
                @can('viewAny', App\Models\Student::class)
                    <a href="{{ route('dashboard.students') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.students*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.students') }}</a>
                @endcan
                @can('viewAny', App\Models\Teacher::class)
                    <a href="{{ route('dashboard.teachers') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.teachers*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.teachers') }}</a>
                @endcan
                @if(auth()->user()?->hasAnyRole(['admin', 'accountant', 'teacher']))
                    <a href="{{ route('dashboard.parents') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.parents*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.parents') }}</a>
                @endif
                @if(auth()->user()?->hasAnyRole(['admin', 'staff']))
                    <a href="{{ route('dashboard.staff') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.staff') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.staff_directory') }}</a>
                @endif
                @if(auth()->user()?->hasAnyRole(['admin']))
                    <a href="{{ route('dashboard.users.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.users*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.all_users') }}</a>
                @endif
            </div>
        </details>

        {{-- Academics --}}
        <details class="group" @if (request()->routeIs('dashboard.classes*') || request()->routeIs('dashboard.exams*') || request()->routeIs('dashboard.assignments*') || request()->routeIs('dashboard.routines*')) open @endif>
            <summary class="admin-nav-link cursor-pointer list-none [&::-webkit-details-marker]:hidden {{ request()->routeIs('dashboard.classes*') || request()->routeIs('dashboard.exams*') || request()->routeIs('dashboard.assignments*') || request()->routeIs('dashboard.routines*') ? 'admin-nav-link--active' : '' }}">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center opacity-80">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg>
                </span>
                <span class="flex-1 truncate">{{ __('dashboard.academics') }}</span>
                <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-90 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </summary>
            <div class="ml-4 mt-1 space-y-0.5 border-l border-slate-200 pl-3 dark:border-slate-700">
                @can('viewAny', App\Models\SchoolClass::class)
                    <a href="{{ route('dashboard.classes') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.classes*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.classes') }}</a>
                @endcan
                @can('viewAny', App\Models\Exam::class)
                    <a href="{{ route('dashboard.exams') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.exams*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.exams') }}</a>
                @endcan
                @can('manage_assignments')
                    <a href="{{ route('dashboard.assignments.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.assignments*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.assignments') }}</a>
                @endcan
                @if(auth()->user()?->hasAnyRole(['admin', 'teacher']) || auth()->user()?->hasPermissionTo('routine-list'))
                    <a href="{{ route('dashboard.routines.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.routines*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.class_routine') }}</a>
                @endif
            </div>
        </details>

        @if (auth()->user()?->can('view_admissions'))
            <x-admin-nav-link :href="route('dashboard.admissions.index')" route-is="dashboard.admissions*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M7 2a1 1 0 00-1 1v1H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H8V3a1 1 0 00-1-1z\'/><path d=\'M6 10a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1z\'/></svg>'">{{ __('dashboard.admissions') }}</x-admin-nav-link>
        @endif

        {{-- Daily --}}
        <details class="group" @if (request()->routeIs('dashboard.attendance*') || request()->routeIs('dashboard.staff-attendance*')) open @endif>
            <summary class="admin-nav-link cursor-pointer list-none [&::-webkit-details-marker]:hidden {{ request()->routeIs('dashboard.attendance*') || request()->routeIs('dashboard.staff-attendance*') ? 'admin-nav-link--active' : '' }}">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center opacity-80">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                </span>
                <span class="flex-1 truncate">{{ __('dashboard.daily') }}</span>
                <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-90 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </summary>
            <div class="ml-4 mt-1 space-y-0.5 border-l border-slate-200 pl-3 dark:border-slate-700">
                @can('viewAny', App\Models\Attendance::class)
                    <a href="{{ route('dashboard.attendance') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.attendance') && !request()->routeIs('dashboard.attendance.bulk*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.attendance') }}</a>
                    <a href="{{ route('dashboard.attendance.bulk') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.attendance.bulk*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.bulk_mark') }}</a>
                @endcan
                @can('manage_teacher_attendance')
                    <a href="{{ route('dashboard.staff-attendance.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.staff-attendance*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.staff_attendance') }}</a>
                @endcan
            </div>
        </details>

        {{-- Finance --}}
        @if ($canFees)
            <details class="group" @if (request()->routeIs('dashboard.fees*') || request()->routeIs('dashboard.fee-payments*') || request()->routeIs('dashboard.expenses*') || request()->routeIs('dashboard.ledger*') || request()->routeIs('dashboard.reports.income-statement') || request()->routeIs('dashboard.reports.balance-sheet') || request()->routeIs('dashboard.reports.cash-flow')) open @endif>
                <summary class="admin-nav-link cursor-pointer list-none [&::-webkit-details-marker]:hidden {{ request()->routeIs('dashboard.fees*') || request()->routeIs('dashboard.fee-payments*') || request()->routeIs('dashboard.expenses*') || request()->routeIs('dashboard.ledger*') ? 'admin-nav-link--active' : '' }}">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center opacity-80">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg>
                    </span>
                    <span class="flex-1 truncate">{{ __('dashboard.finance') }}</span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-90 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </summary>
                <div class="ml-4 mt-1 space-y-0.5 border-l border-slate-200 pl-3 dark:border-slate-700">
                    <a href="{{ route('dashboard.fees') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.fees*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.fees') }}</a>
                    <a href="{{ route('dashboard.fee-payments.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.fee-payments*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.payments') }}</a>
                    @can('manage_expenses')
                        <a href="{{ route('dashboard.expenses.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.expenses*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.expenses') }}</a>
                    @endcan
                    @can('manage_chart_of_accounts')
                        <a href="{{ route('dashboard.ledger.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.ledger*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.ledger') }}</a>
                    @endcan
                </div>
            </details>
        @endif

        {{-- HR --}}
        <details class="group" @if (request()->routeIs('dashboard.leaves*') || request()->routeIs('dashboard.payroll*') || request()->routeIs('dashboard.staff')) open @endif>
            <summary class="admin-nav-link cursor-pointer list-none [&::-webkit-details-marker]:hidden {{ request()->routeIs('dashboard.leaves*') || request()->routeIs('dashboard.payroll*') || request()->routeIs('dashboard.staff') ? 'admin-nav-link--active' : '' }}">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center opacity-80">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a7 7 0 017 7H1v-1a7 7 0 015-6.7z"/></svg>
                </span>
                <span class="flex-1 truncate">{{ __('dashboard.hr') }}</span>
                <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-90 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </summary>
            <div class="ml-4 mt-1 space-y-0.5 border-l border-slate-200 pl-3 dark:border-slate-700">
                @if(auth()->user()->hasAnyRole(['admin','staff','teacher']))
                    <a href="{{ route('dashboard.leaves.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.leaves*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.leaves') }}</a>
                @endif
                @can('view_teacher_salaries')
                    <a href="{{ route('dashboard.payroll.payslips') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.payroll*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.payroll') }}</a>
                @endcan
                @if(auth()->user()?->hasRole('admin'))
                    <a href="{{ route('dashboard.staff') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.staff') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.staff_directory') }}</a>
                @endif
            </div>
        </details>

        {{-- Documents --}}
        <details class="group" @if (request()->routeIs('dashboard.admit-cards*') || request()->routeIs('dashboard.student-id-cards*') || request()->routeIs('dashboard.certificates*') || request()->routeIs('dashboard.testimonials*') || request()->routeIs('dashboard.committee*')) open @endif>
            <summary class="admin-nav-link cursor-pointer list-none [&::-webkit-details-marker]:hidden {{ request()->routeIs('dashboard.admit-cards*') || request()->routeIs('dashboard.student-id-cards*') || request()->routeIs('dashboard.certificates*') || request()->routeIs('dashboard.testimonials*') || request()->routeIs('dashboard.committee*') ? 'admin-nav-link--active' : '' }}">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center opacity-80">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                </span>
                <span class="flex-1 truncate">{{ __('dashboard.documents') }}</span>
                <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-90 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </summary>
            <div class="ml-4 mt-1 space-y-0.5 border-l border-slate-200 pl-3 dark:border-slate-700">
                @can('manage_admit_cards')
                    <a href="{{ route('dashboard.admit-cards.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.admit-cards*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.admit_cards') }}</a>
                @endcan
                @can('manage_student_id_cards')
                    <a href="{{ route('dashboard.student-id-cards.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.student-id-cards*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.student_id_cards') }}</a>
                @endcan
                @can('manage_certificates')
                    <a href="{{ route('dashboard.certificates.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.certificates*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.certificates') }}</a>
                @endcan
                @can('manage_certificates')
                    <a href="{{ route('dashboard.testimonials.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.testimonials*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.testimonials') }}</a>
                @endcan
                    <a href="{{ route('dashboard.committee.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.committee*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.committee_members') }}</a>
            </div>
        </details>

        {{-- Library --}}
        @can('manage_books')
        <details class="group" @if (request()->routeIs('dashboard.library*')) open @endif>
            <summary class="admin-nav-link cursor-pointer list-none [&::-webkit-details-marker]:hidden {{ request()->routeIs('dashboard.library*') ? 'admin-nav-link--active' : '' }}">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center opacity-80">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg>
                </span>
                <span class="flex-1 truncate">{{ __('dashboard.library') }}</span>
                <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-90 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </summary>
            <div class="ml-4 mt-1 space-y-0.5 border-l border-slate-200 pl-3 dark:border-slate-700">
                <a href="{{ route('dashboard.library.books.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.library.books*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.books') }}</a>
                <a href="{{ route('dashboard.library.categories.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.library.categories*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.book_categories') }}</a>
                <a href="{{ route('dashboard.library.issues.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.library.issues*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.book_issues') }}</a>
                <a href="{{ route('dashboard.library.reports.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.library.reports*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.library_reports') }}</a>
            </div>
        </details>
        @endcan

        <x-admin-nav-link :href="route('dashboard.events')" route-is="dashboard.events*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z\' clip-rule=\'evenodd\'/></svg>'">{{ __('dashboard.events') }}</x-admin-nav-link>
        <x-admin-nav-link :href="route('dashboard.events.calendar')" route-is="dashboard.events.calendar" :icon="'<svg class=\'h-5 w-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z\'/></svg>'">{{ __('dashboard.calendar') }}</x-admin-nav-link>
        @if ($canFees)
            @can('manage_vehicles')
                <x-admin-nav-link :href="route('dashboard.transport.vehicles.index')" route-is="dashboard.transport.*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z\'/><path d=\'M3 4a1 1 0 00-1 1v3a1 1 0 001 1h1l1.6 4.4A2 2 0 007.4 15h7.2a2 2 0 001.8-1.6L17 9h2a1 1 0 100-2h-3.28l-.6-2H11v2h2.28l1.2 4H8.52L7.4 6.6A2 2 0 005.6 5H3z\'/></svg>'">{{ __('dashboard.transport') }}</x-admin-nav-link>
            @endcan
        @endif

        <x-admin-nav-link :href="route('dashboard.hostels.index')" route-is="dashboard.hostels*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z\'/></svg>'">{{ __('dashboard.hostel_management') }}</x-admin-nav-link>

        @if ($isAdmin ?? auth()->user()?->hasRole('admin'))
            <p class="mb-2 mt-5 px-3 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ __('dashboard.system') }}</p>
            @can('view_audit_log')
                <x-admin-nav-link :href="route('dashboard.activity.index')" route-is="dashboard.activity*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M9 2a1 1 0 000 2h2a1 1 0 100-2H9z\'/><path fill-rule=\'evenodd\' d=\'M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z\' clip-rule=\'evenodd\'/></svg>'">{{ __('dashboard.activity_log') }}</x-admin-nav-link>
            @endcan
            <x-admin-nav-link :href="route('dashboard.visitor-logs.index')" route-is="dashboard.visitor-logs*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M10 12a2 2 0 100-4 2 2 0 000 4z\'/><path fill-rule=\'evenodd\' d=\'M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z\' clip-rule=\'evenodd\'/></svg>'">{{ __('dashboard.visitor_logs') }}</x-admin-nav-link>
            @can('backup_database')
                <x-admin-nav-link :href="route('dashboard.backup.index')" route-is="dashboard.backup*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z\'/></svg>'">{{ __('dashboard.backups') }}</x-admin-nav-link>
            @endcan

            <p class="mb-2 mt-5 px-3 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ __('dashboard.website') }}</p>

            <details class="group" @if (request()->routeIs('dashboard.cms.*') || request()->routeIs('dashboard.contact-submissions') || request()->routeIs('dashboard.news.*') || request()->routeIs('dashboard.gallery.*') || request()->routeIs('dashboard.announcements.*') || request()->routeIs('dashboard.notices.*') || request()->routeIs('dashboard.documents.*') || request()->routeIs('dashboard.media.*') || request()->routeIs('dashboard.settings.cms') || request()->routeIs('dashboard.settings.global-labels')) open @endif>
                <summary class="admin-nav-link cursor-pointer list-none [&::-webkit-details-marker]:hidden {{ request()->routeIs('dashboard.cms.*') || request()->routeIs('dashboard.settings.cms') || request()->routeIs('dashboard.settings.global-labels') || request()->routeIs('dashboard.news.*') || request()->routeIs('dashboard.gallery.*') ? 'admin-nav-link--active' : '' }}">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center opacity-80">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.083 9h1.946c.089-1.546.383-2.97.837-4.118A6.004 6.004 0 004.083 9zM10 2a8 8 0 100 16 8 8 0 000-16zm0 2c-.076 0-.232.032-.465.262-.238.234-.497.623-.737 1.182-.389.907-.673 2.142-.766 3.556h3.936c-.093-1.414-.377-2.649-.766-3.556-.24-.56-.5-.948-.737-1.182C10.232 4.032 10.076 4 10 4zm3.971 5c-.089-1.546-.383-2.97-.837-4.118A6.004 6.004 0 0115.917 9h-1.946zm-2.003 2H8.032c.093 1.414.377 2.649.766 3.556.24.56.5.948.737 1.182.233.23.389.262.465.262.076 0 .232-.032.465-.262.238-.234.498-.623.737-1.182.389-.907.673-2.142.766-3.556zm1.166 4.118c.454-1.147.748-2.572.837-4.118h1.946a6.004 6.004 0 01-2.783 4.118zm-6.268 0C6.412 13.97 6.118 12.546 6.03 11H4.083a6.004 6.004 0 002.783 4.118z" clip-rule="evenodd"/></svg>
                    </span>
                    <span class="flex-1 truncate">{{ __('dashboard.website_cms') }}</span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-90 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </summary>
                <div class="ml-4 mt-1 space-y-0.5 border-l border-slate-200 pl-3 dark:border-slate-700">
                    <a href="{{ route('dashboard.cms.pages') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.cms.pages') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.all_pages') }}</a>
                    <a href="{{ route('dashboard.settings.cms') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.settings.cms') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.cms_settings') ?? __('CMS Settings') }}</a>
                    <a href="{{ route('dashboard.settings.global-labels') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.settings.global-labels') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.global_labels') ?? __('Global Labels') }}</a>
                    <a href="{{ route('dashboard.news.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.news.*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.news_events') }}</a>
                    <a href="{{ route('dashboard.gallery.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.gallery.*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.gallery') }}</a>
                    <a href="{{ route('dashboard.announcements.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.announcements.*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.announcements') }}</a>
                    <a href="{{ route('dashboard.notices.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.notices.*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.notices') }}</a>
                    <a href="{{ route('dashboard.documents.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.documents.*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.documents_label') }}</a>
                    <a href="{{ route('dashboard.media.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.media.*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.media_library') }}</a>
                    <a href="{{ route('dashboard.contact-submissions') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.contact-submissions') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.form_submissions') }}</a>
                </div>
            </details>

            <x-admin-nav-link :href="route('dashboard.settings.index')" route-is="dashboard.settings.index" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z\'/></svg>'">{{ __('dashboard.school_info') }}</x-admin-nav-link>

            <p class="mb-2 mt-5 px-3 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ __('dashboard.administration') }}</p>

            @can('manage_users')
                <details class="group" @if (request()->routeIs('dashboard.users.*') || request()->routeIs('dashboard.roles.*') || request()->routeIs('dashboard.permissions.*')) open @endif>
                    <summary class="admin-nav-link cursor-pointer list-none [&::-webkit-details-marker]:hidden {{ request()->routeIs('dashboard.users.*') || request()->routeIs('dashboard.roles.*') || request()->routeIs('dashboard.permissions.*') ? 'admin-nav-link--active' : '' }}">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center opacity-80">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a7 7 0 017 7H1v-1a7 7 0 015-6.7z"/></svg>
                        </span>
                        <span class="flex-1 truncate">{{ __('dashboard.users_and_roles') }}</span>
                        <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-90 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </summary>
                    <div class="ml-4 mt-1 space-y-0.5 border-l border-slate-200 pl-3 dark:border-slate-700">
                        <a href="{{ route('dashboard.users.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.users*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.users') }}</a>
                        <a href="{{ route('dashboard.roles.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.roles*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.roles') }}</a>
                        <a href="{{ route('dashboard.permissions.index') }}" class="block rounded-lg py-2 pl-2 text-sm {{ request()->routeIs('dashboard.permissions*') ? 'font-semibold text-brand-700 dark:text-brand-400' : 'text-slate-600 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400' }}">{{ __('dashboard.permissions') }}</a>
                    </div>
                </details>
            @endcan

            <p class="mb-2 mt-5 px-3 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ __('dashboard.configuration') }}</p>

            <x-admin-nav-link :href="route('dashboard.settings.general')" route-is="dashboard.settings.general|dashboard.settings.update.*|dashboard.settings.cms|dashboard.settings.global-labels" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z\' clip-rule=\'evenodd\'/></svg>'">{{ __('dashboard.settings') }}</x-admin-nav-link>
            <x-admin-nav-link :href="route('dashboard.reports')" route-is="dashboard.reports*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z\'/></svg>'">{{ __('dashboard.reports') }}</x-admin-nav-link>
            <x-admin-nav-link :href="route('dashboard.bulk')" route-is="dashboard.bulk*" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z\' clip-rule=\'evenodd\'/></svg>'">{{ __('dashboard.bulk_import_export') }}</x-admin-nav-link>
        @endif

        <p class="mb-2 mt-5 px-3 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ __('dashboard.help_group') }}</p>
        <x-admin-nav-link :href="route('dashboard.about')" route-is="dashboard.about" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z\' clip-rule=\'evenodd\'/></svg>'">{{ __('dashboard.about') }}</x-admin-nav-link>
        <x-admin-nav-link :href="route('dashboard.help')" route-is="dashboard.help" :icon="'<svg class=\'h-5 w-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z\' clip-rule=\'evenodd\'/></svg>'">{{ __('dashboard.help_documentation') }}</x-admin-nav-link>
    </div>
</nav>

<div class="mt-auto border-t border-slate-200/80 p-3 dark:border-slate-700/80">
    {{-- Install App --}}
    <button type="button" data-pwa-install class="mb-2 hidden w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>{{ __('dashboard.install_app') }}</span>
    </button>

    {{-- Dark mode toggle --}}
    <button type="button" data-dark-toggle class="mb-2 flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">
        <svg class="h-4 w-4 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        <svg class="hidden h-4 w-4 dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <span>{{ __('dashboard.dark_mode') }}</span>
    </button>

    <form method="post" action="{{ route('logout') }}">
        @csrf
        <x-button type="submit" variant="danger" class="w-full">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/></svg>
            {{ __('dashboard.logout') }}
        </x-button>
    </form>
</div>
