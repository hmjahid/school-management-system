@php
    $school = $siteSettings->school_name ?? config('app.name', 'School');
    $year = date('Y');
@endphp
<footer class="bg-gray-900 pb-6 pt-12 text-white">
    <div class="container mx-auto px-4">
        <div class="mb-8 grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-5">
            <div>
                <h3 class="mb-4 text-xl font-bold text-orange-400">{{ __('About Us') }}</h3>
                <p class="mb-4 text-gray-300">
                    {{ $siteSettings->tagline ?? __('A premier educational institution committed to excellence in teaching, learning, and community service.') }}
                </p>
                <div class="flex space-x-4 text-gray-400">
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

            <div>
                <h3 class="mb-4 text-xl font-bold text-orange-400">{{ __('Quick Links') }}</h3>
                <ul class="space-y-2">
                    <li><a href="{{ route('site.about') }}" class="text-gray-300 transition-colors hover:text-white">{{ __('About School') }}</a></li>
                    <li><a href="{{ route('site.academics') }}" class="text-gray-300 transition-colors hover:text-white">{{ __('Academics') }}</a></li>
                    <li><a href="{{ route('site.admissions') }}" class="text-gray-300 transition-colors hover:text-white">{{ __('Admissions') }}</a></li>
                    <li><a href="{{ route('admissions.apply') }}" class="text-gray-300 transition-colors hover:text-white">{{ __('Apply online') }}</a></li>
                    <li><a href="{{ route('site.news') }}" class="text-gray-300 transition-colors hover:text-white">{{ __('News & Events') }}</a></li>
                    <li><a href="{{ route('site.gallery') }}" class="text-gray-300 transition-colors hover:text-white">{{ __('Photo Gallery') }}</a></li>
                    <li><a href="{{ route('site.contact') }}" class="text-gray-300 transition-colors hover:text-white">{{ __('Contact Us') }}</a></li>
                    <li><a href="{{ route('site.payments') }}" class="text-gray-300 transition-colors hover:text-white">{{ __('Payments') }}</a></li>
                </ul>
            </div>

            <div>
                <h3 class="mb-4 text-xl font-bold text-orange-400">{{ __('Legal') }}</h3>
                <ul class="space-y-2">
                    <li><span class="text-gray-400">{{ __('Terms & privacy pages can be added as CMS routes.') }}</span></li>
                </ul>
            </div>

            <div>
                <h3 class="mb-4 text-xl font-bold text-orange-400">{{ __('Contact Us') }}</h3>
                <ul class="space-y-3 text-gray-300">
                    @if($siteSettings && ($siteSettings->full_address ?? $siteSettings->address))
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5 text-orange-400" aria-hidden="true">◎</span>
                            <span>{{ $siteSettings->full_address ?? $siteSettings->address }}</span>
                        </li>
                    @endif
                    @if($siteSettings?->phone)
                        <li class="flex items-center gap-2">
                            <span class="text-orange-400" aria-hidden="true">☎</span>
                            <a href="tel:{{ preg_replace('/\s+/', '', $siteSettings->phone) }}" class="hover:text-white">{{ $siteSettings->phone }}</a>
                        </li>
                    @endif
                    @if($siteSettings?->email)
                        <li class="flex items-center gap-2">
                            <span class="text-orange-400" aria-hidden="true">✉</span>
                            <a href="mailto:{{ $siteSettings->email }}" class="hover:text-white">{{ $siteSettings->email }}</a>
                        </li>
                    @endif
                </ul>
            </div>

            <div>
                <h3 class="mb-4 text-xl font-bold text-orange-400">{{ __('Newsletter') }}</h3>
                <p class="mb-4 text-gray-300">{{ __('Subscribe for the latest updates and news.') }}</p>
                <form method="post" action="{{ route('site.newsletter.store') }}" class="flex flex-col gap-2 sm:flex-row">
                    @csrf
                    <input type="email" name="email" required placeholder="{{ __('Your email address') }}"
                        class="w-full rounded-l-md rounded-r-md px-4 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-orange-500 sm:rounded-r-none sm:rounded-l-md">
                    <button type="submit" class="rounded-r-md rounded-l-md bg-orange-500 px-4 py-2 font-medium text-white transition-colors hover:bg-orange-600 sm:rounded-l-none sm:rounded-r-md">
                        {{ __('Subscribe') }}
                    </button>
                </form>
            </div>
        </div>

        <div class="border-t border-gray-800 pt-6">
            <div class="flex flex-col items-center justify-between gap-4 md:flex-row">
                <p class="text-center text-sm text-gray-400 md:text-left">
                    © {{ $year }} {{ $school }}. {{ __('All rights reserved.') }}
                </p>
            </div>
        </div>
    </div>
</footer>
