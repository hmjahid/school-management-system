@php
    $school = $siteSettings?->school_name ?? config('app.name', 'School');
    $year = date('Y');
    $fPhoneReal = $siteSettings?->phone ?? config('school.contact_phone');
    $fEmailReal = $siteSettings?->email ?? config('school.contact_email');
    $fAddrReal = $siteSettings?->full_address ?? $siteSettings?->address ?? config('school.contact_address');
    $fPhone = $fPhoneReal ?: config('school.placeholder_phone');
    $fEmail = $fEmailReal ?: config('school.placeholder_email');
    $fAddr = $fAddrReal ?: config('school.placeholder_address');
    $fPhTip = __('Example — set real details in Dashboard → School settings');
@endphp
<footer class="bg-gray-900 pb-6 pt-12 text-white">
    <div class="container mx-auto px-4">
        <div class="mb-8 grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-5">
            <div>
                <h3 class="mb-4 text-xl font-bold text-orange-400">{{ site_ui('footer.about_title') }}</h3>
                <p class="mb-4 text-gray-300">
                    {{ $siteSettings?->tagline ?? site_ui('footer.about_fallback') }}
                </p>
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Follow us') }}</p>
                <div class="flex flex-wrap items-center gap-3 text-gray-400">
                    @include('partials.site.social-links', ['settings' => $siteSettings, 'linkClass' => 'text-gray-400 hover:text-white', 'placeholderClass' => 'opacity-50'])
                </div>
            </div>

            <div>
                <h3 class="mb-4 text-xl font-bold text-orange-400">{{ site_ui('footer.quick_links_title') }}</h3>
                <ul class="space-y-2">
                    <li><a href="{{ route('site.about') }}" class="text-gray-300 transition-colors hover:text-white">{{ site_ui('footer.link_about_school') }}</a></li>
                    <li><a href="{{ route('site.academics') }}" class="text-gray-300 transition-colors hover:text-white">{{ site_ui('footer.link_academics') }}</a></li>
                    <li><a href="{{ route('site.admissions') }}" class="text-gray-300 transition-colors hover:text-white">{{ site_ui('footer.link_admissions') }}</a></li>
                    <li><a href="{{ route('admissions.apply') }}" class="text-gray-300 transition-colors hover:text-white">{{ site_ui('footer.link_apply_online') }}</a></li>
                    <li><a href="{{ route('site.news') }}" class="text-gray-300 transition-colors hover:text-white">{{ site_ui('footer.link_news') }}</a></li>
                    <li><a href="{{ route('site.gallery') }}" class="text-gray-300 transition-colors hover:text-white">{{ site_ui('footer.link_gallery') }}</a></li>
                    <li><a href="{{ route('site.contact') }}" class="text-gray-300 transition-colors hover:text-white">{{ site_ui('footer.link_contact') }}</a></li>
                    <li><a href="{{ route('site.payments') }}" class="text-gray-300 transition-colors hover:text-white">{{ site_ui('footer.link_payments') }}</a></li>
                </ul>
            </div>

            <div>
                <h3 class="mb-4 text-xl font-bold text-orange-400">{{ site_ui('footer.legal_title') }}</h3>
                <ul class="space-y-2">
                    <li><a href="{{ route('site.terms') }}" class="text-gray-300 transition-colors hover:text-white">{{ site_ui('footer.link_terms') }}</a></li>
                    <li><a href="{{ route('site.privacy') }}" class="text-gray-300 transition-colors hover:text-white">{{ site_ui('footer.link_privacy') }}</a></li>
                    <li class="pt-1 text-xs text-gray-500">{{ site_ui('footer.legal_note') }}</li>
                </ul>
            </div>

            <div>
                <h3 class="mb-4 text-xl font-bold text-orange-400">{{ site_ui('footer.contact_title') }}</h3>
                <ul class="space-y-3 text-gray-300">
                    <li class="flex items-start gap-2 {{ $fAddrReal ? '' : 'opacity-80' }}" @if(! $fAddrReal) title="{{ $fPhTip }}" @endif>
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-orange-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                        @if($fAddrReal)
                            <span>{{ $fAddr }}</span>
                        @else
                            <span class="border-b border-dotted border-gray-500 italic">{{ $fAddr }}</span>
                        @endif
                    </li>
                    <li class="flex items-center gap-2 {{ $fPhoneReal ? '' : 'opacity-80' }}" @if(! $fPhoneReal) title="{{ $fPhTip }}" @endif>
                        <svg class="h-5 w-5 shrink-0 text-orange-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                        @if($fPhoneReal)
                            <a href="tel:{{ preg_replace('/\s+/', '', $fPhoneReal) }}" class="hover:text-white">{{ $fPhone }}</a>
                        @else
                            <span class="border-b border-dotted border-gray-500 italic">{{ $fPhone }}</span>
                        @endif
                    </li>
                    <li class="flex items-center gap-2 {{ $fEmailReal ? '' : 'opacity-80' }}" @if(! $fEmailReal) title="{{ $fPhTip }}" @endif>
                        <svg class="h-5 w-5 shrink-0 text-orange-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
                        @if($fEmailReal)
                            <a href="mailto:{{ $fEmailReal }}" class="break-all hover:text-white">{{ $fEmail }}</a>
                        @else
                            <span class="border-b border-dotted border-gray-500 italic break-all">{{ $fEmail }}</span>
                        @endif
                    </li>
                </ul>
                <p class="mb-2 mt-5 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Follow us') }}</p>
                <div class="flex flex-wrap items-center gap-3 text-gray-400">
                    @include('partials.site.social-links', ['settings' => $siteSettings, 'linkClass' => 'text-gray-400 hover:text-white', 'placeholderClass' => 'opacity-50'])
                </div>
            </div>

            <div>
                <h3 class="mb-4 text-xl font-bold text-orange-400">{{ site_ui('footer.newsletter_title') }}</h3>
                <p class="mb-4 text-gray-300">{{ site_ui('footer.newsletter_intro') }}</p>
                <form method="post" action="{{ route('site.newsletter.store') }}" class="flex flex-col gap-2 sm:flex-row">
                    @csrf
                    <input type="email" name="email" required placeholder="{{ site_ui('footer.newsletter_placeholder') }}"
                        class="w-full rounded-l-md rounded-r-md px-4 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-orange-500 sm:rounded-r-none sm:rounded-l-md">
                    <button type="submit" class="rounded-r-md rounded-l-md bg-orange-500 px-4 py-2 font-medium text-white transition-colors hover:bg-orange-600 sm:rounded-l-none sm:rounded-r-md">
                        {{ site_ui('footer.newsletter_button') }}
                    </button>
                </form>
            </div>
        </div>

        <div class="border-t border-gray-800 pt-6">
            <div class="flex flex-col items-center justify-between gap-4 md:flex-row">
                <p class="text-center text-sm text-gray-400 md:text-left">
                    © {{ $year }} {{ $school }}. {{ site_ui('footer.copyright_suffix') }}
                </p>
            </div>
        </div>
    </div>
</footer>
