@extends('layouts.dashboard')

@section('title', __('Bulk attendance') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Bulk attendance')" :description="__('Mark attendance for an entire class or section in one screen.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Attendance'), 'url' => route('dashboard.attendance')],
                ['label' => __('Bulk')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <form method="get" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-4">
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-600">{{ __('Date') }}</label>
            <input type="date" name="date" value="{{ $date->toDateString() }}" class="admin-input">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-600">{{ __('Batch / Class') }}</label>
            <select name="batch_id" class="admin-select">
                <option value="">—</option>
                @foreach($batches as $b)
                    <option value="{{ $b->id }}" @selected($batchId == $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-600">{{ __('Section') }}</label>
            <select name="section_id" class="admin-select">
                <option value="">{{ __('All') }}</option>
                @foreach($sections as $s)
                    <option value="{{ $s->id }}" @selected($sectionId == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <x-button type="submit" class="w-full">{{ __('Load students') }}</x-button>
        </div>
    </form>

    @if($students->isEmpty())
        <x-card>
            <p class="py-6 text-center text-sm text-slate-500">{{ __('Select a batch and date to load students.') }}</p>
        </x-card>
    @else
        <form method="post" action="{{ route('dashboard.attendance.bulk.store') }}">
            @csrf
            <input type="hidden" name="date" value="{{ $date->toDateString() }}">
            <input type="hidden" name="batch_id" value="{{ $batchId }}">
            <input type="hidden" name="section_id" value="{{ $sectionId }}">

            <x-card :title="__('Mark attendance for') . ' ' . $date->format('M j, Y')" :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">{{ __('Student') }}</th>
                                <th class="px-4 py-3">{{ __('Roll') }}</th>
                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                <th class="px-4 py-3">{{ __('Remarks') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($students as $i => $student)
                                @php $current = $existing[$student->id]->status ?? ''; @endphp
                                <tr>
                                    <td class="px-4 py-2 text-slate-500">{{ $i + 1 }}</td>
                                    <td class="px-4 py-2 font-medium text-slate-900">{{ $student->full_name ?? trim($student->first_name.' '.$student->last_name) }}</td>
                                    <td class="px-4 py-2 text-slate-700">{{ $student->roll_no ?? $student->roll_number ?? '—' }}</td>
                                    <td class="px-4 py-2">
                                        <select name="status[{{ $student->id }}]" class="admin-select">
                                            @foreach($statuses as $val => $label)
                                                <option value="{{ $val }}" @selected($current === $val)>{{ __($label) }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="text" name="remarks[{{ $student->id }}]" value="{{ $existing[$student->id]->remarks ?? '' }}" maxlength="500" class="admin-input" placeholder="—">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                    <x-button :href="route('dashboard.attendance.bulk', ['date' => $date->toDateString(), 'batch_id' => $batchId, 'section_id' => $sectionId])" variant="ghost">{{ __('Reset') }}</x-button>
                    <x-button type="submit">{{ __('Save attendance') }}</x-button>
                </div>
            </x-card>
        </form>
    @endif
@endsection