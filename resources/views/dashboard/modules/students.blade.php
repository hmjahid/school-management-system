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

    <form method="get" class="mb-4 flex flex-wrap items-end gap-2">
        <div class="flex-1 min-w-[200px]">
            <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Search') }}</label>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search name, email, admission…') }}" class="admin-input w-full">
        </div>
        <div class="min-w-[160px]">
            <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Class') }}</label>
            <select name="class_id" class="admin-select w-full">
                <option value="">{{ __('All classes') }}</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[160px]">
            <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Batch') }}</label>
            <select name="batch_id" class="admin-select w-full">
                <option value="">{{ __('All batches') }}</option>
                @foreach($batches as $b)
                    <option value="{{ $b->id }}" @selected(request('batch_id') == $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[160px]">
            <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Section') }}</label>
            <select name="section_id" class="admin-select w-full">
                <option value="">{{ __('All sections') }}</option>
                @foreach($sections as $s)
                    <option value="{{ $s->id }}" @selected(request('section_id') == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <x-button type="submit" variant="secondary">{{ __('Filter') }}</x-button>
        @if(request()->hasAny(['search', 'class_id', 'batch_id']))
            <x-button :href="route('dashboard.students')" variant="ghost">{{ __('Clear') }}</x-button>
        @endif
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
