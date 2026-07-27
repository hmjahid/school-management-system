@extends('layouts.app')

@section('title', site_ui('nav.notices') . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('meta_description', __('Latest notices and announcements from the school.'))

@section('content')
    <div class="bg-white">
        <div class="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <h1 class="text-4xl font-bold md:text-5xl">{{ site_ui('nav.notices') }}</h1>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-blue-100">{{ __('Stay informed with the latest notices and announcements.') }}</p>
            </div>
        </div>

        <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
            @if($notices->isEmpty())
                <div class="rounded-xl border-2 border-dashed border-slate-200 p-16 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <p class="mt-4 text-sm text-slate-500">{{ __('No notices have been published yet.') }}</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($notices as $notice)
                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-md {{ $notice->pinned ? 'border-l-4 border-l-amber-500' : '' }}">
                            <div class="flex items-start gap-4">
                                <div class="shrink-0 pt-1">
                                    @if($notice->pinned)
                                        <svg class="h-5 w-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2V7a5 5 0 00-5-5zm3 7V7a3 3 0 00-6 0v2h6z"/></svg>
                                    @else
                                        <span class="inline-flex h-3 w-3 items-center justify-center rounded-full bg-blue-500/60"></span>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-lg font-semibold text-slate-900">{{ $notice->localizedTitle() }}</h2>
                                        @if($notice->pinned)
                                            <span class="rounded bg-amber-50 px-2 py-0.5 text-xs font-bold uppercase tracking-wider text-amber-600">{{ __('Pinned') }}</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-xs text-slate-400">{{ $notice->created_at->format('M j, Y \a\t g:i A') }}</p>
                                    <div class="mt-3 text-sm leading-relaxed text-slate-600">{!! $notice->localizedContent() !!}</div>
                                    @if($notice->audience && count($notice->audience))
                                        <div class="mt-3 flex flex-wrap gap-1.5">
                                            @foreach($notice->audience as $aud)
                                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">{{ ucfirst($aud) }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $notices->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
