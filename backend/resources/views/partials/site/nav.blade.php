@php
    $school = $siteSettings?->school_name ?? config('app.name', 'School');
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
                        class="inline-flex min-w-[1.75rem] items-center justify-center rounded border px-2 py-0.5 text-[0.7rem] font-bold uppercase tracking-wide transition {{ app()->getLocale() === $loc ? 'border-white bg-white/15 text-white' : 'border-blue-400/60 text-blue-200 hover:border-white hover:text-white' }}">
                        {{ $loc }}
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

{{-- Main header: sticky. Desktop nav shows from 1367px and up; below that,
     a polished off-canvas menu slides down from the header. --}}
<header class="sticky top-0 z-50 border-b border-gray-100 bg-white/95 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-white/80">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-2 px-4 py-3 sm:py-4">
        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2 no-underline sm:gap-3">
            @if($siteSettings?->logo_url)
                <img src="{{ $siteSettings->logo_url }}" alt="" width="120" height="48" class="h-9 w-auto max-h-10 max-w-[8rem] shrink-0 object-contain sm:h-10 sm:max-h-12 sm:max-w-[10rem] md:max-w-[12rem]">
            @endif
            <span class="truncate text-lg font-bold leading-tight text-blue-700 sm:text-2xl md:text-3xl">
                {{ $brandFirst }}@if($brandRest)<span class="text-orange-500">{{ ' '.$brandRest }}</span>@endif
            </span>
        </a>

        {{-- Hamburger: visible below 1366px --}}
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

        {{-- Desktop nav: 1366px and up --}}
        <nav class="hidden items-center gap-1 min-[1367px]:flex" aria-label="{{ site_ui('nav.menu') }}">
            @php
                $link = fn ($active) => 'rounded-md px-3 py-2 text-sm font-medium transition-colors whitespace-nowrap '.($active ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700');
                $btnOutline = 'ml-1 inline-flex items-center justify-center rounded-md border-2 border-blue-600 bg-white px-3 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50 whitespace-nowrap';
                $btnNeutral = 'ml-1 inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-800 shadow-sm transition hover:bg-gray-50 whitespace-nowrap';
                $btnPrimary = 'ml-1 inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 whitespace-nowrap';
            @endphp
            <a href="{{ route('home') }}" class="{{ $link(request()->routeIs('home')) }}">{{ site_ui('nav.home') }}</a>
            <a href="{{ route('site.about') }}" class="{{ $link(request()->routeIs('site.about')) }}">{{ site_ui('nav.about') }}</a>
            <a href="{{ route('site.academics') }}" class="{{ $link(request()->routeIs('site.academics')) }}">{{ site_ui('nav.academics') }}</a>
            <a href="{{ route('site.admissions') }}" class="{{ $link(request()->routeIs('site.admissions') || request()->routeIs('admissions.*')) }}">{{ site_ui('nav.admissions') }}</a>
            <a href="{{ route('site.students') }}" class="{{ $link(request()->routeIs('site.students')) }}">{{ site_ui('nav.students') }}</a>
            <a href="{{ route('site.faculty') }}" class="{{ $link(request()->routeIs('site.faculty')) }}">{{ site_ui('nav.faculty') }}</a>
            <a href="{{ route('site.news') }}" class="{{ $link(request()->routeIs('site.news*')) }}">{{ site_ui('nav.news') }}</a>
            <a href="{{ route('site.gallery') }}" class="{{ $link(request()->routeIs('site.gallery')) }}">{{ site_ui('nav.gallery') }}</a>
            <a href="{{ route('site.contact') }}" class="{{ $link(request()->routeIs('site.contact')) }}">{{ site_ui('nav.contact') }}</a>
            <a href="{{ route('site.payments') }}" class="{{ $link(request()->routeIs('site.payments')) }}">{{ site_ui('nav.payments') }}</a>
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

    {{-- Responsive panel: visible below 1366px --}}
    <div id="site-nav-panel" data-site-nav-panel hidden
        class="min-[1367px]:hidden border-t border-gray-100 bg-white shadow-lg"
        role="dialog" aria-modal="false" aria-label="{{ site_ui('nav.menu') }}">
        <nav class="mx-auto max-h-[calc(100vh-5rem)] max-w-7xl overflow-y-auto px-4 py-4" aria-label="{{ site_ui('nav.menu') }}">
            @php
                $linkMobile = fn ($active) => 'flex items-center justify-between rounded-lg px-4 py-3 text-base font-medium transition '.($active ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-100' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700');
                $sectionTitle = 'mb-2 px-4 text-xs font-semibold uppercase tracking-wider text-gray-400';
                $btnMobilePrimary = 'block w-full rounded-lg bg-blue-600 px-4 py-3 text-center text-base font-semibold text-white shadow-sm transition hover:bg-blue-700';
                $btnMobileOutline = 'block w-full rounded-lg border-2 border-blue-600 bg-white px-4 py-3 text-center text-base font-semibold text-blue-700 transition hover:bg-blue-50';
                $btnMobileNeutral = 'block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-center text-base font-semibold text-gray-800 transition hover:bg-gray-50';
                $check = '<svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4L8.5 12l6.8-6.7a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>';
            @endphp

            <p class="{{ $sectionTitle }}">{{ __('Menu') }}</p>
            <ul class="space-y-1">
                <li><a data-site-nav-link href="{{ route('home') }}" class="{{ $linkMobile(request()->routeIs('home')) }}"><span>{{ site_ui('nav.home') }}</span>@if(request()->routeIs('home')){!! $check !!}@endif</a></li>
                <li><a data-site-nav-link href="{{ route('site.about') }}" class="{{ $linkMobile(request()->routeIs('site.about')) }}"><span>{{ site_ui('nav.about') }}</span>@if(request()->routeIs('site.about')){!! $check !!}@endif</a></li>
                <li><a data-site-nav-link href="{{ route('site.academics') }}" class="{{ $linkMobile(request()->routeIs('site.academics')) }}"><span>{{ site_ui('nav.academics') }}</span>@if(request()->routeIs('site.academics')){!! $check !!}@endif</a></li>
                <li><a data-site-nav-link href="{{ route('site.admissions') }}" class="{{ $linkMobile(request()->routeIs('site.admissions') || request()->routeIs('admissions.*')) }}"><span>{{ site_ui('nav.admissions') }}</span>@if(request()->routeIs('site.admissions') || request()->routeIs('admissions.*')){!! $check !!}@endif</a></li>
                <li><a data-site-nav-link href="{{ route('site.students') }}" class="{{ $linkMobile(request()->routeIs('site.students')) }}"><span>{{ site_ui('nav.students') }}</span>@if(request()->routeIs('site.students')){!! $check !!}@endif</a></li>
                <li><a data-site-nav-link href="{{ route('site.faculty') }}" class="{{ $linkMobile(request()->routeIs('site.faculty')) }}"><span>{{ site_ui('nav.faculty') }}</span>@if(request()->routeIs('site.faculty')){!! $check !!}@endif</a></li>
                <li><a data-site-nav-link href="{{ route('site.news') }}" class="{{ $linkMobile(request()->routeIs('site.news*')) }}"><span>{{ site_ui('nav.news') }}</span>@if(request()->routeIs('site.news*')){!! $check !!}@endif</a></li>
                <li><a data-site-nav-link href="{{ route('site.gallery') }}" class="{{ $linkMobile(request()->routeIs('site.gallery')) }}"><span>{{ site_ui('nav.gallery') }}</span>@if(request()->routeIs('site.gallery')){!! $check !!}@endif</a></li>
                <li><a data-site-nav-link href="{{ route('site.contact') }}" class="{{ $linkMobile(request()->routeIs('site.contact')) }}"><span>{{ site_ui('nav.contact') }}</span>@if(request()->routeIs('site.contact')){!! $check !!}@endif</a></li>
                <li><a data-site-nav-link href="{{ route('site.payments') }}" class="{{ $linkMobile(request()->routeIs('site.payments')) }}"><span>{{ site_ui('nav.payments') }}</span>@if(request()->routeIs('site.payments')){!! $check !!}@endif</a></li>
            </ul>

            <div class="my-4 border-t border-gray-100"></div>

            <p class="{{ $sectionTitle }}">{{ __('Account') }}</p>
            <div class="space-y-2">
                @auth
                    @php
                        $navStaffRoles = ['admin', 'teacher', 'accountant', 'staff', 'librarian'];
                        $navIsStaff = auth()->user()->hasAnyRole($navStaffRoles);
                    @endphp
                    @if($navIsStaff)
                        <a data-site-nav-link href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard*') ? $btnMobilePrimary : $btnMobileOutline }}">{{ site_ui('nav.dashboard') }}</a>
                    @else
                        <a data-site-nav-link href="{{ route('portal') }}" class="{{ request()->routeIs('portal') || request()->routeIs('portal.*') ? $btnMobilePrimary : $btnMobileOutline }}">{{ site_ui('nav.portal') }}</a>
                    @endif
                    <form method="post" action="{{ route('logout') }}" data-site-nav-link>
                        @csrf
                        <button type="submit" class="{{ $btnMobileNeutral }}">{{ site_ui('nav.logout') }}</button>
                    </form>
                @else
                    <a data-site-nav-link href="{{ route('portal.register') }}" class="{{ $btnMobileOutline }}">{{ site_ui('nav.register') }}</a>
                    <a data-site-nav-link href="{{ route('login') }}" class="{{ $btnMobilePrimary }}">{{ site_ui('nav.login') }}</a>
                @endauth
            </div>

            <div class="my-4 border-t border-gray-100"></div>

            <p class="{{ $sectionTitle }}">{{ __('Language') }}</p>
            <div class="flex flex-wrap gap-2 px-1">
                @foreach (config('school.supported_locales', ['en']) as $loc)
                    <a data-site-nav-link href="{{ route('locale.switch', ['locale' => $loc]) }}"
                        class="inline-flex min-w-[3rem] items-center justify-center rounded-md border px-3 py-1.5 text-xs font-bold uppercase tracking-wide transition {{ app()->getLocale() === $loc ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-600 hover:border-blue-300 hover:text-blue-700' }}">
                        {{ $loc }}
                    </a>
                @endforeach
            </div>

            @if($phoneReal || $emailReal || $addrReal)
                <div class="my-4 border-t border-gray-100"></div>
                <p class="{{ $sectionTitle }}">{{ __('Contact') }}</p>
                <ul class="space-y-2 px-1 text-sm text-gray-600">
                    @if($phoneReal)
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                            <a href="tel:{{ preg_replace('/\s+/', '', $phoneReal) }}" class="hover:text-blue-700">{{ $phone }}</a>
                        </li>
                    @endif
                    @if($emailReal)
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
                            <a href="mailto:{{ $emailReal }}" class="break-all hover:text-blue-700">{{ $email }}</a>
                        </li>
                    @endif
                    @if($addrReal)
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                            <span>{{ \Illuminate\Support\Str::limit($addr, 80) }}</span>
                        </li>
                    @endif
                </ul>
            @endif
        </nav>
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

        // Close when any link inside the panel is activated.
        panel.addEventListener('click', function (e) {
            var link = e.target.closest('[data-site-nav-link]');
            if (link) setOpen(false);
        });

        // Close on Escape.
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !panel.hasAttribute('hidden')) {
                setOpen(false);
                trigger.focus();
            }
        });

        // If the viewport grows past 1366px, hide the panel automatically.
        desktopQuery.addEventListener('change', function (e) {
            if (e.matches) setOpen(false);
        });
    })();
</script>
