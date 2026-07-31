{{--
    Hero design 6 — School name with hero slider.
    Variables: $hero, $heroImg, $headline, $sub, $siteSettings, $admissionsOpen, $sliderSlides
--}}
@php
    $slides = collect($sliderSlides ?? []);
@endphp

<style>
    @keyframes hero6-fade {
        0%, 16.66% { opacity: 1; }
        20%, 100% { opacity: 0; }
    }
</style>

<section class="relative min-h-[85vh] flex items-center overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950">
    {{-- Right-side slider --}}
    <div class="absolute inset-0 lg:static lg:inset-auto lg:col-span-7">
        @if($slides->isNotEmpty())
            <div class="relative h-full w-full overflow-hidden">
                @foreach($slides as $i => $slide)
                    <div
                        class="absolute inset-0 transition-opacity duration-1000 {{ $i === 0 ? 'opacity-100' : 'opacity-0' }}"
                        @if($i > 0) style="animation: hero6-fade {{ count($slides) * 4 }}s {{ $i * 4 }}s infinite;" @endif
                    >
                        @if(!empty($slide['image']))
                            <img src="{{ $slide['image'] }}" alt="{{ $slide['title'] ?? '' }}" class="h-full w-full object-cover" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" width="1920" height="1080">
                        @else
                            <div class="h-full w-full bg-gradient-to-br from-indigo-600 to-purple-700"></div>
                        @endif
                    </div>
                @endforeach
                <div class="absolute inset-0 bg-gradient-to-r from-slate-900 via-slate-900/60 to-transparent lg:from-slate-900 lg:via-slate-900/40 lg:to-transparent"></div>
            </div>
        @else
            <div class="h-full w-full bg-gradient-to-br from-indigo-700 to-purple-800"></div>
        @endif
    </div>

    {{-- Left content --}}
    <div class="relative z-10 mx-auto w-full max-w-7xl px-4 py-28 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-12 lg:items-center">
            <div class="lg:col-span-5">
                @if($siteSettings && $siteSettings->localized_school_name)
                    <span class="mb-4 inline-block rounded-full border border-white/20 bg-white/10 px-5 py-1.5 text-sm font-semibold uppercase tracking-widest text-white/90 backdrop-blur-sm">
                        {{ $siteSettings->localized_school_name }}
                    </span>
                @endif

                <h1 class="text-4xl font-black leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl" style="text-shadow: 0 4px 30px rgba(0,0,0,0.5);">
                    {{ $headline }}
                </h1>

                <p class="mt-6 max-w-xl text-lg leading-relaxed text-white/70 sm:text-xl">{{ $sub }}</p>

                <div class="mt-10 flex flex-wrap items-center gap-4">
                    @if($admissionsOpen)
                        <a href="{{ route('admissions.apply') }}" class="inline-flex items-center gap-2.5 rounded-xl bg-orange-500 px-8 py-4 text-base font-semibold text-white shadow-lg shadow-orange-500/25 transition-all duration-300 hover:bg-orange-600 hover:shadow-xl hover:shadow-orange-500/30 hover:-translate-y-0.5">
                            {{ $hero['cta_primary'] ?? site_ui('home.hero_cta_primary') }}
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                    @else
                        <a href="{{ route('site.contact') }}" class="inline-flex items-center gap-2.5 rounded-xl bg-orange-500 px-8 py-4 text-base font-semibold text-white shadow-lg shadow-orange-500/25 transition-all duration-300 hover:bg-orange-600 hover:shadow-xl hover:shadow-orange-500/30 hover:-translate-y-0.5">
                            {{ site_ui('home.cta_contact') }}
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </a>
                    @endif
                    <a href="{{ route('site.about') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/25 bg-white/5 px-8 py-4 text-base font-semibold text-white backdrop-blur-sm transition-all duration-300 hover:bg-white/10 hover:border-white/40">
                        {{ $hero['cta_secondary'] ?? site_ui('home.hero_cta_secondary') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
