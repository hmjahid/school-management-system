@extends('layouts.dashboard')

@section('title', __('Leave requests') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Leave requests')" :description="__('Submit, review, and approve leave requests.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Leaves')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-button :href="route('dashboard.leaves.create')">{{ __('New request') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="get" class="mb-6 flex flex-wrap items-center gap-2">
        <select name="status" class="admin-select" onchange="this.form.submit()">
            <option value="">{{ __('All statuses') }}</option>
            @foreach(['pending','approved','rejected','cancelled'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </form>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">{{ __('Teacher') }}</th>
                        <th class="px-4 py-3">{{ __('Type') }}</th>
                        <th class="px-4 py-3">{{ __('Dates') }}</th>
                        <th class="px-4 py-3">{{ __('Days') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $r)
                        @php
                            $cls = match($r->status) {
                                'approved' => 'bg-emerald-100 text-emerald-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                'cancelled' => 'bg-slate-200 text-slate-800',
                                default => 'bg-amber-100 text-amber-800',
                            };
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $r->teacher?->user?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $r->type?->name() ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $r->from_date->format('M j, Y') }} → {{ $r->to_date->format('M j, Y') }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $r->days() }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $cls }}">{{ ucfirst($r->status) }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <x-button :href="route('dashboard.leaves.show', $r)" variant="ghost" size="sm">{{ __('View') }}</x-button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No leave requests.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $rows->links() }}</div>
        @endif
    </x-card>
@endsection