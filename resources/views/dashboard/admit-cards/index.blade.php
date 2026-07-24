@extends('layouts.dashboard')
@section('title', __('Admit cards') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Admit cards') }}</h1>
    @can('create', App\Models\AdmitCard::class)
        <a href="{{ route('dashboard.admit-cards.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Generate admit card') }}</a>
    @endcan
</div>
<form method="get" class="mb-6 flex flex-wrap gap-3">
    <input name="search" value="{{ request('search') }}" placeholder="{{ __('Search student or number...') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm flex-1 min-w-[200px]">
    <select name="exam_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">{{ __('All exams') }}</option>@foreach($exams as $e)<option value="{{ $e->id }}" @selected(request('exam_id') == $e->id)>{{ $e->name }}</option>@endforeach</select>
    <button type="submit" class="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ __('Filter') }}</button>
</form>
<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left font-semibold text-gray-600">#</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Student') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Exam') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Card number') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Issue date') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Actions') }}</th></tr></thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($admitCards as $ac)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $ac->id }}</td>
                    <td class="px-4 py-3">{{ $ac->student?->user?->name ?? 'N/A' }}</td>
                    <td class="px-4 py-3">{{ $ac->exam?->name }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $ac->admit_card_number }}</td>
                    <td class="px-4 py-3">{{ $ac->issue_date?->format('d M Y') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('dashboard.admit-cards.show', $ac) }}" class="text-blue-600 hover:text-blue-800">{{ __('View') }}</a>
                        <a href="{{ route('dashboard.admit-cards.print', $ac) }}" target="_blank" class="ml-2 text-green-600 hover:text-green-800">{{ __('Print') }}</a>
                        @can('update', $ac)
                            <a href="{{ route('dashboard.admit-cards.edit', $ac) }}" class="ml-2 text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</a>
                        @endcan
                        @can('delete', $ac)
                            <form method="post" action="{{ route('dashboard.admit-cards.destroy', $ac) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                @csrf @method('delete')
                                <button type="submit" class="ml-2 text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">{{ __('No admit cards found.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $admitCards->links() }}</div>
@endsection