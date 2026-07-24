@extends('layouts.dashboard')
@section('title', __('Admit card') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Admit card') }}: {{ $admitCard->admit_card_number }}</h1>
    <div class="flex gap-2">
        <a href="{{ route('dashboard.admit-cards.index') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">{{ __('Back') }}</a>
        <a href="{{ route('dashboard.admit-cards.print', $admitCard) }}" target="_blank" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">{{ __('Print') }}</a>
        @can('update', $admitCard)
            <a href="{{ route('dashboard.admit-cards.edit', $admitCard) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ __('Edit') }}</a>
        @endcan
        @can('delete', $admitCard)
            <form method="post" action="{{ route('dashboard.admit-cards.destroy', $admitCard) }}" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                @csrf @method('delete')
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">{{ __('Delete') }}</button>
            </form>
        @endcan
    </div>
</div>
<div class="grid gap-6 sm:grid-cols-2">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <dl class="space-y-4 text-sm">
            <div><dt class="text-gray-500">{{ __('Card number') }}</dt><dd class="font-mono font-medium">{{ $admitCard->admit_card_number }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Student') }}</dt><dd class="font-medium">{{ $admitCard->student?->user?->name }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Class') }}</dt><dd>{{ $admitCard->student?->class?->name ?? 'N/A' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Section') }}</dt><dd>{{ $admitCard->student?->section?->name ?? 'N/A' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Exam') }}</dt><dd class="font-medium">{{ $admitCard->exam?->name }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Issue date') }}</dt><dd>{{ $admitCard->issue_date?->format('d M Y') }}</dd></div>
        </dl>
    </div>
</div>
@endsection