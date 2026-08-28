@extends('layouts.dashboard')
@section('title', __('Progress Reports') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Progress Reports') }}</h1>
</div>
@include('dashboard.partials.form-errors')
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
    <select name="batch_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">{{ __('All batches') }}</option>
        @foreach($batches as $b)
            <option value="{{ $b->id }}" @selected(request('batch_id') == $b->id)>{{ $b->name }}</option>
        @endforeach
    </select>
    <button type="submit" class="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ __('Filter') }}</button>
</form>
<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">#</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Student') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Class') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Section') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Roll') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($students as $student)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $student->id }}</td>
                    <td class="px-4 py-3">{{ $student->user?->name ?? 'N/A' }}</td>
                    <td class="px-4 py-3">{{ $student->class?->name ?? 'N/A' }}</td>
                    <td class="px-4 py-3">{{ $student->section?->name ?? 'N/A' }}</td>
                    <td class="px-4 py-3">{{ $student->roll_number ?? 'N/A' }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('dashboard.progress-reports.generate', $student) }}" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">{{ __('Generate Progress Report') }}</a>
                        <a href="{{ route('dashboard.progress-reports.generate', ['student' => $student, 'view' => 1]) }}" target="_blank" class="ml-2 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200">{{ __('Preview') }}</a>
                    </td>
                </tr>
@empty
            <tr><td colspan="6" class="px-4 py-10">
                <x-empty-state
                    icon="document"
                    :title="__('No students found')"
                    :message="__('Select a class with students to generate progress reports.')"
                />
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $students->links() }}</div>
@endsection
