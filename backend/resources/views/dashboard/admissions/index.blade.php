@extends('layouts.dashboard')

@section('title', __('Admissions') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Admissions') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Review applications and schedule admission tests.') }}</p>
        </div>
        <form method="get" class="flex flex-wrap items-center gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search name/email/phone/app no...') }}"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm" />
            <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" onchange="this.form.submit()">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (['draft','submitted','under_review','approved','rejected','waitlisted','enrolled','cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">{{ __('Filter') }}</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Application') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Applicant') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Test') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $row)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs text-gray-700">{{ $row->application_number }}</div>
                            <div class="text-xs text-gray-500">{{ $row->submitted_at?->format('Y-m-d') ?: '—' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $row->full_name }}</div>
                            <div class="text-xs text-gray-500">{{ $row->email }} · {{ $row->phone }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $row->status_badge }}">{{ $row->status_label }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            @if($row->latestTest?->scheduled_at)
                                <div class="text-xs">{{ $row->latestTest->scheduled_at->format('Y-m-d H:i') }}</div>
                                <div class="text-xs text-gray-500">{{ $row->latestTest->venue ?: '—' }}</div>
                            @else
                                <span class="text-xs text-gray-500">{{ __('Not scheduled') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a class="text-blue-600 hover:underline" href="{{ route('dashboard.admissions.show', $row) }}">{{ __('View') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('No admissions found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $rows->links() }}</div>
@endsection

