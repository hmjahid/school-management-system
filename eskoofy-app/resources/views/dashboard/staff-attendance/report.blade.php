@extends('layouts.dashboard')

@section('title', __('Staff attendance report') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Monthly staff attendance')" :description="$month->format('F Y')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Staff attendance'), 'url' => route('dashboard.staff-attendance.index')],
                ['label' => __('Report')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <form method="get" class="flex items-center gap-2">
                <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="admin-input">
                <x-button type="submit" variant="secondary" size="sm">{{ __('Change month') }}</x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-left uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="sticky left-0 z-10 bg-slate-50 px-3 py-3">{{ __('Staff') }}</th>
                        @foreach($period as $day)
                            <th class="px-2 py-3 text-center">{{ $day->format('j') }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($teachers as $t)
                        <tr>
                            <td class="sticky left-0 z-10 bg-white px-3 py-2 text-sm font-medium text-slate-900">{{ $t->user?->name ?? $t->employee_id }}</td>
                            @foreach($period as $day)
                                @php
                                    $rec = $records[$t->id] ?? collect();
                                    $r = $rec->firstWhere('date', $day->toDateString());
                                    $cls = match($r?->status) {
                                        'present' => 'bg-emerald-100 text-emerald-800',
                                        'absent' => 'bg-red-100 text-red-800',
                                        'late' => 'bg-amber-100 text-amber-800',
                                        'leave' => 'bg-sky-100 text-sky-800',
                                        default => '',
                                    };
                                @endphp
                                <td class="px-1 py-2 text-center">
                                    @if($r)
                                        <span class="inline-block rounded px-1.5 py-0.5 text-[10px] font-bold {{ $cls }}">{{ strtoupper(substr($r->status, 0, 1)) }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ $period->count() + 1 }}" class="px-3 py-6 text-center text-sm text-slate-500">{{ __('No staff members yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
@endsection