@extends('layouts.dashboard')

@section('title', __('Attendance') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    @php
        $statusVariants = [
            'present' => 'success',
            'absent' => 'danger',
            'late' => 'warning',
            'half_day' => 'warning',
            'leave' => 'default',
            'holiday' => 'info',
        ];
    @endphp

    <x-page-header :title="__('Attendance')" :description="__('Daily attendance records across classes.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Attendance')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            @can('create', App\Models\Attendance::class)
                <x-button :href="route('dashboard.attendance.create')">{{ __('Record attendance') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="get" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800 md:grid-cols-2 xl:grid-cols-5">
        <div>
            <label for="filter-date" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('Date') }}</label>
            <input id="filter-date" type="date" name="date" value="{{ request('date') }}" class="admin-input">
        </div>
        <div>
            <label for="filter-status" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('Status') }}</label>
            <select id="filter-status" name="status" class="admin-select">
                <option value="">{{ __('Any') }}</option>
                <option value="present" @selected(request('status') === 'present')>{{ __('Present') }}</option>
                <option value="absent" @selected(request('status') === 'absent')>{{ __('Absent') }}</option>
                <option value="late" @selected(request('status') === 'late')>{{ __('Late') }}</option>
            </select>
        </div>
        <div>
            <label for="filter-class" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('Class') }}</label>
            <select id="filter-class" name="class_id" class="admin-select">
                <option value="">{{ __('Any class') }}</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-section" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('Section') }}</label>
            <select id="filter-section" name="section_id" class="admin-select">
                <option value="">{{ __('Any section') }}</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected(request('section_id') == $section->id)>{{ $section->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <x-button type="submit" size="sm">{{ __('Filter') }}</x-button>
            @if (request()->hasAny(['date', 'status', 'class_id', 'section_id']))
                <x-button :href="route('dashboard.attendance')" variant="secondary" size="sm">{{ __('Reset') }}</x-button>
            @endif
        </div>
    </form>

    <x-admin-data-table
        :headers="[
            ['label' => __('Date')],
            ['label' => __('Student')],
            ['label' => __('Subject')],
            ['label' => __('Status')],
        ]"
        :paginator="$records"
        empty-icon="clock"
        :empty-title="__('No attendance records.')"
        :empty-message="__('Try adjusting your filters or record attendance for a date.')"
    >
        @forelse ($records as $row)
            <tr class="admin-table-row">
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ optional($row->date)->format('Y-m-d') ?? '—' }}</td>
                <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $row->student?->user?->name ?? '—' }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $row->subject?->name ?? '—' }}</td>
                <td class="px-4 py-3">
                    <x-badge :variant="$statusVariants[$row->status ?? ''] ?? 'default'">{{ str_replace('_', ' ', $row->status ?? '—') }}</x-badge>
                </td>
            </tr>
        @empty
        @endforelse
    </x-admin-data-table>
@endsection