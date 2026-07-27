@php
    $school = $siteSettings?->localized_school_name ?: ($siteSettings?->school_name ?? config('app.name', 'School'));
    $words = preg_split('/\s+/', trim($school), 2);
    $brandFirst = $words[0] ?? $school;
    $brandRest = $words[1] ?? '';
    $phoneReal = $siteSettings?->phone ?? config('school.contact_phone');
    $emailReal = $siteSettings?->email ?? config('school.contact_email');
    $addrReal = $siteSettings?->full_address ?? $siteSettings?->address ?? config('school.contact_address');
    $phone = $phoneReal ?: config('school.placeholder_phone');
    $email = $emailReal ?: config('school.placeholder_email');
    $addr = $addrReal ?: config('school.placeholder_address');
    $phTip = __('Example — set real details in Dashboard → School settings');
    $currentRoute = request()->route()->getName();
    $isActive = fn($patterns) => collect((array)$patterns)->contains(fn($p) => request()->routeIs($p));
@endphp

{{-- Top utility bar: hidden on small, condenses on medium, full on large --}}
<div class="hidden bg-blue-900 text-sm text-white sm:block">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-2 lg:flex-row">
        <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-1 lg:justify-start">
            <span class="inline-flex items-center gap-1.5 {{ $phoneReal ? '' : 'opacity-80' }}" @if(! $phoneReal) title="{{ $phTip }}" @endif>
                <svg class="h-3.5 w-3.5 shrink-0 text-blue-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                @if($phoneReal)
                    <a href="tel:{{ preg_replace('/\s+/', '', $phoneReal) }}" class="whitespace-nowrap font-medium hover:text-blue-100">{{ $phone }}</a>
                @else
                    <span class="border-b border-dotted border-blue-300/80 font-medium italic">{{ $phone }}</span>
                @endif
            </span>
            <span class="inline-flex items-center gap-1.5 {{ $emailReal ? '' : 'opacity-80' }}" @if(! $emailReal) title="{{ $phTip }}" @endif>
                <svg class="h-3.5 w-3.5 shrink-0 text-blue-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
                @if($emailReal)
                    <a href="mailto:{{ $emailReal }}" class="max-w-[16rem] truncate font-medium hover:text-blue-100 lg:max-w-none">{{ $email }}</a>
                @else
                    <span class="border-b border-dotted border-blue-300/80 font-medium italic">{{ $email }}</span>
                @endif
            </span>
            <span class="hidden items-start gap-1.5 xl:inline-flex {{ $addrReal ? '' : 'opacity-80' }}" @if(! $addrReal) title="{{ $phTip }}" @endif>
                <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-blue-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                @if($addrReal)
                    <span class="text-blue-100">{{ \Illuminate\Support\Str::limit($addr, 80) }}</span>
                @else
                    <span class="border-b border-dotted border-blue-300/80 text-blue-100 italic">{{ \Illuminate\Support\Str::limit($addr, 80) }}</span>
                @endif
            </span>
        </div>
        <div class="flex flex-wrap items-center justify-center gap-3 lg:justify-end">
            <div class="flex items-center gap-1">
                @foreach (config('school.supported_locales', ['en']) as $loc)
                    <a href="{{ route('locale.switch', ['locale' => $loc]) }}"
                        @php $locLabel = ['en' => 'EN', 'bn' => 'বাংলা'][$loc] ?? strtoupper($loc); @endphp
                        aria-label="{{ $locLabel }}"
                        class="inline-flex min-w-[1.75rem] items-center justify-center rounded border px-2 py-0.5 text-[0.7rem] font-bold uppercase tracking-wide transition {{ app()->getLocale() === $loc ? 'border-white bg-white/15 text-white' : 'border-blue-400/60 text-blue-200 hover:border-white hover:text-white' }}">
                        {{ $locLabel }}
                    </a>
                @endforeach
            </div>
            <span class="hidden h-4 w-px bg-blue-600 sm:block" aria-hidden="true"></span>
            <div class="flex items-center gap-2 text-blue-200">
                @include('partials.site.social-links', ['settings' => $siteSettings, 'linkClass' => 'text-blue-200 hover:text-white', 'placeholderClass' => 'opacity-55'])
            </div>
            <span class="hidden h-4 w-px bg-blue-600 sm:block" aria-hidden="true"></span>
        </div>
    </div>
</div>

{{-- Admissions CTA top bar — only shown when admissions are open --}}
@php
    $admissionOpen = true;
    try {
        if (class_exists(\App\Models\AdmissionSetting::class) && \Illuminate\Support\Facades\Schema::hasTable('admission_settings')) {
            $admissionOpen = \App\Models\AdmissionSetting::getSettings()->is_open;
        }
    } catch (\Throwable) {}
@endphp
@if($admissionOpen && ($siteSettings->section_visibility['admissions_bar'] ?? true))
<div class="hidden items-center justify-center gap-2 bg-gradient-to-r from-orange-500 to-orange-600 px-4 py-1.5 text-center text-xs font-medium text-white sm:flex">
    <span class="inline-flex items-center gap-1">
        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm2.5 3a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm6.207.293a1 1 0 00-1.414 0l-6 6a1 1 0 101.414 1.414l6-6a1 1 0 000-1.414zM14 8a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" clip-rule="evenodd"/></svg>
        {{ __('Admissions Open') }} {{ date('Y') }}-{{ date('Y', strtotime('+1 year')) }}
    </span>
    <a href="{{ route('admissions.apply') }}" class="inline-flex items-center gap-0.5 font-semibold underline underline-offset-2 hover:no-underline">
        {{ __('Apply Now') }}
        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </a>
</div>
@endif

{{-- Main header: sticky with blur --}}
<header class="sticky top-0 z-50 w-full border-b border-slate-200/80 bg-white/95 backdrop-blur-md transition-shadow duration-300">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-2 px-4 py-3 sm:py-4">
        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2 no-underline sm:gap-3">
            @if($siteSettings?->logo_url)
                <img src="{{ $siteSettings->logo_url }}" alt="" width="120" height="48" class="h-9 w-auto max-h-10 max-w-[8rem] shrink-0 object-contain sm:h-10 sm:max-h-12 sm:max-w-[10rem] md:max-w-[12rem]">
            @endif
            <span class="truncate text-lg font-bold leading-tight text-blue-700 sm:text-2xl md:text-3xl">
                {{ $brandFirst }}@if($brandRest)<span class="text-orange-500">{{ ' '.$brandRest }}</span>@endif
            </span>
        </a>

        <div class="flex items-center gap-1">
            {{-- Search toggle --}}
            <button type="button" data-search-toggle aria-label="Search"
                class="inline-flex items-center justify-center rounded-md border border-gray-200 bg-white p-2 text-gray-500 transition hover:bg-blue-50 hover:text-blue-700 min-[1367px]:hidden">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>

            {{-- Hamburger: visible below 1367px --}}
            <button type="button" data-site-nav-trigger aria-controls="site-nav-panel" aria-expanded="false"
                aria-label="{{ site_ui('nav.menu') }}"
                class="inline-flex items-center justify-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 min-[1367px]:hidden">
                <svg data-icon-menu class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
                <svg data-icon-close class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"/>
                </svg>
                <span class="hidden sm:inline">{{ site_ui('nav.menu') }}</span>
            </button>
        </div>

        {{-- Desktop nav: 1367px and up --}}
        <nav class="hidden items-center gap-1 min-[1367px]:flex" aria-label="{{ site_ui('nav.menu') }}">
            @php
                $link = fn ($active) => 'rounded-md px-3 py-2 text-sm font-medium transition-colors whitespace-nowrap '.($active ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700');
                $btnOutline = 'ml-1 inline-flex items-center justify-center rounded-md border-2 border-blue-600 bg-white px-3 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50 whitespace-nowrap';
                $btnNeutral = 'ml-1 inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-800 shadow-sm transition hover:bg-gray-50 whitespace-nowrap';
                $btnPrimary = 'ml-1 inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 whitespace-nowrap';

                $dropdownItem = fn ($active) => 'flex items-center gap-3 rounded-md px-3 py-2 text-sm transition '.($active ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700');
                $dropdownGroup = function (string $key, string $label, array $items, string $activePattern) {
                    $isActive = false;
                    foreach ($items as $it) { if (request()->routeIs($it['pattern'])) { $isActive = true; break; } }
                    if (request()->routeIs($activePattern)) $isActive = true;
                    return [
                        'key' => $key,
                        'label' => $label,
                        'active' => $isActive,
                        'items' => $items,
                    ];
                };

                $navGroups = [
                    'about' => $dropdownGroup('about', site_ui('nav.group.about'), [
                        ['label' => site_ui('nav.about'),    'route' => 'site.about',    'pattern' => 'site.about',    'icon' => 'M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z'],
                        ['label' => site_ui('nav.faculty'),  'route' => 'site.faculty',  'pattern' => 'site.faculty',  'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
                        ['label' => site_ui('nav.students'), 'route' => 'site.students', 'pattern' => 'site.students', 'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
                    ], 'site.about'),
                    'academics' => $dropdownGroup('academics', site_ui('nav.group.academics'), [
                        ['label' => site_ui('nav.academics'), 'route' => 'site.academics', 'pattern' => 'site.academics', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                        ['label' => site_ui('nav.routine'),   'route' => 'site.routine',   'pattern' => 'site.routine',   'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['label' => site_ui('nav.admissions'),'route' => 'site.admissions','pattern' => 'site.admissions|admissions.*', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['label' => site_ui('nav.gallery'),   'route' => 'site.gallery',   'pattern' => 'site.gallery',   'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ['label' => site_ui('nav.results'),   'route' => 'site.results',   'pattern' => 'site.results',   'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                    ], 'site.academics'),
                    'contact' => $dropdownGroup('contact', site_ui('nav.group.contact'), [
                        ['label' => site_ui('nav.contact'),  'route' => 'site.contact',  'pattern' => 'site.contact',  'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                        ['label' => site_ui('nav.payments'), 'route' => 'site.payments', 'pattern' => 'site.payments', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                    ], 'site.contact'),
                    'news' => $dropdownGroup('news', site_ui('nav.group.news'), [
                        ['label' => site_ui('nav.news'),   'route' => 'site.news',   'pattern' => 'site.news*',   'icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z'],
                        ['label' => site_ui('nav.notices'), 'route' => 'site.notices', 'pattern' => 'site.notices', 'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                    ], 'site.news'),
                ];
            @endphp

            <a href="{{ route('home') }}" class="{{ $link(request()->routeIs('home')) }}">{{ site_ui('nav.home') }}</a>

            @foreach ($navGroups as $group)
                @php $gid = 'site-nav-group-'.$group['key']; @endphp
                <div class="relative" data-site-nav-dropdown>
                    <button type="button" data-site-nav-dropdown-trigger aria-haspopup="true" aria-expanded="false" aria-controls="{{ $gid }}"
                        class="{{ $link($group['active']) }} inline-flex items-center gap-1">
                        <span>{{ $group['label'] }}</span>
                        <svg class="h-3.5 w-3.5 transition-transform duration-200" data-site-nav-caret fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="{{ $gid }}" data-site-nav-dropdown-panel
                        class="invisible absolute right-0 top-full z-50 mt-1 min-w-[14rem] origin-top-right translate-y-1 rounded-lg border border-gray-100 bg-white p-2 opacity-0 shadow-lg ring-1 ring-black/5 transition-all duration-150 data-[open=true]:visible data-[open=true]:translate-y-0 data-[open=true]:opacity-100"
                        role="menu" aria-label="{{ $group['label'] }}">
                        <ul class="space-y-0.5">
                            @foreach ($group['items'] as $item)
                                @php
                                    $patterns = explode('|', $item['pattern']);
                                    $isItemActive = false;
                                    foreach ($patterns as $p) { if (request()->routeIs($p)) { $isItemActive = true; break; } }
                                @endphp
                                <li role="none">
                                    <a href="{{ route($item['route']) }}" role="menuitem"
                                        class="{{ $dropdownItem($isItemActive) }}">
                                        <svg class="h-4 w-4 shrink-0 {{ $isItemActive ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                                        </svg>
                                        <span class="flex-1">{{ $item['label'] }}</span>
                                        @if($isItemActive)
                                            <svg class="h-4 w-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4L8.5 12l6.8-6.7a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach

            {{-- Search toggle desktop --}}
            <button type="button" data-search-toggle aria-label="Search"
                class="rounded-md p-2 text-gray-500 transition hover:bg-blue-50 hover:text-blue-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>

            @auth
                @php
                    $navStaffRoles = ['admin', 'teacher', 'accountant', 'staff', 'librarian'];
                    $navIsStaff = auth()->user()->hasAnyRole($navStaffRoles);
                @endphp
                @if($navIsStaff)
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard*') ? $btnPrimary : $btnOutline }}">{{ site_ui('nav.dashboard') }}</a>
                @else
                    <a href="{{ route('portal') }}" class="{{ request()->routeIs('portal') || request()->routeIs('portal.*') ? $btnPrimary : $btnOutline }}">{{ site_ui('nav.portal') }}</a>
                @endif
                <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile*') ? $link(true) : $link(false) }}">{{ site_ui('nav.profile') }}</a>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="{{ $btnNeutral }}">{{ site_ui('nav.logout') }}</button>
                </form>
            @else
                <a href="{{ route('portal.register') }}" class="{{ $btnOutline }}">{{ site_ui('nav.register') }}</a>
                <a href="{{ route('login') }}" class="{{ $btnPrimary }}">{{ site_ui('nav.login') }}</a>
            @endauth
        </nav>
    </div>

    {{-- Search overlay --}}
    <div data-search-overlay class="hidden absolute inset-x-0 top-full border-t border-slate-200 bg-white shadow-lg">
        <div class="mx-auto max-w-3xl px-4 py-6">
            <form action="{{ route('home') }}" method="GET" class="relative">
                <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" name="q" placeholder="Search for pages, news, events..." autocomplete="off"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                <button type="button" data-search-close class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md p-1 text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </form>
        </div>
    </div>

    {{-- Responsive panel: visible below 1366px --}}
    <div id="site-nav-panel" data-site-nav-panel hidden
        class="min-[1367px]:hidden border-t border-gray-100 bg-white shadow-lg"
        role="dialog" aria-modal="false" aria-label="{{ site_ui('nav.menu') }}">
        <nav class="mx-auto max-h-[calc(100vh-5rem)] max-w-7xl overflow-y-auto pb-24" aria-label="{{ site_ui('nav.menu') }}">
            @php
                $linkMobile = fn ($active) => 'flex items-center justify-between rounded-xl px-4 py-3 text-[0.95rem] font-medium transition-all duration-200 '.($active ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-100 shadow-sm' : 'text-gray-700 hover:bg-gray-50 active:bg-blue-50');
                $sectionTitle = 'mb-2 px-5 text-[0.65rem] font-bold uppercase tracking-[0.15em] text-gray-400';
                $btnMobilePrimary = 'flex items-center justify-center w-full rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-3.5 text-[0.95rem] font-semibold text-white shadow-md transition-all duration-200 hover:from-blue-700 hover:to-blue-800 active:scale-[0.98]';
                $btnMobileOutline = 'flex items-center justify-center w-full rounded-xl border-2 border-blue-600 bg-white px-4 py-3.5 text-[0.95rem] font-semibold text-blue-700 transition-all duration-200 hover:bg-blue-50 active:scale-[0.98]';
                $btnMobileNeutral = 'flex items-center justify-center w-full rounded-xl border border-gray-200 bg-white px-4 py-3.5 text-[0.95rem] font-semibold text-gray-700 transition-all duration-200 hover:bg-gray-50 active:scale-[0.98]';
                $check = '<svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4L8.5 12l6.8-6.7a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>';
            @endphp

            {{-- School branding at top --}}
            <div class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-blue-50/80 to-white px-5 py-4">
                @if($siteSettings?->logo_url)
                    <img src="{{ $siteSettings->logo_url }}" alt="" width="80" height="32" class="h-8 w-auto shrink-0 object-contain">
                @endif
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-blue-800">{{ $school }}</p>
                    @if($siteSettings?->tagline)
                        <p class="truncate text-xs text-gray-500">{{ $siteSettings->tagline }}</p>
                    @endif
                </div>
            </div>

            {{-- Search bar --}}
            <div class="px-5 pt-4 pb-2">
                <form action="{{ route('home') }}" method="GET" class="relative">
                    <svg class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="search" name="q" placeholder="{{ __('Search...') }}" autocomplete="off"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-900 placeholder:text-gray-400 transition-all duration-200 focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </form>
            </div>

            {{-- Navigation links --}}
            <div class="px-5 pt-3">
                <p class="{{ $sectionTitle }}">{{ __('Menu') }}</p>
                <ul class="space-y-1">
                    <li><a data-site-nav-link href="{{ route('home') }}" class="{{ $linkMobile(request()->routeIs('home')) }}"><span>{{ site_ui('nav.home') }}</span>@if(request()->routeIs('home')){!! $check !!}@endif</a></li>
                </ul>

                @php
                    $mobileGroup = function (array $group) {
                        $isGroupActive = $group['active'];
                        $subActive = fn ($pattern) => (bool) request()->routeIs($pattern);
                        return compact('isGroupActive', 'subActive', 'group');
                    };
                @endphp

                <ul class="mt-2 space-y-1">
                    @foreach ($navGroups as $group)
                        @php $g = $mobileGroup($group); @endphp
                        <li data-site-nav-accordion class="rounded-xl {{ $g['isGroupActive'] ? 'bg-blue-50/70 ring-1 ring-blue-100/80 shadow-sm' : '' }}">
                            <button type="button" data-site-nav-accordion-trigger aria-expanded="false"
                                class="flex w-full items-center justify-between rounded-xl px-4 py-3.5 text-[0.95rem] font-medium transition-all duration-200 {{ $g['isGroupActive'] ? 'text-blue-700' : 'text-gray-700 hover:bg-gray-50 active:bg-blue-50' }}">
                                <div class="flex items-center gap-3">
                                    @if($group['active'])
                                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500 shrink-0"></span>
                                    @endif
                                    <span>{{ $group['label'] }}</span>
                                </div>
                                <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-300 ease-out" data-site-nav-accordion-caret fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div data-site-nav-accordion-panel class="overflow-hidden transition-[max-height] duration-300 ease-out" style="max-height:0">
                                <div class="px-2 pb-3 pt-1">
                                    <ul class="ml-4 space-y-0.5 border-l-2 border-gray-100 pl-4">
                                        @foreach ($group['items'] as $item)
                                            @php
                                                $patterns = explode('|', $item['pattern']);
                                                $isSubActive = false;
                                                foreach ($patterns as $p) { if (request()->routeIs($p)) { $isSubActive = true; break; } }
                                            @endphp
                                            <li>
                                                <a data-site-nav-link href="{{ route($item['route']) }}"
                                                    class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm transition-all duration-200 {{ $isSubActive ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-700 active:bg-blue-50' }}">
                                                    <svg class="h-4 w-4 shrink-0 {{ $isSubActive ? 'text-blue-600' : 'text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                                                    </svg>
                                                    <span class="flex-1">{{ $item['label'] }}</span>
                                                    @if($isSubActive)
                                                        <svg class="h-4 w-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4L8.5 12l6.8-6.7a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                                                    @endif
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Language segmented control --}}
                <div class="mt-5">
                    <p class="{{ $sectionTitle }}">{{ __('Language') }}</p>
                    <div class="mx-1 flex overflow-hidden rounded-xl border border-gray-200 bg-gray-50 p-1">
                        @foreach (config('school.supported_locales', ['en']) as $loc)
                            @php $locLabel = ['en' => 'English', 'bn' => 'বাংলা'][$loc] ?? strtoupper($loc); @endphp
                            <a data-site-nav-link href="{{ route('locale.switch', ['locale' => $loc]) }}"
                                aria-label="{{ $locLabel }}"
                                class="flex-1 text-center rounded-lg px-3 py-2.5 text-sm font-semibold transition-all duration-200 {{ app()->getLocale() === $loc ? 'bg-white text-blue-700 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-700' }}">
                                {{ $locLabel }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Contact info with icons --}}
                @if($phoneReal || $emailReal || $addrReal)
                    <div class="mt-5">
                        <p class="{{ $sectionTitle }}">{{ __('Contact') }}</p>
                        <div class="mx-1 space-y-1 rounded-xl bg-gray-50 px-4 py-3">
                            @if($phoneReal)
                                <a href="tel:{{ preg_replace('/\s+/', '', $phoneReal) }}" class="flex items-center gap-3 py-2 text-sm text-gray-600 transition-colors hover:text-blue-700">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                                    </span>
                                    <span>{{ $phone }}</span>
                                </a>
                            @endif
                            @if($emailReal)
                                <a href="mailto:{{ $emailReal }}" class="flex items-center gap-3 py-2 text-sm text-gray-600 transition-colors hover:text-blue-700">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
                                    </span>
                                    <span class="break-all">{{ $email }}</span>
                                </a>
                            @endif
                            @if($addrReal)
                                <div class="flex items-start gap-3 py-2 text-sm text-gray-600">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                    </span>
                                    <span>{{ \Illuminate\Support\Str::limit($addr, 80) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </nav>

        {{-- Fixed bottom action bar for thumb access --}}
        <div class="fixed inset-x-0 bottom-0 z-50 border-t border-gray-200 bg-white/95 px-5 py-3 backdrop-blur-md min-[1367px]:hidden">
            @auth
                @php
                    $navStaffRoles = ['admin', 'teacher', 'accountant', 'staff', 'librarian'];
                    $navIsStaff = auth()->user()->hasAnyRole($navStaffRoles);
                @endphp
                <div class="flex items-center gap-3">
                    <a data-site-nav-link href="{{ $navIsStaff ? route('dashboard') : route('portal') }}"
                        class="flex-1 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-3 text-center text-sm font-semibold text-white shadow-md transition-all duration-200 hover:from-blue-700 hover:to-blue-800 active:scale-[0.98]">
                        {{ $navIsStaff ? site_ui('nav.dashboard') : site_ui('nav.portal') }}
                    </a>
                    <a data-site-nav-link href="{{ route('profile.edit') }}"
                        class="flex-1 rounded-xl border border-gray-200 bg-white px-4 py-3 text-center text-sm font-semibold text-gray-700 transition-all duration-200 hover:bg-gray-50 active:scale-[0.98]">
                        {{ site_ui('nav.profile') }}
                    </a>
                    <form method="post" action="{{ route('logout') }}" data-site-nav-link class="flex-1">
                        @csrf
                        <button type="submit" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition-all duration-200 hover:bg-gray-50 active:scale-[0.98]">
                            {{ site_ui('nav.logout') }}
                        </button>
                    </form>
                </div>
            @else
                <div class="flex items-center gap-3">
                    <a data-site-nav-link href="{{ route('portal.register') }}"
                        class="flex-1 rounded-xl border-2 border-blue-600 bg-white px-4 py-3 text-center text-sm font-semibold text-blue-700 transition-all duration-200 hover:bg-blue-50 active:scale-[0.98]">
                        {{ site_ui('nav.register') }}
                    </a>
                    <a data-site-nav-link href="{{ route('login') }}"
                        class="flex-1 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-3 text-center text-sm font-semibold text-white shadow-md transition-all duration-200 hover:from-blue-700 hover:to-blue-800 active:scale-[0.98]">
                        {{ site_ui('nav.login') }}
                    </a>
                </div>
            @endauth
        </div>
    </div>
</header>

<script>
(function () {
    var trigger = document.querySelector('[data-site-nav-trigger]');
    var panel = document.querySelector('[data-site-nav-panel]');
    if (!trigger || !panel) return;

    var iconMenu = trigger.querySelector('[data-icon-menu]');
    var iconClose = trigger.querySelector('[data-icon-close]');
    var desktopQuery = window.matchMedia('(min-width: 1367px)');

    function setOpen(open) {
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.setAttribute('aria-modal', open ? 'true' : 'false');
        if (open) {
            panel.removeAttribute('hidden');
        } else {
            panel.setAttribute('hidden', '');
        }
        if (iconMenu && iconClose) {
            iconMenu.classList.toggle('hidden', open);
            iconClose.classList.toggle('hidden', !open);
        }
    }

    trigger.addEventListener('click', function () {
        setOpen(panel.hasAttribute('hidden'));
    });

    panel.addEventListener('click', function (e) {
        var link = e.target.closest('[data-site-nav-link]');
        if (link) setOpen(false);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !panel.hasAttribute('hidden')) {
            setOpen(false);
            trigger.focus();
        }
    });

    desktopQuery.addEventListener('change', function (e) {
        if (e.matches) setOpen(false);
    });

    var dropdowns = document.querySelectorAll('[data-site-nav-dropdown]');

    function openDropdown(dd) {
        var trig = dd.querySelector('[data-site-nav-dropdown-trigger]');
        var p = dd.querySelector('[data-site-nav-dropdown-panel]');
        var caret = dd.querySelector('[data-site-nav-caret]');
        if (!trig || !p) return;
        dropdowns.forEach(function (other) {
            if (other !== dd) closeDropdown(other);
        });
        p.setAttribute('data-open', 'true');
        trig.setAttribute('aria-expanded', 'true');
        if (caret) caret.style.transform = 'rotate(180deg)';
    }

    function closeDropdown(dd) {
        var trig = dd.querySelector('[data-site-nav-dropdown-trigger]');
        var p = dd.querySelector('[data-site-nav-dropdown-panel]');
        var caret = dd.querySelector('[data-site-nav-caret]');
        if (!trig || !p) return;
        p.removeAttribute('data-open');
        trig.setAttribute('aria-expanded', 'false');
        if (caret) caret.style.transform = '';
    }

    function closeAllDropdowns() {
        dropdowns.forEach(closeDropdown);
    }

    dropdowns.forEach(function (dd) {
        var trig = dd.querySelector('[data-site-nav-dropdown-trigger]');
        if (!trig) return;
        var p = dd.querySelector('[data-site-nav-dropdown-panel]');
        var isOpen = false;
        var closeTimer = null;

        dd.addEventListener('mouseenter', function () {
            if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
            if (!desktopQuery.matches) return;
            openDropdown(dd);
            isOpen = true;
        });
        dd.addEventListener('mouseleave', function () {
            if (!desktopQuery.matches) return;
            closeTimer = setTimeout(function () { closeDropdown(dd); isOpen = false; }, 120);
        });

        trig.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            isOpen = !isOpen;
            if (isOpen) openDropdown(dd);
            else closeDropdown(dd);
        });

        if (p) {
            p.addEventListener('click', function (e) {
                if (e.target.closest('a')) closeDropdown(dd);
            });
        }
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('[data-site-nav-dropdown]')) closeAllDropdowns();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAllDropdowns();
    });

    var accordions = document.querySelectorAll('[data-site-nav-accordion]');
    accordions.forEach(function (acc) {
        var trig = acc.querySelector('[data-site-nav-accordion-trigger]');
        var p = acc.querySelector('[data-site-nav-accordion-panel]');
        var caret = acc.querySelector('[data-site-nav-accordion-caret]');
        if (!trig || !p) return;

        function openAccordion() {
            p.style.maxHeight = p.scrollHeight + 'px';
            trig.setAttribute('aria-expanded', 'true');
            if (caret) caret.style.transform = 'rotate(180deg)';
        }
        function closeAccordion() {
            p.style.maxHeight = '0px';
            trig.setAttribute('aria-expanded', 'false');
            if (caret) caret.style.transform = '';
        }

        if (acc.querySelector('a[aria-current], a.bg-blue-50')) {
            openAccordion();
        }

        trig.addEventListener('click', function () {
            var isOpen = trig.getAttribute('aria-expanded') === 'true';
            if (isOpen) closeAccordion();
            else openAccordion();
        });
    });

    // Search toggle
    var searchToggles = document.querySelectorAll('[data-search-toggle]');
    var searchOverlay = document.querySelector('[data-search-overlay]');
    var searchClose = document.querySelector('[data-search-close]');
    if (searchToggles.length && searchOverlay) {
        searchToggles.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var hidden = searchOverlay.classList.toggle('hidden');
                if (!hidden) {
                    var input = searchOverlay.querySelector('input[type="search"]');
                    if (input) setTimeout(function () { input.focus(); }, 100);
                }
            });
        });
        if (searchClose) {
            searchClose.addEventListener('click', function () {
                searchOverlay.classList.add('hidden');
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !searchOverlay.classList.contains('hidden')) {
                searchOverlay.classList.add('hidden');
            }
        });
    }
})();
</script>
