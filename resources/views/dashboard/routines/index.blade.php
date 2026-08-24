@extends('layouts.dashboard')
@section('title', __('Routines') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-bold text-gray-900">{{ $type === 'exam' ? __('Exam routines') : __('Class routines') }}</h1>
    <div class="flex gap-2">
        <a href="{{ route('dashboard.routines.index', ['type' => 'class']) }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $type === 'class' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">{{ __('Class Routine') }}</a>
        <a href="{{ route('dashboard.routines.index', ['type' => 'exam']) }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $type === 'exam' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">{{ __('Exam Routine') }}</a>
    </div>
    @can('create', App\Models\Routine::class)
        <a href="{{ route('dashboard.routines.create', ['type' => $type]) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Add entry') }}</a>
    @endcan
</div>
<form method="get" class="mb-6 flex flex-wrap gap-3">
    <select name="class_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">{{ __('All classes') }}</option>
        @foreach($classes as $c)
            <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->name }}</option>
        @endforeach
    </select>
    <select name="section_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">{{ __('All sections') }}</option>
        @foreach($sections as $s)
            <option value="{{ $s->id }}" @selected(request('section_id') == $s->id)>{{ $s->name }}</option>
        @endforeach
    </select>
    <select name="day" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">{{ __('All days') }}</option>
        @foreach(App\Models\Routine::DAYS as $k => $d)
            <option value="{{ $k }}" @selected(request('day') == $k)>{{ __($d) }}</option>
        @endforeach
    </select>
    <button type="submit" class="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ __('Filter') }}</button>
</form>
<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Day') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Time') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Class') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Section') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Subject') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Teacher') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Room') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($routines as $r)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">{{ __(App\Models\Routine::DAYS[$r->day_of_week] ?? '') }}</td>
                    <td class="px-4 py-3">{{ substr($r->start_time, 0, 5) }} - {{ substr($r->end_time, 0, 5) }}</td>
                    <td class="px-4 py-3">{{ $r->schoolClass?->name }}</td>
                    <td class="px-4 py-3">{{ $r->section?->name ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $r->subject?->name }}</td>
                    <td class="px-4 py-3">{{ $r->teacher?->user?->name }}</td>
                    <td class="px-4 py-3">{{ $r->room_number ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('dashboard.routines.show', $r) }}" class="text-blue-600 hover:text-blue-800">{{ __('View') }}</a>
                        <a href="{{ route('dashboard.routines.edit', $r) }}" class="ml-2 text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</a>
                        <form method="post" action="{{ route('dashboard.routines.destroy', $r) }}" class="inline" onsubmit="return confirm('{{ __('Delete this entry?') }}')">@csrf @method('delete')<button type="submit" class="ml-2 text-red-600 hover:text-red-800">{{ __('Delete') }}</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">{{ __('No routines found.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $routines->links() }}</div>
@endsection