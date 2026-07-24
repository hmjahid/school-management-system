@extends('layouts.dashboard')
@section('title', __('Assignments') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Assignments') }}</h1>
    @can('create', App\Models\Assignment::class)
        <a href="{{ route('dashboard.assignments.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('New assignment') }}</a>
    @endcan
</div>
<form method="get" class="mb-6 flex flex-wrap gap-3">
    <input name="search" value="{{ request('search') }}" placeholder="{{ __('Search assignments...') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm flex-1 min-w-[200px]">
    <select name="batch_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">{{ __('All batches') }}</option>@foreach($batches as $b)<option value="{{ $b->id }}" @selected(request('batch_id') == $b->id)>{{ $b->name }}</option>@endforeach</select>
    <button type="submit" class="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ __('Filter') }}</button>
</form>
<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Title') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Subject') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Batch') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Due') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Actions') }}</th></tr></thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($assignments as $a)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">{{ $a->title }}</td>
                    <td class="px-4 py-3">{{ $a->subject?->name }}</td>
                    <td class="px-4 py-3">{{ $a->batch?->name }}</td>
                    <td class="px-4 py-3">{{ $a->due_date?->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('dashboard.assignments.show', $a) }}" class="text-blue-600 hover:text-blue-800">{{ __('View') }}</a>
                        <a href="{{ route('dashboard.assignments.submissions', $a) }}" class="ml-2 text-green-600 hover:text-green-800">{{ __('Submissions') }}</a>
                        @can('update', $a)<a href="{{ route('dashboard.assignments.edit', $a) }}" class="ml-2 text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</a>@endcan
                        @can('delete', $a)
                            <form method="post" action="{{ route('dashboard.assignments.destroy', $a) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                @csrf @method('delete')
                                <button type="submit" class="ml-2 text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('No assignments found.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $assignments->links() }}</div>
@endsection