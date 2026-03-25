@php
    $school = $siteSettings->school_name ?? config('app.name', 'School');
    $words = preg_split('/\s+/', trim($school), 2);
    $brandFirst = $words[0] ?? $school;
    $brandRest = $words[1] ?? '';
    $phone = $siteSettings->phone ?? null;
    $email = $siteSettings->email ?? null;
    $addr = $siteSettings->full_address ?? $siteSettings->address ?? null;
@endphp

<div class="bg-blue-900 text-sm text-white">
    <div class="container mx-auto flex flex-col items-center justify-between gap-2 px-4 py-2 md:flex-row">
        <div class="flex flex-col flex-wrap items-center gap-x-6 gap-y-1 sm:flex-row">
            @if($phone)
                <span class="inline-flex items-center gap-2">
                    <svg class="h-3.5 w-3.5 shrink-0 text-blue-300" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                    <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="hover:text-blue-200">{{ $phone }}</a>
                </span>
            @endif
            @if($email)
                <span class="inline-flex items-center gap-2">
                    <svg class="h-3.5 w-3.5 shrink-0 text-blue-300" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
                    <a href="mailto:{{ $email }}" class="hover:text-blue-200">{{ $email }}</a>
                </span>
            @endif
            @if($addr)
                <span class="inline-flex max-w-md items-start gap-2 text-center sm:text-left">
                    <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-blue-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                    <span class="text-blue-100">{{ \Illuminate\Support\Str::limit($addr, 80) }}</span>
                </span>
            @endif
        </div>
        <div class="flex items-center gap-4 text-blue-200">
            @foreach (config('school.supported_locales', ['en']) as $loc)
                <a href="{{ route('locale.switch', ['locale' => $loc]) }}" class="text-xs font-semibold uppercase hover:text-white {{ app()->getLocale() === $loc ? 'text-white' : '' }}">{{ $loc }}</a>
            @endforeach
            @if($siteSettings?->facebook_url)
                <a href="{{ $siteSettings->facebook_url }}" class="hover:text-white" rel="noopener noreferrer" target="_blank" aria-label="Facebook">FB</a>
            @endif
            @if($siteSettings?->twitter_url)
                <a href="{{ $siteSettings->twitter_url }}" class="hover:text-white" rel="noopener noreferrer" target="_blank" aria-label="X">X</a>
            @endif
            @if($siteSettings?->instagram_url)
                <a href="{{ $siteSettings->instagram_url }}" class="hover:text-white" rel="noopener noreferrer" target="_blank" aria-label="Instagram">IG</a>
            @endif
            @if($siteSettings?->youtube_url)
                <a href="{{ $siteSettings->youtube_url }}" class="hover:text-white" rel="noopener noreferrer" target="_blank" aria-label="YouTube">YT</a>
            @endif
        </div>
    </div>
</div>

<header class="sticky top-0 z-50 bg-white shadow-md">
    <input type="checkbox" id="site-nav-toggle" class="peer sr-only" aria-hidden="true">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between py-4">
            <a href="{{ route('home') }}" class="flex items-center no-underline">
                <span class="text-2xl font-bold text-blue-700 md:text-3xl">{{ $brandFirst }}@if($brandRest)<span class="text-orange-500">{{ ' '.$brandRest }}</span>@endif</span>
            </a>

            <label for="site-nav-toggle" class="cursor-pointer rounded-md p-2 text-gray-700 hover:bg-blue-50 md:hidden" aria-label="{{ __('Menu') }}">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </label>

            <nav class="absolute left-0 right-0 top-full z-50 hidden max-h-[calc(100vh-5rem)] flex-col gap-1 overflow-y-auto border-t border-gray-100 bg-white px-4 py-4 shadow-lg peer-checked:flex md:static md:ml-6 md:flex md:max-h-none md:flex-1 md:flex-row md:flex-wrap md:items-center md:justify-end md:gap-1 md:border-0 md:bg-transparent md:px-0 md:py-0 md:shadow-none">
                @php
                    $link = fn ($active) => 'rounded-md px-4 py-2 text-sm font-medium transition-colors '.($active ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700');
                @endphp
                <a href="{{ route('home') }}" class="{{ $link(request()->routeIs('home')) }}">{{ __('Home') }}</a>
                <a href="{{ route('site.about') }}" class="{{ $link(request()->routeIs('site.about')) }}">{{ __('About Us') }}</a>
                <a href="{{ route('site.academics') }}" class="{{ $link(request()->routeIs('site.academics')) }}">{{ __('Academics') }}</a>
                <a href="{{ route('site.admissions') }}" class="{{ $link(request()->routeIs('site.admissions') || request()->routeIs('admissions.*')) }}">{{ __('Admissions') }}</a>
                <a href="{{ route('site.students') }}" class="{{ $link(request()->routeIs('site.students')) }}">{{ __('Students') }}</a>
                <a href="{{ route('site.faculty') }}" class="{{ $link(request()->routeIs('site.faculty')) }}">{{ __('Faculty') }}</a>
                <a href="{{ route('site.news') }}" class="{{ $link(request()->routeIs('site.news*')) }}">{{ __('News & Events') }}</a>
                <a href="{{ route('site.gallery') }}" class="{{ $link(request()->routeIs('site.gallery')) }}">{{ __('Gallery') }}</a>
                <a href="{{ route('site.contact') }}" class="{{ $link(request()->routeIs('site.contact')) }}">{{ __('Contact') }}</a>
                <a href="{{ route('site.payments') }}" class="{{ $link(request()->routeIs('site.payments')) }}">{{ __('Payments') }}</a>
                @auth
                    <a href="{{ route('portal') }}" class="{{ $link(request()->routeIs('portal')) }}">{{ __('Portal') }}</a>
                    <a href="{{ route('dashboard') }}" class="{{ $link(request()->routeIs('dashboard*')) }}">{{ __('Dashboard') }}</a>
                    <form method="post" action="{{ route('logout') }}" class="md:inline">
                        @csrf
                        <button type="submit" class="w-full rounded-md px-4 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-100 md:w-auto">{{ __('Log out') }}</button>
                    </form>
                @else
                    <a href="{{ route('portal.register') }}" class="{{ $link(false) }}">{{ __('Register') }}</a>
                    <a href="{{ route('login') }}" class="rounded-md bg-blue-600 px-6 py-2 text-center text-sm font-semibold text-white hover:bg-blue-700">{{ __('Login') }}</a>
                @endauth
            </nav>
        </div>
    </div>
</header>
