@extends('layouts.dashboard')
@section('title', __('Student ID cards') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Student ID cards') }}</h1>
    @can('create', App\Models\StudentIdCard::class)
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dashboard.student-id-cards.batch.create') }}" class="rounded-lg border border-blue-600 bg-white px-4 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50">{{ __('Batch generate') }}</a>
            <a href="{{ route('dashboard.student-id-cards.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('New ID card') }}</a>
        </div>
    @endcan
</div>
<form method="get" class="mb-6 flex flex-wrap gap-3">
    <input name="search" value="{{ request('search') }}" placeholder="{{ __('Search by name or card number...') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm flex-1 min-w-[200px]">
    <button type="submit" class="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ __('Search') }}</button>
</form>
<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left font-semibold text-gray-600">#</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Student') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Card number') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Issue date') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Expiry') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Status') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Actions') }}</th></tr></thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($idCards as $c)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $c->id }}</td>
                    <td class="px-4 py-3">{{ $c->student?->user?->name ?? 'N/A' }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $c->id_card_number }}</td>
                    <td class="px-4 py-3">{{ $c->issue_date?->format('d M Y') }}</td>
                    <td class="px-4 py-3">{{ $c->expiry_date?->format('d M Y') ?? '—' }}</td>
                    <td class="px-4 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $c->status === 'active' ? 'bg-green-100 text-green-700' : ($c->status === 'revoked' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">{{ __(ucfirst($c->status)) }}</span></td>
                    <td class="px-4 py-3">
                        <a href="{{ route('dashboard.student-id-cards.show', $c) }}" class="text-blue-600 hover:text-blue-800">{{ __('View') }}</a>
                        @can('update', $c)<a href="{{ route('dashboard.student-id-cards.edit', $c) }}" class="ml-2 text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</a>@endcan
                        <button type="button" data-preview-url="{{ route('dashboard.student-id-cards.preview', $c) }}" class="ml-2 text-purple-600 hover:text-purple-800">{{ __('Preview') }}</button>
                        <a href="{{ route('dashboard.student-id-cards.print', $c) }}" target="_blank" class="ml-2 text-green-600 hover:text-green-800">{{ __('Print') }}</a>
                        @can('delete', $c)
                            <form method="post" action="{{ route('dashboard.student-id-cards.destroy', $c) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                @csrf @method('delete')
                                <button type="submit" class="ml-2 text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">{{ __('No ID cards found.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $idCards->links() }}</div>
@endsection