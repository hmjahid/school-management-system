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

    <x-admin-data-table
        :headers="[
            ['label' => __('Number')],
            ['label' => __('Type')],
            ['label' => __('Capacity')],
            ['label' => __('Driver')],
            ['label' => __('Status')],
            ['label' => __('Actions'), 'class' => 'text-right'],
        ]"
        :paginator="$rows"
        empty-icon="inbox"
        :empty-title="__('No vehicles yet.')"
        :empty-message="__('Add a vehicle before assigning routes.')"
    >
        @forelse($rows as $v)
            <tr class="admin-table-row">
                <td class="px-4 py-3 font-mono font-semibold text-slate-700 dark:text-slate-300">{{ $v->number }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $v->type ?: '—' }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $v->capacity ?: '—' }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                    {{ $v->driver_name ?: '—' }}
                    @if($v->driver_phone)<br><span class="text-xs text-slate-500 dark:text-slate-400">{{ $v->driver_phone }}</span>@endif
                </td>
                <td class="px-4 py-3">
                    <x-badge :variant="$v->is_active ? 'success' : 'default'">{{ $v->is_active ? __('Active') : __('Inactive') }}</x-badge>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <x-button :href="route('dashboard.transport.vehicles.edit', $v)" variant="ghost" size="sm">{{ __('Edit') }}</x-button>
                        <form method="post" action="{{ route('dashboard.transport.vehicles.destroy', $v) }}" class="inline" data-confirm="{{ __('Delete this vehicle?') }}" data-confirm-title="{{ __('Delete vehicle') }}">
                            @csrf @method('delete')
                            <x-button type="submit" variant="ghost" size="sm" class="text-red-600 hover:text-red-800 dark:text-red-400">{{ __('Delete') }}</x-button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
        @endforelse
    </x-admin-data-table>
@endsection