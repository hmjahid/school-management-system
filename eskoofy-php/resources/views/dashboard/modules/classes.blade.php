@extends('layouts.dashboard')

@section('title', __('Classes') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <x-page-header :title="__('Classes')" :description="__('Classes and sections organise students and routines.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Classes')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            @can('create', App\Models\SchoolClass::class)
                <x-button :href="route('dashboard.classes.create')">{{ __('Add class') }}</x-button>
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
                <x-button :href="route('dashboard.classes')" variant="secondary" size="sm">{{ __('Reset') }}</x-button>
            @endif
        </div>
    </form>

    <x-admin-data-table
        :headers="[
            ['label' => __('Name')],
            ['label' => __('Code')],
            ['label' => __('Grade')],
            ['label' => __('Teacher')],
            ['label' => __('Active')],
            ['label' => __('Actions'), 'class' => 'text-right'],
        ]"
        :paginator="$classes"
        empty-icon="document"
        :empty-title="__('No classes found')"
        :empty-message="__('Create classes and sections to organise students and routines.')"
    >
        @forelse ($classes as $class)
            <tr class="admin-table-row">
                <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $class->name }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $class->code ?? '—' }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $class->grade_level ?? '—' }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $class->classTeacher?->user?->name ?? '—' }}</td>
                <td class="px-4 py-3">
                    <x-badge :variant="$class->is_active ? 'success' : 'default'">{{ $class->is_active ? __('Yes') : __('No') }}</x-badge>
                </td>
                <td class="px-4 py-3 text-right">
                    @can('view', $class)
                        <x-button :href="route('dashboard.classes.show', $class)" variant="ghost" size="sm">{{ __('View') }}</x-button>
                    @endcan
                </td>
            </tr>
        @empty
        @endforelse
    </x-admin-data-table>
@endsection