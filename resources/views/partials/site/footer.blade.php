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
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
            {{-- Column 1: School info + social --}}
            <div>
                <div class="flex items-center gap-3">
                    @if($siteSettings->footer_logo_url ?? false)
                        <img src="{{ $siteSettings->footer_logo_url }}" alt="{{ $school }}" class="h-10 w-10 rounded-lg object-contain ring-1 ring-white/10">
                    @elseif($siteSettings->logo_url ?? false)
                        <img src="{{ $siteSettings->logo_url }}" alt="{{ $school }}" class="h-10 w-10 rounded-lg object-contain ring-1 ring-white/10">
                    @else
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600 text-lg font-bold text-white">{{ substr($school, 0, 1) }}</span>
                    @endif
                    <div>
                        <p class="text-base font-bold text-white">{{ $school }}</p>
                        <p class="text-xs text-slate-400">{{ $siteSettings->tagline ?? __('Excellence in Education') }}</p>
                    </div>
                </div>
                <p class="mt-4 text-sm leading-relaxed text-slate-400">{{ $siteSettings->footer_description ?? site_ui('footer.about_fallback') }}</p>
            </div>

            {{-- Column 2: Quick Links --}}
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-white">{{ site_ui('footer.quick_links_title') }}</h3>
                <ul class="mt-4 space-y-2.5">
                    <li><a href="{{ route('site.about') }}" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('nav.about') }}</a></li>
                    <li><a href="{{ route('site.academics') }}" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('nav.academics') }}</a></li>
                    <li><a href="{{ route('site.admissions') }}" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('nav.admissions') }}</a></li>
                    <li><a href="{{ route('site.faculty') }}" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('nav.faculty') }}</a></li>
                    <li><a href="{{ route('site.committee') }}" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('nav.committee') }}</a></li>
                    <li><a href="{{ route('site.news') }}" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('nav.news') }}</a></li>
                    <li><a href="{{ route('site.gallery') }}" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('nav.gallery') }}</a></li>
                </ul>
            </div>

            {{-- Column 3: Important / ministry links --}}
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-white">{{ site_ui('footer.important_title') }}</h3>
                <ul class="mt-4 space-y-2.5">
                    @php
                        $ministryLinks = collect(site_ui('footer.ministry_links', []));
                    @endphp
                    @forelse($ministryLinks as $entry)
                        @php
                            if (is_array($entry)) {
                                $label = $entry['label'] ?? $entry['title'] ?? '';
                                $url = $entry['url'] ?? $entry['href'] ?? '#';
                            } else {
                                [$label, $url] = array_pad(explode('|', (string) $entry, 2), 2, '#');
                            }
                        @endphp
                        @if($label)
                            <li>
                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-start gap-2 text-sm text-slate-400 transition-colors hover:text-white">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 010 5.656l-4 4a4 4 0 01-5.656-5.656l1.5-1.5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.172 13.828a4 4 0 010-5.656l4-4a4 4 0 015.656 5.656l-1.5 1.5"/></svg>
                                    {{ $label }}
                                </a>
                            </li>
                        @endif
                    @empty
                        <li><a href="https://www.moedu.gov.bd" target="_blank" rel="noopener noreferrer" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('footer.link_ministry_education_ministry') }}</a></li>
                        <li><a href="#" class="text-sm text-slate-400 transition-colors hover:text-white">{{ site_ui('footer.link_ministry_primary_education') }}</a></li>
                    @endforelse
                    <li class="pt-2"><a href="{{ route('site.transport') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-300 transition-colors hover:text-white">{{ site_ui('nav.transport') }}</a></li>
                </ul>
            </div>

            {{-- Column 4: Contact, Follow Us & Newsletter --}}
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-white">{{ site_ui('footer.contact_title') }}</h3>
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

                <div class="mt-6">
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-white">{{ site_ui('footer.follow_us') }}</h4>
                    <div class="mt-3 flex gap-3">
                        @include('partials.site.social-links', ['settings' => $siteSettings, 'linkClass' => 'flex h-9 w-9 items-center justify-center rounded-full bg-slate-800 text-slate-300 ring-1 ring-slate-700 transition hover:bg-brand-600 hover:text-white', 'placeholderClass' => 'opacity-50'])
                    </div>
                </div>

                {{-- Newsletter --}}
                <div class="mt-6">
                    <h4 class="text-sm font-semibold text-white">{{ site_ui('footer.newsletter_title') }}</h4>
                    <form action="{{ route('site.newsletter.store') }}" method="post" class="mt-2 flex gap-2" novalidate>
                        @csrf
                        <input type="email" name="email" id="newsletter-email" required placeholder="{{ site_ui('footer.newsletter_placeholder') }}" aria-label="{{ site_ui('footer.newsletter_email_label') }}" class="min-w-0 flex-1 rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        <button type="submit" class="rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700" aria-label="{{ site_ui('footer.newsletter_button') }}">
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
            <p class="text-xs text-slate-500">&copy; {{ $year }} {{ $school }}. {{ site_ui('footer.copyright_suffix') }}</p>
            <div class="flex gap-4">
                <a href="{{ route('site.privacy') }}" class="text-xs text-slate-500 transition-colors hover:text-slate-300">{{ site_ui('footer.link_privacy') }}</a>
                <a href="{{ route('site.terms') }}" class="text-xs text-slate-500 transition-colors hover:text-slate-300">{{ site_ui('footer.link_terms') }}</a>
                <a href="{{ route('site.sitemap') }}" class="text-xs text-slate-500 transition-colors hover:text-slate-300">{{ site_ui('footer.link_sitemap') }}</a>
            </div>
        </div>
    </div>
</footer>
