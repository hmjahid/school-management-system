@extends('layouts.dashboard')

@section('title', __('dashboard.setup.title') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <x-page-header :title="__('dashboard.setup.title')" :description="__('dashboard.setup.subtitle')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('dashboard.setup.title')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    {{-- Progress banner --}}
    <div class="mb-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        {{ $setupComplete ? __('dashboard.setup.complete') : __('dashboard.setup.remaining', ['n' => $totalCount - $doneCount, 'total' => $totalCount]) }}
                    </h2>
                    @if(! $setupComplete)
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.setup.progress', ['percent' => $setupPercent]) }}</p>
                    @else
                        <p class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">{{ __('dashboard.setup.progress', ['percent' => 100]) }}</p>
                    @endif
                </div>
                <div class="relative h-20 w-20">
                    <svg viewBox="0 0 36 36" class="h-20 w-20 -rotate-90">
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="oklch(0.9 0 0)" stroke-width="3" class="dark:stroke-slate-700"/>
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="oklch(0.55 0.18 150)" stroke-width="3" stroke-linecap="round"
                            stroke-dasharray="97.4" stroke-dashoffset="{{ 97.4 - (97.4 * $setupPercent / 100) }}"/>
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-sm font-bold text-slate-900 dark:text-white">{{ $setupPercent }}%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Steps --}}
    <div class="space-y-4">
        @foreach($items as $index => $item)
            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition dark:border-slate-700 dark:bg-slate-800 {{ $item['done'] ? 'border-emerald-200 dark:border-emerald-800' : '' }}">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold
                    {{ $item['done'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400' : 'bg-brand-100 text-brand-700 dark:bg-brand-900/40 dark:text-brand-400' }}">
                    <?php if($item['done']): ?>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    @else
                        {{ $loop->iteration }}
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $item['label'] }}</h3>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $item['description'] }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <span class="rounded-full px-2.5 py-1 text-xs font-medium
                        {{ $item['done'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' }}">
                        {{ $item['done'] ? __('dashboard.setup.done') : __('dashboard.setup.pending') }}
                    </span>
                    @if(! $item['done'])
                        <a href="{{ $item['url'] }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-brand-700">
                            {{ __('dashboard.setup.open') }}
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endsection