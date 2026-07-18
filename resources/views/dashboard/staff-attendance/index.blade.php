@extends('layouts.dashboard')

@section('title', __('Staff attendance') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Staff attendance')" :description="__('Mark teacher / staff attendance for the day.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Staff attendance')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <form method="get" class="flex flex-wrap items-center gap-2">
                <input type="date" name="date" value="{{ $date->toDateString() }}" class="admin-input">
                <x-button type="submit" variant="secondary" size="sm">{{ __('Change date') }}</x-button>
                <x-button :href="route('dashboard.staff-attendance.report')" variant="ghost" size="sm">{{ __('Monthly report') }}</x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <form method="post" action="{{ route('dashboard.staff-attendance.store') }}">
        @csrf
        <input type="hidden" name="date" value="{{ $date->toDateString() }}">

        <x-card :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3">{{ __('Staff') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                            <th class="px-4 py-3">{{ __('Note') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($teachers as $t)
                            @php $rec = $existing[$t->id] ?? null; @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $t->user?->name ?? $t->employee_id }}</div>
                                    <div class="text-xs text-slate-500">{{ $t->user?->email }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <select name="status[{{ $t->id }}]" class="admin-select">
                                        @foreach(\App\Models\StaffAttendance::STATUSES as $val => $label)
                                            <option value="{{ $val }}" @selected(($rec?->status ?? 'present') === $val)>{{ __($label) }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" name="note[{{ $t->id }}]" value="{{ $rec?->note }}" maxlength="500" class="admin-input" placeholder="—">
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-sm text-slate-500">{{ __('No staff members yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($teachers->isNotEmpty())
                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                    <x-button type="submit">{{ __('Save attendance') }}</x-button>
                </div>
            @endif
        </x-card>
    </form>
@endsection