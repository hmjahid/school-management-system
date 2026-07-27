@extends('layouts.app')

@section('title', ($content->title ?? site_ui('gallery.page_title_fallback')) . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('meta_description', $content->meta_description)

@section('content')
    <div class="bg-white">
        @if($siteSettings->section_visibility['gallery_hero'] ?? true)
        <div class="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <h1 class="text-4xl font-bold md:text-5xl">{{ $content->title ?? site_ui('gallery.page_title_fallback') }}</h1>
                @if($content->meta_description ?? false)
                    <p class="mx-auto mt-4 max-w-2xl text-lg text-blue-100">{{ $content->meta_description }}</p>
                @endif
            </div>
        </div>
        @endif

        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            @include('site.partials.sections', ['content' => $content])

            @php $categories = $items->keys(); @endphp
            @if($categories->isNotEmpty())

                @if($siteSettings->section_visibility['gallery_tabs'] ?? true)
                {{-- Category filter tabs --}}
                <div class="mb-10 flex flex-wrap gap-2 reveal" data-filter-tabs>
                    <button type="button" data-filter="all" class="rounded-full bg-blue-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 data-[active=true]:bg-blue-600 data-[active=true]:text-white">
                        {{ __('All') }}
                    </button>
                    @foreach($categories as $cat)
                        <button type="button" data-filter="{{ \Illuminate\Support\Str::slug($cat) }}" class="rounded-full bg-slate-100 px-5 py-2 text-sm font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-700 data-[active=true]:bg-blue-600 data-[active=true]:text-white">
                            {{ $cat }}
                        </button>
                    @endforeach
                </div>
                @endif

                @if($siteSettings->section_visibility['gallery_grid'] ?? true)
                {{-- Masonry-style gallery grid --}}
                <div class="columns-1 gap-6 sm:columns-2 lg:columns-3 xl:columns-4 space-y-6 reveal" data-gallery-grid>
                    @foreach ($items as $category => $group)
                        @foreach ($group as $g)
                            @php
                                $src = $g->image_path;
                                if ($src && ! \Illuminate\Support\Str::startsWith($src, ['http://', 'https://'])) {
                                    $src = \Illuminate\Support\Facades\Storage::url($src);
                                }
                                $slug = \Illuminate\Support\Str::slug($category);
                            @endphp
                            <figure class="group relative overflow-hidden rounded-2xl bg-slate-100 shadow-md ring-1 ring-slate-100 break-inside-avoid transition-all duration-300 hover:shadow-xl" data-category="{{ $slug }}" data-lightbox='@json([[$src]])'>
                                @if($src)
                                    <img src="{{ $src }}" alt="{{ $g->title }}" class="w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                                @endif
                                {{-- Hover overlay --}}
                                <div class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-black/60 via-black/10 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100 p-5">
                                    <h3 class="text-lg font-semibold text-white">{{ $g->title }}</h3>
                                    @if($g->description)
                                        <p class="mt-1 text-sm text-white/80">{{ $g->description }}</p>
                                    @endif
                                    <span class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-white/70">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        {{ __('Click to view') }}
                                    </span>
                                </div>
                            </figure>
                        @endforeach
                    @endforeach
                </div>
                @endif
            @else
                <div class="rounded-xl border-2 border-dashed border-slate-200 p-16 text-center reveal">
                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p class="mt-4 text-sm text-slate-500">{{ site_ui('gallery.empty') }}</p>
                </div>
            @endif

            {{-- Video section --}}
            <section class="mt-16 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center reveal">
                <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <h2 class="mt-4 text-lg font-semibold text-slate-900">{{ site_ui('gallery.video_section_title') }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ site_ui('gallery.video_section_body') }}</p>
            </section>
        </div>
    </div>

    {{-- Lightbox overlay --}}
    <div id="lightbox-overlay" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/90" data-lightbox-overlay>
        <button type="button" data-lightbox-close class="absolute right-4 top-4 z-10 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <button type="button" data-lightbox-prev class="absolute left-4 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <img data-lightbox-image src="" alt="" class="max-h-[85vh] max-w-[90vw] rounded-2xl object-contain shadow-2xl">
        <button type="button" data-lightbox-next class="absolute right-4 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>
@endsection
