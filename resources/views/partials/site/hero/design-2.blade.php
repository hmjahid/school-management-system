{{--
    Hero design 2 — Centered banner with background image.
    Variables: $hero, $heroImg, $headline, $sub, $siteSettings
--}}
<section class="relative flex min-h-[85vh] items-center justify-center overflow-hidden">
    @if(!empty($heroImg))
        <img src="{{ $heroImg }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="eager" width="1920" height="1080">
        <div class="absolute inset-0 bg-gradient-to-b from-slate-950/85 via-slate-900/75 to-slate-950/90"></div>
    @else
        <div class="absolute inset-0 bg-gradient-to-br from-blue-900 via-indigo-900 to-slate-900"></div>
    @endif

    <div class="absolute inset-0 opacity-20" aria-hidden="true" style="background-image: radial-gradient(circle at 20% 30%, rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 28px 28px;"></div>

    <div class="relative z-10 mx-auto w-full max-w-5xl px-4 py-32 text-center sm:px-6 lg:px-8">
        @if($siteSettings && $siteSettings->localized_school_name)
            <span class="mb-6 inline-block rounded-full border border-white/20 bg-white/10 px-5 py-1.5 text-sm font-semibold uppercase tracking-widest text-white/90 backdrop-blur-sm">
                {{ $siteSettings->localized_school_name }}
            </span>
        @endif

        <h1 class="text-5xl font-black leading-tight tracking-tight text-white sm:text-6xl lg:text-7xl" style="text-shadow: 0 4px 30px rgba(0,0,0,0.5);">
            {{ $headline }}
        </h1>

        <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-white/75 sm:text-xl">{{ $sub }}</p>

        <div class="mt-12 flex flex-wrap items-center justify-center gap-4">
            <a href="{{ route('admissions.apply') }}" class="inline-flex items-center gap-2.5 rounded-xl bg-gradient-to-r from-orange-500 to-orange-600 px-8 py-4 text-base font-semibold text-white shadow-lg shadow-orange-500/30 transition-all duration-300 hover:shadow-xl hover:brightness-110 hover:-translate-y-0.5">
                {{ $hero['cta_primary'] ?? site_ui('home.hero_cta_primary') }}
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
            <a href="{{ route('site.about') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/25 bg-white/5 px-8 py-4 text-base font-semibold text-white backdrop-blur-sm transition-all duration-300 hover:bg-white/10 hover:border-white/40">
                {{ $hero['cta_secondary'] ?? site_ui('home.hero_cta_secondary') }}
            </a>
        </div>

        <div class="mx-auto mt-14 h-1 w-24 rounded-full bg-gradient-to-r from-orange-400 to-orange-600"></div>
    </div>
</section>
