@extends('layouts.app')

@section('title', $article->title . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('og_title', $article->title)
@section('og_description', strip_tags(\Illuminate\Support\Str::limit($article->content, 160)))
@section('og_image', $article->image_url ?? asset('images/og-default.jpg'))

@push('schema')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'datePublished' => optional($article->published_at)->toAtomString(),
            'dateModified' => optional($article->updated_at)->toAtomString(),
            'author' => [
                '@type' => 'Person',
                'name' => $article->author_name ?: ($siteSettings->school_name ?? config('app.name')),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $siteSettings->school_name ?? config('app.name'),
            ],
            'image' => $article->image_url ? [$article->image_url] : null,
            'mainEntityOfPage' => url()->current(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@push('head')
    <meta property="article:published_time" content="{{ optional($article->published_at)->toAtomString() }}">
    <meta property="article:modified_time" content="{{ optional($article->updated_at)->toAtomString() }}">
    <meta property="article:author" content="{{ $article->author_name ?: ($siteSettings->site_name ?? config('app.name')) }}">
@endpush

@section('content')
    <article class="bg-white">
        {{-- Featured image hero --}}
        @if($article->image_url)
            <div class="relative h-[40vh] md:h-[55vh] overflow-hidden">
                <img src="{{ $article->image_url }}" alt="{{ $article->title }}" class="h-full w-full object-cover" loading="eager">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
                <div class="absolute bottom-0 left-0 right-0 p-6 md:p-12">
                    <div class="mx-auto max-w-3xl">
                        @if($article->category ?? false)
                            <span class="inline-block rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold text-white">{{ $article->category }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
            {{-- Back link --}}
            <a href="{{ route('site.news') }}" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                {{ site_ui('news_show.back') }}
            </a>

            {{-- Article header --}}
            <header class="mt-6">
                <h1 class="text-3xl font-bold leading-tight text-slate-900 md:text-4xl lg:text-5xl">{{ $article->title }}</h1>

                {{-- Article meta --}}
                <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-slate-500">
                    @if($article->author_name ?? false)
                        <span class="flex items-center gap-1.5">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                            {{ $article->author_name }}
                        </span>
                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                    @endif
                    @if($article->published_at)
                        <span class="flex items-center gap-1.5">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <time datetime="{{ $article->published_at->toIso8601String() }}">{{ $article->published_at->format('F j, Y') }}</time>
                        </span>
                    @endif
                    <span class="flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        {{ __('5 min read') }}
                    </span>
                    <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                    <button onclick="window.print()" class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        {{ __('Print') }}
                    </button>
                </div>
            </header>

            {{-- Article content with dropcap --}}
            <div class="mt-10 max-w-none text-base leading-relaxed text-slate-700 lg:text-lg">
                @php
                    $content = e($article->content);
                    $firstChar = \Illuminate\Support\Str::substr(strip_tags($article->content), 0, 1);
                    $rest = \Illuminate\Support\Str::substr(strip_tags($article->content), 1);
                @endphp
                <p>
                    <span class="float-left mr-3 mt-1 text-5xl font-bold leading-none text-blue-600">{{ $firstChar }}</span>
                    {!! nl2br($rest) !!}
                </p>
            </div>

            {{-- Share buttons --}}
            <div class="mt-10 border-t border-slate-100 pt-8">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-sm font-medium text-slate-600">{{ __('Share this article') }}:</span>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-full bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?text={{ urlencode($article->title) }}&url={{ urlencode(url()->current()) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-full bg-slate-800 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-900">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        X (Twitter)
                    </a>
                    <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(url()->current()) }}&title={{ urlencode($article->title) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-full bg-blue-700 px-4 py-2 text-xs font-semibold text-white transition hover:bg-blue-800">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        LinkedIn
                    </a>
                    <button onclick="navigator.clipboard.writeText(window.location.href);this.textContent='Copied!';setTimeout(()=>this.textContent='Copy Link',2000)" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        Copy Link
                    </button>
                </div>
            </div>

            {{-- Related articles --}}
            @if(isset($related) && $related->isNotEmpty())
                <div class="mt-16 border-t border-slate-100 pt-12">
                    <h2 class="text-2xl font-bold text-slate-900">{{ __('Related Articles') }}</h2>
                    <div class="mt-2 h-1 w-16 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full"></div>
                    <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($related->take(3) as $rel)
                            <a href="{{ route('site.news.show', $rel->slug) }}" class="group overflow-hidden rounded-xl bg-white shadow-md ring-1 ring-slate-100 transition-all duration-300 hover:shadow-xl">
                                <div class="h-40 overflow-hidden bg-slate-100">
                                    @if($rel->image_url)
                                        <img src="{{ $rel->image_url }}" alt="" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                                    @else
                                        <div class="h-full w-full bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center">
                                            <svg class="h-8 w-8 text-blue-200" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="p-4">
                                    @if($rel->published_at)
                                        <time class="text-xs text-slate-500">{{ $rel->published_at->format('M j, Y') }}</time>
                                    @endif
                                    <h3 class="mt-1 text-base font-semibold text-slate-900 group-hover:text-blue-600 transition-colors">{{ $rel->title }}</h3>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </article>
@endsection
