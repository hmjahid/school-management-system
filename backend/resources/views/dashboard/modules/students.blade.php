@extends('layouts.dashboard')

@section('title', __('Students') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <x-page-header :title="__('Students')" :description="__('Manage enrolled students, classes, and records.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Students')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            @can('create', App\Models\Student::class)
                <x-button :href="route('dashboard.students.create')">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    {{ __('Add student') }}
                </x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="get" class="mb-4 flex flex-wrap gap-2">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search name, email, admission…') }}" class="admin-input min-w-[220px] flex-1 sm:max-w-xs">
        <x-button type="submit" variant="secondary">{{ __('Search') }}</x-button>
    </form>

    <x-admin-data-table
        :headers="[
            ['label' => __('Student')],
            ['label' => __('Admission')],
            ['label' => __('Class')],
            ['label' => __('Status')],
            ['label' => __('Actions'), 'class' => 'text-right'],
        ]"
        :paginator="$students"
        :empty-title="__('No students found')"
        :empty-message="__('Add your first student or adjust your search filters.')"
        empty-icon="users"
        :empty-cta="auth()->user()?->can('create', App\Models\Student::class) ? ['label' => __('Add student'), 'url' => route('dashboard.students.create')] : null"
    >
        @foreach ($students as $student)
            <tr class="admin-table-row">
                <td class="px-4 py-3.5">
                    <div class="font-medium text-slate-900">{{ $student->user?->name ?? __('N/A') }}</div>
                    <div class="text-xs text-slate-500">{{ $student->user?->email }}</div>
                </td>
                <td class="px-4 py-3.5 font-mono text-xs text-slate-700">{{ $student->admission_number ?? $student->admission_no ?? '—' }}</td>
                <td class="px-4 py-3.5 text-slate-700">{{ $student->class?->name ?? '—' }}</td>
                <td class="px-4 py-3.5">
                    <x-badge>{{ $student->status ?? '—' }}</x-badge>
                </td>
                <td class="px-4 py-3.5 text-right">
                    @can('view', $student)
                        <x-button :href="route('dashboard.students.show', $student)" variant="ghost" size="sm">{{ __('View') }}</x-button>
                    @endcan
                </td>
            </tr>
        @endforeach
    </x-admin-data-table>
@endsection
