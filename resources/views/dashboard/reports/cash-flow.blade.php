@extends('layouts.dashboard')

@section('title', __('Cash flow') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Cash flow')" :description="$from . ' — ' . $to">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Reports')],
                ['label' => __('Cash flow')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <form method="get" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-3">
        <input type="date" name="from" value="{{ $from }}" class="admin-input">
        <input type="date" name="to" value="{{ $to }}" class="admin-input">
        <x-button type="submit" variant="secondary">{{ __('Refresh') }}</x-button>
    </form>

    <div class="grid gap-6 md:grid-cols-2">
        <x-card :title="__('Cash on hand')" :padding="false">
            <div class="divide-y divide-slate-100">
                @forelse($cashMovements as $m)
                    <div class="flex items-center justify-between px-5 py-3 text-sm">
                        <span class="text-slate-700">{{ $m->date?->format('Y-m-d') }} · {{ $m->note ?: '—' }}</span>
                        <span class="font-mono {{ $m->debit > 0 ? 'text-emerald-700' : 'text-red-700' }}">
                            {{ ($m->debit > 0 ? '+' : '−') . number_format((float) max($m->debit, $m->credit), 2) }}
                        </span>
                    </div>
                @empty
                    <p class="px-5 py-6 text-center text-sm text-slate-500">{{ __('No movements.') }}</p>
                @endforelse
            </div>
            <div class="flex items-center justify-between border-t border-slate-200 px-5 py-3 text-sm">
                <span class="font-semibold">{{ __('Period balance') }}</span>
                <span class="font-mono font-bold">{{ number_format((float) $cashBalance, 2) }}</span>
            </div>
        </x-card>

        <x-card :title="__('Bank account')" :padding="false">
            <div class="divide-y divide-slate-100">
                @forelse($bankMovements as $m)
                    <div class="flex items-center justify-between px-5 py-3 text-sm">
                        <span class="text-slate-700">{{ $m->date?->format('Y-m-d') }} · {{ $m->note ?: '—' }}</span>
                        <span class="font-mono {{ $m->debit > 0 ? 'text-emerald-700' : 'text-red-700' }}">
                            {{ ($m->debit > 0 ? '+' : '−') . number_format((float) max($m->debit, $m->credit), 2) }}
                        </span>
                    </div>
                @empty
                    <p class="px-5 py-6 text-center text-sm text-slate-500">{{ __('No movements.') }}</p>
                @endforelse
            </div>
            <div class="flex items-center justify-between border-t border-slate-200 px-5 py-3 text-sm">
                <span class="font-semibold">{{ __('Period balance') }}</span>
                <span class="font-mono font-bold">{{ number_format((float) $bankBalance, 2) }}</span>
            </div>
        </x-card>
    </div>
@endsection