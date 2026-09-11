@extends('layouts.dashboard')

@section('title', __('Transport routes') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Transport routes')" :description="__('Each route defines stops and a fare applied to assigned students.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Transport')],
                ['label' => __('Routes')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-button :href="route('dashboard.transport.assignments.index')" variant="ghost" size="sm">{{ __('Assignments') }}</x-button>
            <x-button :href="route('dashboard.transport.routes.create')">{{ __('New route') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-admin-data-table
        :headers="[
            ['label' => __('Code')],
            ['label' => __('Name')],
            ['label' => __('Vehicle')],
            ['label' => __('Stops')],
            ['label' => __('Fare'), 'class' => 'text-right'],
            ['label' => __('Status')],
            ['label' => __('Actions'), 'class' => 'text-right'],
        ]"
        :paginator="$rows"
        empty-icon="inbox"
        :empty-title="__('No routes yet.')"
        :empty-message="__('Create a route to start assigning students.')"
    >
        @forelse($rows as $r)
            <tr class="admin-table-row">
                <td class="px-4 py-3 font-mono font-semibold text-slate-700 dark:text-slate-300">{{ $r->code }}</td>
                <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $r->name }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $r->vehicle?->number ?: '—' }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $r->stops->count() }}</td>
                <td class="px-4 py-3 text-right font-mono text-slate-900 dark:text-slate-100">{{ number_format((float) $r->fare, 2) }}</td>
                <td class="px-4 py-3">
                    <x-badge :variant="$r->is_active ? 'success' : 'default'">{{ $r->is_active ? __('Active') : __('Inactive') }}</x-badge>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <x-button :href="route('dashboard.transport.routes.edit', $r)" variant="ghost" size="sm">{{ __('Edit') }}</x-button>
                        <form method="post" action="{{ route('dashboard.transport.routes.destroy', $r) }}" class="inline" data-confirm="{{ __('Delete this route?') }}" data-confirm-title="{{ __('Delete route') }}">
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