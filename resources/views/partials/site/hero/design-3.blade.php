{{--
    Hero design 3 — Light split layout with photo card.
    Variables: $hero, $heroImg, $headline, $sub, $siteSettings
--}}
<section class="relative overflow-hidden bg-slate-50">
    <div class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-blue-200/40 blur-3xl" aria-hidden="true"></div>
    <div class="absolute -bottom-32 -left-32 h-96 w-96 rounded-full bg-orange-200/40 blur-3xl" aria-hidden="true"></div>

    <div class="relative z-10 mx-auto grid max-w-7xl items-center gap-14 px-4 py-24 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-28">
        <div>
            @if($siteSettings && $siteSettings->localized_school_name)
                <span class="mb-4 inline-flex items-center gap-2 rounded-full bg-blue-100 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-blue-700">
                    {{ $siteSettings->localized_school_name }}
                </span>
            @endif
            <h1 class="text-4xl font-black leading-tight tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
                {{ $headline }}
            </h1>
            <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-600 sm:text-xl">{{ $sub }}</p>

            <div class="mt-10 flex flex-wrap items-center gap-4">
                @if($admissionsOpen)
                    <a href="{{ route('admissions.apply') }}" class="inline-flex items-center gap-2.5 rounded-xl bg-blue-600 px-8 py-4 text-base font-semibold text-white shadow-lg shadow-blue-600/25 transition-all duration-300 hover:bg-blue-700 hover:shadow-xl hover:-translate-y-0.5">
                        {{ $hero['cta_primary'] ?? site_ui('home.hero_cta_primary') }}
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                @else
                    <a href="{{ route('site.contact') }}" class="inline-flex items-center gap-2.5 rounded-xl bg-blue-600 px-8 py-4 text-base font-semibold text-white shadow-lg shadow-blue-600/25 transition-all duration-300 hover:bg-blue-700 hover:shadow-xl hover:-translate-y-0.5">
                        {{ site_ui('home.cta_contact') }}
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </a>
                @endif
                <a href="{{ route('site.about') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-8 py-4 text-base font-semibold text-slate-700 transition-all duration-300 hover:border-blue-300 hover:text-blue-700">
                    {{ $hero['cta_secondary'] ?? site_ui('home.hero_cta_secondary') }}
                </a>
            </div>

            <div class="mt-10 flex items-center gap-8">
                <div>
                    <div class="text-3xl font-black text-slate-900">100%</div>
                    <div class="mt-1 text-sm font-medium text-slate-500">{{ __('Focused learning') }}</div>
                </div>
                <div class="h-10 w-px bg-slate-200"></div>
                <div>
                    <div class="text-3xl font-black text-slate-900">{{ $stats['years'] ?? '—' }}+</div>
                    <div class="mt-1 text-sm font-medium text-slate-500">{{ $statsL['years'] ?? site_ui('home.stats_years') }}</div>
                </div>
            </div>
        </div>

        <div class="relative">
            @if(!empty($heroImg))
                <img src="{{ $heroImg }}" alt="" class="relative z-10 aspect-[4/3] w-full rounded-3xl object-cover shadow-2xl ring-1 ring-slate-200">
            @else
                <div class="relative z-10 flex aspect-[4/3] w-full items-center justify-center rounded-3xl bg-gradient-to-br from-blue-100 to-indigo-100">
                    <svg class="h-24 w-24 text-blue-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                </div>
            @endif
            <div class="absolute -top-6 -left-6 h-40 w-40 rounded-3xl bg-gradient-to-br from-orange-400 to-orange-600 shadow-xl" aria-hidden="true"></div>
            <div class="absolute -bottom-6 -right-6 h-40 w-40 rounded-3xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-xl" aria-hidden="true"></div>
        </div>
    </div>
</section>
