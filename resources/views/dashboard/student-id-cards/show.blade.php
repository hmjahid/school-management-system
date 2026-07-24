@extends('layouts.dashboard')
@section('title', __('ID card') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('ID card') }}: {{ $studentIdCard->id_card_number }}</h1>
    <div class="flex gap-2">
        <a href="{{ route('dashboard.student-id-cards.index') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">{{ __('Back') }}</a>
        @can('update', $studentIdCard)<a href="{{ route('dashboard.student-id-cards.edit', $studentIdCard) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ __('Edit') }}</a>@endcan
        <a href="{{ route('dashboard.student-id-cards.print', $studentIdCard) }}" target="_blank" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">{{ __('Print') }}</a>
        @can('delete', $studentIdCard)
            <form method="post" action="{{ route('dashboard.student-id-cards.destroy', $studentIdCard) }}" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                @csrf @method('delete')
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">{{ __('Delete') }}</button>
            </form>
        @endcan
    </div>
</div>
<div class="grid gap-6 sm:grid-cols-2">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <dl class="space-y-4 text-sm">
            <div><dt class="text-gray-500">{{ __('Card number') }}</dt><dd class="font-mono font-medium">{{ $studentIdCard->id_card_number }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Student') }}</dt><dd class="font-medium">{{ $studentIdCard->student?->user?->name }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Class') }}</dt><dd>{{ $studentIdCard->student?->class?->name ?? 'N/A' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Section') }}</dt><dd>{{ $studentIdCard->student?->section?->name ?? 'N/A' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Blood group') }}</dt><dd>{{ $studentIdCard->blood_group ?? 'N/A' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Issue date') }}</dt><dd>{{ $studentIdCard->issue_date?->format('d M Y') }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Expiry date') }}</dt><dd>{{ $studentIdCard->expiry_date?->format('d M Y') ?? __('Never') }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Status') }}</dt><dd><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $studentIdCard->status === 'active' ? 'bg-green-100 text-green-700' : ($studentIdCard->status === 'revoked' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">{{ __(ucfirst($studentIdCard->status)) }}</span></dd></div>
        </dl>
    </div>
</div>
@endsection