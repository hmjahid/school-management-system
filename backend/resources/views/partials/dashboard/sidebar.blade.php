@php
    $brand = config('app.name', 'SchoolEase');
    $isAdmin = auth()->user()?->hasRole('admin');
@endphp

<div class="flex h-16 flex-shrink-0 items-center border-b border-gray-200 bg-white px-4">
    <a href="{{ route('dashboard') }}" class="text-xl font-bold text-blue-600">{{ $brand }}</a>
</div>

<nav class="flex flex-1 flex-col overflow-y-auto px-2 py-4">
    <div class="space-y-1">
        <a href="{{ route('dashboard') }}"
            class="{{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} flex items-center rounded-md px-3 py-2 text-sm font-medium">
            <svg class="mr-3 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
            {{ __('Dashboard') }}
        </a>

        @php
            $u = auth()->user();
            $canFees = $u && ($u->hasAnyRole(['admin', 'accountant']) || $u->hasAnyPermission(['collect_fees', 'view_financial_reports', 'manage_fee_categories', 'manage_fee_types']));
        @endphp

        @can('viewAny', App\Models\Student::class)
            <a href="{{ route('dashboard.students') }}"
                class="{{ request()->routeIs('dashboard.students*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} flex items-center rounded-md px-3 py-2 text-sm font-medium">
                <svg class="mr-3 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/></svg>
                {{ __('Students') }}
            </a>
        @endcan
        @can('viewAny', App\Models\Teacher::class)
            <a href="{{ route('dashboard.teachers') }}"
                class="{{ request()->routeIs('dashboard.teachers*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} flex items-center rounded-md px-3 py-2 text-sm font-medium">
                <svg class="mr-3 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/></svg>
                {{ __('Teachers') }}
            </a>
        @endcan
        @can('viewAny', App\Models\Guardian::class)
            <a href="{{ route('dashboard.parents') }}"
                class="{{ request()->routeIs('dashboard.parents*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} flex items-center rounded-md px-3 py-2 text-sm font-medium">
                <svg class="mr-3 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                {{ __('Parents') }}
            </a>
        @endcan
        @can('viewAny', App\Models\SchoolClass::class)
            <a href="{{ route('dashboard.classes') }}"
                class="{{ request()->routeIs('dashboard.classes*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} flex items-center rounded-md px-3 py-2 text-sm font-medium">
                <svg class="mr-3 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg>
                {{ __('Classes') }}
            </a>
        @endcan
        @can('viewAny', App\Models\Attendance::class)
            <a href="{{ route('dashboard.attendance') }}"
                class="{{ request()->routeIs('dashboard.attendance*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} flex items-center rounded-md px-3 py-2 text-sm font-medium">
                <svg class="mr-3 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                {{ __('Attendance') }}
            </a>
        @endcan
        @if (auth()->user()?->can('view_admissions'))
            <a href="{{ route('dashboard.admissions.index') }}"
                class="{{ request()->routeIs('dashboard.admissions*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} flex items-center rounded-md px-3 py-2 text-sm font-medium">
                <svg class="mr-3 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a1 1 0 00-1 1v1H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H8V3a1 1 0 00-1-1z"/><path d="M6 10a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1z"/></svg>
                {{ __('Admissions') }}
            </a>
        @endif
        @can('viewAny', App\Models\Exam::class)
            <a href="{{ route('dashboard.exams') }}"
                class="{{ request()->routeIs('dashboard.exams*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} flex items-center rounded-md px-3 py-2 text-sm font-medium">
                <svg class="mr-3 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                {{ __('Exams') }}
            </a>
        @endcan
        @if ($canFees)
            <a href="{{ route('dashboard.fees') }}"
                class="{{ request()->routeIs('dashboard.fees*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} flex items-center rounded-md px-3 py-2 text-sm font-medium">
                <svg class="mr-3 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg>
                {{ __('Fees') }}
            </a>
        @endif

        @if ($isAdmin)
            <details class="group rounded-md" @if (request()->routeIs('dashboard.cms.*') || request()->routeIs('dashboard.contact-submissions') || request()->routeIs('dashboard.news.*') || request()->routeIs('dashboard.gallery.*')) open @endif>
                <summary class="{{ request()->routeIs('dashboard.cms.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} flex cursor-pointer list-none items-center justify-between rounded-md px-3 py-2 text-sm font-medium [&::-webkit-details-marker]:hidden">
                    <span class="flex items-center">
                        <svg class="mr-3 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.083 9h1.946c.089-1.546.383-2.97.837-4.118A6.004 6.004 0 004.083 9zM10 2a8 8 0 100 16 8 8 0 000-16zm0 2c-.076 0-.232.032-.465.262-.238.234-.497.623-.737 1.182-.389.907-.673 2.142-.766 3.556h3.936c-.093-1.414-.377-2.649-.766-3.556-.24-.56-.5-.948-.737-1.182C10.232 4.032 10.076 4 10 4zm3.971 5c-.089-1.546-.383-2.97-.837-4.118A6.004 6.004 0 0115.917 9h-1.946zm-2.003 2H8.032c.093 1.414.377 2.649.766 3.556.24.56.5.948.737 1.182.233.23.389.262.465.262.076 0 .232-.032.465-.262.238-.234.498-.623.737-1.182.389-.907.673-2.142.766-3.556zm1.166 4.118c.454-1.147.748-2.572.837-4.118h1.946a6.004 6.004 0 01-2.783 4.118zm-6.268 0C6.412 13.97 6.118 12.546 6.03 11H4.083a6.004 6.004 0 002.783 4.118z" clip-rule="evenodd"/></svg>
                        {{ __('Website CMS') }}
                    </span>
                    <svg class="h-4 w-4 shrink-0 transition group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </summary>
                <div class="mt-1 space-y-1 border-l border-gray-200 pl-3">
                    <a href="{{ route('dashboard.cms.pages') }}" class="{{ request()->routeIs('dashboard.cms.pages') ? 'font-medium text-blue-700' : 'text-gray-600 hover:text-blue-700' }} block rounded-md py-2 pl-2 text-sm">{{ __('All pages') }}</a>
                    <a href="{{ route('dashboard.news.index') }}" class="{{ request()->routeIs('dashboard.news.*') ? 'font-medium text-blue-700' : 'text-gray-600 hover:text-blue-700' }} block rounded-md py-2 pl-2 text-sm">{{ __('News & events') }}</a>
                    <a href="{{ route('dashboard.gallery.index') }}" class="{{ request()->routeIs('dashboard.gallery.*') ? 'font-medium text-blue-700' : 'text-gray-600 hover:text-blue-700' }} block rounded-md py-2 pl-2 text-sm">{{ __('Gallery') }}</a>
                    <a href="{{ route('dashboard.announcements.index') }}" class="{{ request()->routeIs('dashboard.announcements.*') ? 'font-medium text-blue-700' : 'text-gray-600 hover:text-blue-700' }} block rounded-md py-2 pl-2 text-sm">{{ __('Announcements') }}</a>
                    <a href="{{ route('dashboard.documents.index') }}" class="{{ request()->routeIs('dashboard.documents.*') ? 'font-medium text-blue-700' : 'text-gray-600 hover:text-blue-700' }} block rounded-md py-2 pl-2 text-sm">{{ __('Documents') }}</a>
                    <a href="{{ route('dashboard.contact-submissions') }}" class="{{ request()->routeIs('dashboard.contact-submissions') ? 'font-medium text-blue-700' : 'text-gray-600 hover:text-blue-700' }} block rounded-md py-2 pl-2 text-sm">{{ __('Form submissions') }}</a>
                    @foreach (['header' => __('Header'), 'footer' => __('Footer'), 'menus' => __('Menus'), 'media' => __('Media'), 'blocks' => __('Blocks'), 'blog' => __('Blog')] as $slug => $plabel)
                        <a href="{{ route('dashboard.cms.edit', ['page' => $slug]) }}" class="{{ request()->routeIs('dashboard.cms.edit') && request()->route('page') === $slug ? 'font-medium text-blue-700' : 'text-gray-600 hover:text-blue-700' }} block rounded-md py-2 pl-2 text-sm">{{ $plabel }}</a>
                    @endforeach
                </div>
            </details>

            <a href="{{ route('dashboard.settings') }}"
                class="{{ request()->routeIs('dashboard.settings') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }} flex items-center rounded-md px-3 py-2 text-sm font-medium">
                <svg class="mr-3 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                {{ __('School settings') }}
            </a>
        @endif
    </div>
</nav>

<div class="mt-auto border-t border-gray-200 p-4">
    <form method="post" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="flex w-full items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-red-100 hover:bg-red-700">
            <svg class="mr-3 h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/></svg>
            {{ __('Logout') }}
        </button>
    </form>
</div>
