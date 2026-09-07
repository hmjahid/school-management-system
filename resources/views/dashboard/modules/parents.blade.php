@extends('layouts.dashboard')

@section('title', __('Parents & guardians') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <x-page-header :title="__('Parents & guardians')" :description="__('Link parents to students so families can follow progress and fees.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Parents & guardians')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            @can('create', App\Models\Guardian::class)
                <x-button :href="route('dashboard.parents.create')">{{ __('Add guardian') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="get" class="mb-6 flex flex-wrap items-end gap-2">
        <div class="min-w-[220px]">
            <label for="filter-search" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('Search') }}</label>
            <input id="filter-search" type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search…') }}" class="admin-input">
        </div>
        <div class="flex items-end gap-2">
            <x-button type="submit" size="sm">{{ __('Search') }}</x-button>
            @if (request()->filled('search'))
                <x-button :href="route('dashboard.parents')" variant="secondary" size="sm">{{ __('Reset') }}</x-button>
            @endif
        </div>
    </form>

    <x-admin-data-table
        :headers="[
            ['label' => __('Guardian')],
            ['label' => __('Phone')],
            ['label' => __('Students')],
            ['label' => __('Actions'), 'class' => 'text-right'],
        ]"
        :paginator="$guardians"
        empty-icon="users"
        :empty-title="__('No guardians found')"
        :empty-message="__('Link parents to students so families can follow progress and fees.')"
    >
        @forelse ($guardians as $guardian)
            <tr class="admin-table-row">
                <td class="px-4 py-3">
                    <div class="font-medium text-slate-900 dark:text-slate-100">{{ $guardian->user?->name ?? __('N/A') }}</div>
                    <div class="text-slate-500 dark:text-slate-400">{{ $guardian->user?->email }}</div>
                </td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $guardian->phone ?? '—' }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $guardian->students->map(fn ($s) => $s->user?->name)->filter()->implode(', ') ?: '—' }}</td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        @can('view', $guardian)
                            <x-button :href="route('dashboard.parents.show', $guardian)" variant="ghost" size="sm">{{ __('View') }}</x-button>
                        @endcan
                        @can('update', $guardian)
                            <x-button :href="route('dashboard.parents.edit', $guardian)" variant="ghost" size="sm">{{ __('Edit') }}</x-button>
                        @endcan
                        @can('delete', $guardian)
                            <form method="post" action="{{ route('dashboard.parents.destroy', $guardian) }}" class="inline" data-confirm="{{ __('Delete this guardian?') }}" data-confirm-title="{{ __('Delete guardian') }}">
                                @csrf @method('delete')
                                <x-button type="submit" variant="ghost" size="sm" class="text-red-600 hover:text-red-800 dark:text-red-400">{{ __('Delete') }}</x-button>
                            </form>
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
        @endforelse
    </x-admin-data-table>
@endsection