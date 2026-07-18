@extends('layouts.dashboard')

@section('title', __('Vehicles') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Vehicles')" :description="__('Buses, vans, and other transport vehicles.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Transport')],
                ['label' => __('Vehicles')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-button :href="route('dashboard.transport.vehicles.create')">{{ __('New vehicle') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card :padding="false">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">{{ __('Number') }}</th>
                    <th class="px-4 py-3">{{ __('Type') }}</th>
                    <th class="px-4 py-3">{{ __('Capacity') }}</th>
                    <th class="px-4 py-3">{{ __('Driver') }}</th>
                    <th class="px-4 py-3">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $v)
                    <tr>
                        <td class="px-4 py-3 font-mono font-semibold">{{ $v->number }}</td>
                        <td class="px-4 py-3">{{ $v->type ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $v->capacity ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $v->driver_name ?: '—' }}<br><span class="text-xs text-slate-500">{{ $v->driver_phone }}</span></td>
                        <td class="px-4 py-3">
                            @if($v->is_active)
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">{{ __('Active') }}</span>
                            @else
                                <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-semibold text-slate-700">{{ __('Inactive') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <x-button :href="route('dashboard.transport.vehicles.edit', $v)" variant="ghost" size="sm">{{ __('Edit') }}</x-button>
                            <form method="post" action="{{ route('dashboard.transport.vehicles.destroy', $v) }}" class="inline" onsubmit="return confirm('{{ __('Delete this vehicle?') }}')">
                                @csrf @method('delete')
                                <button class="text-xs font-semibold text-red-700 hover:underline" type="submit">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No vehicles yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($rows->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $rows->links() }}</div>
        @endif
    </x-card>
@endsection