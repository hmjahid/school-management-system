@extends('layouts.dashboard')

@section('title', __('Exams') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    @php
        $statusVariants = [
            'upcoming' => 'info',
            'ongoing' => 'warning',
            'completed' => 'success',
        ];
    @endphp

    <x-page-header :title="__('Exams')" :description="__('Scheduled exams, results entry, and publishing.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Exams')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            @can('create', App\Models\Exam::class)
                <x-button :href="route('dashboard.exams.create')">{{ __('Schedule exam') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="get" class="mb-6 flex flex-wrap items-end gap-2">
        <div>
            <label for="filter-status" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('Status') }}</label>
            <select id="filter-status" name="status" class="admin-select">
                <option value="">{{ __('Any') }}</option>
                <option value="upcoming" @selected(request('status') === 'upcoming')>{{ __('Upcoming') }}</option>
                <option value="ongoing" @selected(request('status') === 'ongoing')>{{ __('Ongoing') }}</option>
                <option value="completed" @selected(request('status') === 'completed')>{{ __('Completed') }}</option>
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
        <div class="flex items-end gap-2">
            <x-button type="submit" size="sm">{{ __('Filter') }}</x-button>
            @if (request()->hasAny(['status', 'class_id']))
                <x-button :href="route('dashboard.exams')" variant="secondary" size="sm">{{ __('Reset') }}</x-button>
            @endif
        </div>
    </form>

    <x-admin-data-table
        :headers="[
            ['label' => __('Title')],
            ['label' => __('Subject')],
            ['label' => __('Starts')],
            ['label' => __('Status')],
            ['label' => __('Actions'), 'class' => 'text-right'],
        ]"
        :paginator="$exams"
        empty-icon="document"
        :empty-title="__('No exams found.')"
        :empty-message="__('Schedule an exam to start entering results.')"
    >
        @forelse ($exams as $exam)
            <tr class="admin-table-row">
                <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $exam->name ?? '—' }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $exam->subject?->name ?? '—' }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ optional($exam->start_date)->format('Y-m-d H:i') ?? '—' }}</td>
                <td class="px-4 py-3">
                    <x-badge :variant="$statusVariants[$exam->status ?? ''] ?? 'default'">{{ $exam->status ?? '—' }}</x-badge>
                </td>
                <td class="px-4 py-3 text-right">
                    <x-button :href="route('dashboard.exams.results', $exam)" variant="ghost" size="sm">{{ __('Results') }}</x-button>
                </td>
            </tr>
        @empty
        @endforelse
    </x-admin-data-table>
@endsection