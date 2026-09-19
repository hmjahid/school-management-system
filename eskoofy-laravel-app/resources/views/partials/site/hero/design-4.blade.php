{{--
    Hero design 4 — Minimal gradient with eyebrow text.
    Variables: $hero, $heroImg, $headline, $sub, $siteSettings
--}}
<section class="relative flex min-h-[80vh] items-center overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-purple-600 to-fuchsia-600"></div>
    @if(!empty($heroImg))
        <div class="absolute inset-0">
            <img src="{{ $heroImg }}" alt="" class="h-full w-full object-cover opacity-30" loading="eager" width="1920" height="1080">
        </div>
    @endif
    <div class="absolute -top-32 right-0 h-96 w-96 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
    <div class="absolute bottom-0 left-0 h-72 w-72 rounded-full bg-black/20 blur-3xl" aria-hidden="true"></div>

    <div class="relative z-10 mx-auto w-full max-w-4xl px-4 py-28 sm:px-6 lg:px-8">
        <div class="max-w-2xl">
            <span class="mb-5 inline-block rounded-full bg-white/15 px-4 py-1.5 text-xs font-bold uppercase tracking-[0.2em] text-white backdrop-blur-sm">
                {{ $siteSettings->localized_school_name ?? site_ui('home.eyebrow') }}
            </span>

            <h1 class="text-4xl font-black leading-tight tracking-tight text-white sm:text-6xl">
                {{ $headline }}
            </h1>

            <p class="mt-6 text-lg leading-relaxed text-white/80 sm:text-xl">{{ $sub }}</p>

            <div class="mt-10 flex flex-wrap items-center gap-4">
                @if($admissionsOpen)
                    <a href="{{ route('admissions.apply') }}" class="inline-flex items-center gap-2.5 rounded-xl bg-white px-8 py-4 text-base font-bold text-purple-700 shadow-xl shadow-purple-900/20 transition-all duration-300 hover:bg-purple-50 hover:-translate-y-0.5">
                        {{ $hero['cta_primary'] ?? site_ui('home.hero_cta_primary') }}
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                @else
                    <a href="{{ route('site.contact') }}" class="inline-flex items-center gap-2.5 rounded-xl bg-white px-8 py-4 text-base font-bold text-purple-700 shadow-xl shadow-purple-900/20 transition-all duration-300 hover:bg-purple-50 hover:-translate-y-0.5">
                        {{ site_ui('home.cta_contact') }}
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </a>
                @endif
                <a href="{{ route('site.about') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/40 bg-white/10 px-8 py-4 text-base font-semibold text-white backdrop-blur-sm transition-all duration-300 hover:bg-white/20">
                    {{ $hero['cta_secondary'] ?? site_ui('home.hero_cta_secondary') }}
                </a>
            </div>
        </div>
    </div>

    <div class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-slate-900/40 to-transparent" aria-hidden="true"></div>
</section>
