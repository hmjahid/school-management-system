{{--
    Hero design 1 — Dark split with notices panel.
    Variables: $hero, $heroImg, $headline, $sub, $siteSettings, $recentNotices, $noticesH, $sectionVis
--}}
<section class="relative min-h-[85vh] flex items-center overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950">
    @if(!empty($heroImg))
    <div class="absolute inset-0">
        <img src="{{ $heroImg }}" alt="" class="h-full w-full object-cover" loading="eager" width="1920" height="1080">
        <div class="absolute inset-0 bg-gradient-to-br from-slate-900/90 via-slate-800/80 to-indigo-950/90"></div>
    </div>
    @endif

    {{-- Decorative blobs --}}
    <div class="absolute -top-40 -right-40 h-[500px] w-[500px] rounded-full bg-orange-500/10 blur-3xl" aria-hidden="true"></div>
    <div class="absolute -bottom-40 -left-40 h-[500px] w-[500px] rounded-full bg-indigo-500/10 blur-3xl" aria-hidden="true"></div>

    {{-- School name banner --}}
    @if($siteSettings && $siteSettings->localized_school_name)
        <div class="absolute top-0 left-0 right-0 z-10" aria-hidden="true">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-6">
                <div class="mx-auto max-w-3xl rounded-2xl border border-white/10 bg-white/[0.06] backdrop-blur-md px-8 py-4 text-center shadow-xl shadow-black/10">
                    <span class="font-black uppercase tracking-widest text-white text-xl sm:text-2xl lg:text-3xl drop-shadow-lg">{{ $siteSettings->localized_school_name }}</span>
                </div>
            </div>
        </div>
    @endif

    <div class="relative z-10 mx-auto w-full max-w-7xl px-4 pt-28 pb-20 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-12 lg:items-center">

            {{-- Left: Headline --}}
            <div class="lg:col-span-7 xl:col-span-7">

                <h1 class="text-4xl font-black leading-tight tracking-tight text-white sm:text-5xl lg:text-7xl" style="text-shadow: 0 4px 30px rgba(0,0,0,0.5), 0 2px 8px rgba(0,0,0,0.3);">
                    {{ $headline }}
                </h1>

                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-white/70 sm:text-xl">{{ $sub }}</p>

                <div class="mt-10 flex flex-wrap items-center gap-4">
                    <a href="{{ route('admissions.apply') }}" class="inline-flex items-center gap-2.5 rounded-xl bg-orange-500 px-8 py-4 text-base font-semibold text-white shadow-lg shadow-orange-500/25 transition-all duration-300 hover:bg-orange-600 hover:shadow-xl hover:shadow-orange-500/30 hover:-translate-y-0.5">
                        {{ $hero['cta_primary'] ?? site_ui('home.hero_cta_primary') }}
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                    <a href="{{ route('site.about') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/25 bg-white/5 text-white backdrop-blur-sm px-8 py-4 text-base font-semibold transition-all duration-300 hover:bg-white/10 hover:border-white/40">
                        {{ $hero['cta_secondary'] ?? site_ui('home.hero_cta_secondary') }}
                    </a>
                </div>

            </div>

            {{-- Right: Notice panel --}}
            @if(($sectionVis['urgent_notices'] ?? true) && $recentNotices->isNotEmpty())
                <div class="lg:col-span-5 xl:col-span-5">
                    <div class="rounded-2xl border border-slate-200 bg-white shadow-lg">
                        <div class="flex items-center justify-between px-6 pt-5 pb-4 sm:px-7">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-50">
                                    <svg class="h-5 w-5 text-orange-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z" clip-rule="evenodd"/></svg>
                                </span>
                                <h3 class="text-base font-bold text-slate-900">{{ $noticesH['title'] ?? site_ui('home.latest_notices') }}</h3>
                            </div>
                        </div>

                        {{-- Auto-scrolling notice list (bottom→top, ~4 visible) --}}
                        @php
                            $visibleCount = 4;
                            $noticeHeight = 88;
                            $gap = 10;
                            $visibleHeight = ($visibleCount * $noticeHeight) + (($visibleCount - 1) * $gap);
                            $totalNotices = $recentNotices->count();
                            $scrollDuration = max(8, $totalNotices * 3);
                        @endphp
                        <div class="relative px-6 pb-5 sm:px-7">
                            <div class="pointer-events-none absolute inset-x-6 sm:inset-x-7 top-0 h-6 bg-gradient-to-b from-white to-transparent z-10"></div>
                            <div class="pointer-events-none absolute inset-x-6 sm:inset-x-7 bottom-5 h-6 bg-gradient-to-t from-white to-transparent z-10"></div>

                            <div
                                class="notice-scroll-container overflow-hidden"
                                style="height: {{ $visibleHeight }}px;"
                                data-scroll-speed="{{ $scrollDuration }}"
                            >
                                <div class="notice-scroll-content">
                                    @foreach($recentNotices as $notice)
                                        <div class="notice-item rounded-xl border border-slate-100 bg-slate-50 p-4 transition-all duration-200 hover:border-slate-200 hover:bg-slate-100" style="min-height: {{ $noticeHeight }}px;">
                                            <div class="flex items-start gap-3">
                                                @if($notice->pinned)
                                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2V7a5 5 0 00-5-5zm3 7V7a3 3 0 00-6 0v2h6z"/></svg>
                                                @else
                                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-500/60"></span>
                                                @endif
                                                <div class="min-w-0 flex-1">
                                                    <h4 class="text-sm font-semibold text-slate-900 leading-snug">{{ $notice->localizedTitle() }}</h4>
                                                    <p class="mt-1 text-xs leading-relaxed text-slate-500 line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($notice->localizedContent()), 100) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    {{-- Duplicate for seamless loop --}}
                                    @foreach($recentNotices as $notice)
                                        <div class="notice-item rounded-xl border border-slate-100 bg-slate-50 p-4 transition-all duration-200 hover:border-slate-200 hover:bg-slate-100" style="min-height: {{ $noticeHeight }}px;">
                                            <div class="flex items-start gap-3">
                                                @if($notice->pinned)
                                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2V7a5 5 0 00-5-5zm3 7V7a3 3 0 00-6 0v2h6z"/></svg>
                                                @else
                                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-500/60"></span>
                                                @endif
                                                <div class="min-w-0 flex-1">
                                                    <h4 class="text-sm font-semibold text-slate-900 leading-snug">{{ $notice->localizedTitle() }}</h4>
                                                    <p class="mt-1 text-xs leading-relaxed text-slate-500 line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($notice->localizedContent()), 100) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="px-6 pb-5 sm:px-7">
                            <a href="{{ route('site.notices') }}" class="flex items-center justify-center gap-1.5 rounded-lg bg-slate-100 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-200 hover:text-slate-900">
                                {{ $noticesH['view_all'] ?? site_ui('home.view_all_notices') }}
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</section>
