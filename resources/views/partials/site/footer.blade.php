@php
    $school = $siteSettings?->localized_school_name ?: ($siteSettings?->school_name ?? config('app.name', 'School'));
    $year = date('Y');
    $fPhoneReal = $siteSettings?->phone ?? config('school.contact_phone');
    $fEmailReal = $siteSettings?->email ?? config('school.contact_email');
    $fAddrReal = $siteSettings?->full_address ?? $siteSettings?->address ?? config('school.contact_address');
    $fPhone = $fPhoneReal ?: config('school.placeholder_phone');
    $fEmail = $fEmailReal ?: config('school.placeholder_email');
    $fAddr = $fAddrReal ?: config('school.placeholder_address');
@endphp
<footer class="no-print border-t border-slate-200 bg-slate-900 text-slate-300">
    {{-- Main footer columns --}}
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Column 1: School info --}}
            <div>
                <div class="flex items-center gap-3">
                    @if($siteSettings->logo_url ?? false)
                        <img src="{{ $siteSettings->logo_url }}" alt="" class="h-10 w-10 rounded-lg object-cover ring-1 ring-white/10">
                    @else
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600 text-lg font-bold text-white">{{ substr($school, 0, 1) }}</span>
                    @endif
                    <div>
                        <p class="text-base font-bold text-white">{{ $school }}</p>
                        <p class="text-xs text-slate-400">{{ $siteSettings->tagline ?? __('Excellence in Education') }}</p>
                    </div>
                </div>
                <p class="mt-4 text-sm leading-relaxed text-slate-400">{{ $siteSettings->footer_description ?? site_ui('footer.description') }}</p>
                @include('partials.site.social-links', ['settings' => $siteSettings, 'linkClass' => 'text-slate-400 hover:text-white', 'placeholderClass' => 'opacity-50'])
            </div>

            {{-- Column 2: Quick Links --}}
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-white">{{ site_ui('footer.quick_links') }}</h3>
                <ul class="mt-4 space-y-2.5">
                    <li><a href="{{ route('site.about') }}" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('nav.about') }}</a></li>
                    <li><a href="{{ route('site.academics') }}" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('nav.academics') }}</a></li>
                    <li><a href="{{ route('site.admissions') }}" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('nav.admissions') }}</a></li>
                    <li><a href="{{ route('site.faculty') }}" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('nav.faculty') }}</a></li>
                    <li><a href="{{ route('site.news') }}" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('nav.news') }}</a></li>
                    <li><a href="{{ route('site.events') }}" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('nav.events') }}</a></li>
                    <li><a href="{{ route('site.gallery') }}" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('nav.gallery') }}</a></li>
                    <li><a href="{{ route('site.contact') }}" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('nav.contact') }}</a></li>
                </ul>
            </div>

            {{-- Column 3: Programs / Academics --}}
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-white">{{ site_ui('footer.programs') }}</h3>
                <ul class="mt-4 space-y-2.5">
                    <li><a href="#" class="text-sm text-slate-400 transition-colors hover:text-white">{{ __('Play to KG-2') }}</a></li>
                    <li><a href="#" class="text-sm text-slate-400 transition-colors hover:text-white">{{ __('Primary (Class 1-5)') }}</a></li>
                    <li><a href="#" class="text-sm text-slate-400 transition-colors hover:text-white">{{ __('Junior (Class 6-8)') }}</a></li>
                    <li><a href="#" class="text-sm text-slate-400 transition-colors hover:text-white">{{ __('Secondary (Class 9-10)') }}</a></li>
                    <li><a href="#" class="text-sm text-slate-400 transition-colors hover:text-white">{{ __('Extracurricular') }}</a></li>
                    <li><a href="{{ route('site.transport') }}" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('nav.transport') }}</a></li>
                </ul>
            </div>

            {{-- Column 4: Contact & Newsletter --}}
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-white">{{ site_ui('footer.contact') }}</h3>
                <ul class="mt-4 space-y-3">
                    <li class="flex items-start gap-2">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="text-sm text-slate-400">{{ $fAddr }}</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="text-sm text-slate-400">{{ $fEmail }}</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span class="text-sm text-slate-400">{{ $fPhone }}</span>
                    </li>
                </ul>

                {{-- Newsletter --}}
                <div class="mt-6">
                    <h4 class="text-sm font-semibold text-white">{{ __('Newsletter') }}</h4>
                    <form class="mt-2 flex gap-2">
                        <input type="email" placeholder="{{ __('Your email') }}" class="min-w-0 flex-1 rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        <button type="submit" class="rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom bar --}}
    <div class="border-t border-slate-800">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-4 sm:flex-row sm:px-6 lg:px-8">
            <p class="text-xs text-slate-500">&copy; {{ $year }} {{ $school }}. {{ site_ui('footer.copyright') }}</p>
            <div class="flex gap-4">
                <a href="{{ route('site.privacy') }}" class="text-xs text-slate-500 transition-colors hover:text-slate-300">{{ site_ui('footer.privacy') }}</a>
                <a href="{{ route('site.terms') }}" class="text-xs text-slate-500 transition-colors hover:text-slate-300">{{ site_ui('footer.terms') }}</a>
                <a href="{{ route('site.sitemap') }}" class="text-xs text-slate-500 transition-colors hover:text-slate-300">{{ __('Sitemap') }}</a>
            </div>
        </div>
    </div>
</footer>
