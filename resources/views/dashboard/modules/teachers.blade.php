@extends('layouts.dashboard')

@section('title', __('Teachers') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <x-page-header :title="__('Teachers')" :description="__('Teaching staff, assignments, and attendance.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Teachers')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            @can('create', App\Models\Teacher::class)
                <x-button :href="route('dashboard.teachers.create')">{{ __('Add teacher') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="get" class="mb-6 flex flex-wrap items-end gap-2">
        <div class="min-w-[220px]">
            <label for="filter-search" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('Search') }}</label>
            <input id="filter-search" type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search…') }}" class="admin-input">
        </div>
        <div>
            <label for="filter-status" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('Status') }}</label>
            <select id="filter-status" name="status" class="admin-select">
                <option value="">{{ __('Any status') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <x-button type="submit" size="sm">{{ __('Search') }}</x-button>
            @if (request()->hasAny(['search', 'status']))
                <x-button :href="route('dashboard.teachers')" variant="secondary" size="sm">{{ __('Reset') }}</x-button>
            @endif
        </div>
    </form>

    <x-admin-data-table
        :headers="[
            ['label' => __('Name')],
            ['label' => __('Employee ID')],
            ['label' => __('Status')],
            ['label' => __('Actions'), 'class' => 'text-right'],
        ]"
        :paginator="$teachers"
        empty-icon="users"
        :empty-title="__('No teachers found')"
        :empty-message="__('Add teaching staff to assign classes and track attendance.')"
    >
        @forelse ($teachers as $teacher)
            <tr class="admin-table-row">
                <td class="px-4 py-3">
                    <div class="font-medium text-slate-900 dark:text-slate-100">{{ $teacher->user?->name ?? __('N/A') }}</div>
                    <div class="text-slate-500 dark:text-slate-400">{{ $teacher->user?->email }}</div>
                </td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $teacher->employee_id ?? '—' }}</td>
                <td class="px-4 py-3">
                    <x-badge :variant="$teacher->status === 'active' ? 'success' : 'default'">{{ $teacher->status ?? '—' }}</x-badge>
                </td>
                <td class="px-4 py-3 text-right">
                    @can('view', $teacher)
                        <x-button :href="route('dashboard.teachers.show', $teacher)" variant="ghost" size="sm">{{ __('View') }}</x-button>
                    @endcan
                </td>
            </tr>
        @empty
        @endforelse
    </x-admin-data-table>
@endsection