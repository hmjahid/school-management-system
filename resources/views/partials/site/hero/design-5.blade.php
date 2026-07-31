{{--
    Hero design 5 — Full-width image hero with school name.
    Variables: $hero, $heroImg, $headline, $sub, $siteSettings, $admissionsOpen
--}}
<section class="relative min-h-[85vh] flex items-center justify-center overflow-hidden">
    @if(!empty($heroImg))
        <img src="{{ $heroImg }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="eager" width="1920" height="1080">
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-black/60"></div>
    @else
        <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900"></div>
    @endif

    <div class="relative z-10 mx-auto w-full max-w-5xl px-4 py-32 text-center sm:px-6 lg:px-8">
        @if($siteSettings && $siteSettings->localized_school_name)
            <h1 class="text-5xl font-black uppercase tracking-widest text-white sm:text-6xl lg:text-8xl" style="text-shadow: 0 4px 30px rgba(0,0,0,0.6), 0 2px 8px rgba(0,0,0,0.4);">
                {{ $siteSettings->localized_school_name }}
            </h1>
        @endif

        @if($headline)
            <p class="mt-6 text-xl font-semibold text-white/90 sm:text-2xl">{{ $headline }}</p>
        @endif

        @if($sub)
            <p class="mx-auto mt-4 max-w-2xl text-lg leading-relaxed text-white/70 sm:text-xl">{{ $sub }}</p>
        @endif

        <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
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
</section>