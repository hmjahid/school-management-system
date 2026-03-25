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

<div class="bg-blue-900 text-sm text-white">
    <div class="container mx-auto flex flex-col items-center justify-between gap-3 px-4 py-2.5 md:flex-row">
        <div class="flex flex-col flex-wrap items-center justify-center gap-x-6 gap-y-2 sm:flex-row sm:justify-start">
            <span class="inline-flex items-center gap-2 {{ $phoneReal ? '' : 'opacity-80' }}" @if(! $phoneReal) title="{{ $phTip }}" @endif>
                <svg class="h-4 w-4 shrink-0 text-blue-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                @if($phoneReal)
                    <a href="tel:{{ preg_replace('/\s+/', '', $phoneReal) }}" class="font-medium hover:text-blue-100">{{ $phone }}</a>
                @else
                    <span class="border-b border-dotted border-blue-300/80 font-medium italic">{{ $phone }}</span>
                @endif
            </span>
            <span class="inline-flex items-center gap-2 {{ $emailReal ? '' : 'opacity-80' }}" @if(! $emailReal) title="{{ $phTip }}" @endif>
                <svg class="h-4 w-4 shrink-0 text-blue-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
                @if($emailReal)
                    <a href="mailto:{{ $emailReal }}" class="font-medium hover:text-blue-100 break-all">{{ $email }}</a>
                @else
                    <span class="border-b border-dotted border-blue-300/80 font-medium italic break-all">{{ $email }}</span>
                @endif
            </span>
            <span class="inline-flex max-w-xl items-start gap-2 text-center sm:text-left {{ $addrReal ? '' : 'opacity-80' }}" @if(! $addrReal) title="{{ $phTip }}" @endif>
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-blue-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                @if($addrReal)
                    <span class="text-blue-100">{{ \Illuminate\Support\Str::limit($addr, 120) }}</span>
                @else
                    <span class="border-b border-dotted border-blue-300/80 text-blue-100 italic">{{ \Illuminate\Support\Str::limit($addr, 120) }}</span>
                @endif
            </span>
        </div>
        <div class="flex flex-wrap items-center justify-center gap-3 md:justify-end">
            <div class="flex items-center gap-1.5">
                @foreach (config('school.supported_locales', ['en']) as $loc)
                    <a href="{{ route('locale.switch', ['locale' => $loc]) }}"
                        class="inline-flex min-w-[2.25rem] items-center justify-center rounded-md border px-2.5 py-1 text-xs font-bold uppercase tracking-wide transition {{ app()->getLocale() === $loc ? 'border-white bg-white/15 text-white' : 'border-blue-400/60 text-blue-200 hover:border-white hover:text-white' }}">
                        {{ $loc }}
                    </a>
                @endforeach
            </div>
            <span class="hidden h-6 w-px bg-blue-600 sm:block" aria-hidden="true"></span>
            <div class="flex items-center gap-3 text-blue-200">
                @include('partials.site.social-links', ['settings' => $siteSettings, 'linkClass' => 'text-blue-200 hover:text-white', 'placeholderClass' => 'opacity-55'])
            </div>
        </div>
    </div>
</div>

<header class="sticky top-0 z-50 bg-white shadow-md">
    <input type="checkbox" id="site-nav-toggle" class="peer sr-only" aria-hidden="true">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between py-4">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3 no-underline">
                @if($siteSettings?->logo_url)
                    <img src="{{ $siteSettings->logo_url }}" alt="" width="120" height="48" class="h-10 w-auto max-h-12 max-w-[10rem] shrink-0 object-contain md:h-12 md:max-w-[12rem]">
                @endif
                <span class="truncate text-2xl font-bold text-blue-700 md:text-3xl">{{ $brandFirst }}@if($brandRest)<span class="text-orange-500">{{ ' '.$brandRest }}</span>@endif</span>
            </a>

            <label for="site-nav-toggle" class="cursor-pointer rounded-md p-2 text-gray-700 hover:bg-blue-50 md:hidden" aria-label="{{ site_ui('nav.menu') }}">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </label>

            <nav class="absolute left-0 right-0 top-full z-50 hidden max-h-[calc(100vh-5rem)] flex-col gap-2 overflow-y-auto border-t border-gray-100 bg-white px-4 py-4 shadow-lg peer-checked:flex md:static md:ml-6 md:flex md:max-h-none md:flex-1 md:flex-row md:flex-wrap md:items-center md:justify-end md:gap-2 md:border-0 md:bg-transparent md:px-0 md:py-0 md:shadow-none">
                @php
                    $link = fn ($active) => 'rounded-md px-4 py-2 text-sm font-medium transition-colors '.($active ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700');
                    $btnOutline = 'inline-flex w-full items-center justify-center rounded-md border-2 border-blue-600 bg-white px-4 py-2 text-center text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50 md:w-auto';
                    $btnNeutral = 'inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-center text-sm font-semibold text-gray-800 shadow-sm transition hover:bg-gray-50 md:w-auto';
                    $btnPrimary = 'inline-flex w-full items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 md:w-auto';
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
                    @unless($navIsStaff)
                        <a href="{{ route('portal') }}" class="{{ request()->routeIs('portal') || request()->routeIs('portal.*') ? $btnPrimary : $btnOutline }}">{{ site_ui('nav.portal') }}</a>
                    @endunless
                    @if($navIsStaff)
                        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard*') ? $btnPrimary : $btnOutline }}">{{ site_ui('nav.dashboard') }}</a>
                    @endif
                    <form method="post" action="{{ route('logout') }}" class="w-full md:w-auto">
                        @csrf
                        <button type="submit" class="{{ $btnNeutral }} w-full">{{ site_ui('nav.logout') }}</button>
                    </form>
                @else
                    <a href="{{ route('portal.register') }}" class="{{ $btnOutline }}">{{ site_ui('nav.register') }}</a>
                    <a href="{{ route('login') }}" class="{{ $btnPrimary }}">{{ site_ui('nav.login') }}</a>
                @endauth
            </nav>
        </div>
    </div>
</header>
