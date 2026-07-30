@extends('layouts.dashboard')
@section('title', __('Book Issue') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Book Issue') }} #{{ $issue->id }}</h1>
    <div class="flex gap-2">
        <a href="{{ route('dashboard.library.issues.index') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">{{ __('Back') }}</a>
    </div>
</div>
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-500">{{ __('dashboard.title') }}</dt><dd class="font-medium">{{ $issue->book?->title }}</dd></div>
                <div><dt class="text-gray-500">{{ __('Author') }}</dt><dd class="font-medium">{{ $issue->book?->author ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">{{ __('ISBN') }}</dt><dd class="font-mono font-medium">{{ $issue->book?->isbn ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">{{ __('dashboard.borrower') }}</dt>
                    <dd class="font-medium">
                        @if($issue->student)
                            {{ trim($issue->student->first_name . ' ' . $issue->student->last_name) }}
                            <span class="text-xs text-gray-400">({{ __('Student') }})</span>
                        @elseif($issue->teacher)
                            {{ $issue->teacher->user?->name ?? $issue->teacher->employee_id }}
                            <span class="text-xs text-gray-400">({{ __('Teacher') }})</span>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div><dt class="text-gray-500">{{ __('dashboard.issue_date') }}</dt><dd class="font-medium">{{ $issue->issue_date->format('d M Y') }}</dd></div>
                <div><dt class="text-gray-500">{{ __('dashboard.due_date') }}</dt><dd class="font-medium {{ $issue->isOverdue() ? 'text-red-600' : '' }}">{{ $issue->due_date->format('d M Y') }}@if($issue->isOverdue()) ({{ __('Overdue') }})@endif</dd></div>
                <div><dt class="text-gray-500">{{ __('dashboard.return_date') }}</dt><dd class="font-medium">{{ $issue->return_date?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">{{ __('Status') }}</dt>
                    <dd>
                        @php
                            $statusColors = ['issued' => 'bg-yellow-100 text-yellow-800', 'returned' => 'bg-green-100 text-green-800', 'lost' => 'bg-red-100 text-red-800', 'damaged' => 'bg-orange-100 text-orange-800'];
                            $color = $statusColors[$issue->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium {{ $color }}">{{ __($issue->status) }}</span>
                    </dd>
                </div>
            </dl>
            @if($issue->notes)
                <div class="mt-4 border-t border-gray-100 pt-4">
                    <dt class="mb-1 text-sm text-gray-500">{{ __('Notes') }}</dt>
                    <dd class="text-sm text-gray-700">{{ $issue->notes }}</dd>
                </div>
            @endif
        </div>
    </div>
    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <dl class="space-y-3 text-sm">
                <div><dt class="text-gray-500">{{ __('dashboard.late_fee') }}</dt>
                    <dd class="font-medium">
                        @if($issue->late_fee > 0)
                            <span class="text-red-600">{{ number_format($issue->late_fee, 2) }}</span>
                        @else
                            <span class="text-gray-400">{{ number_format(0, 2) }}</span>
                        @endif
                    </dd>
                </div>
                <div><dt class="text-gray-500">{{ __('Fine status') }}</dt>
                    <dd>
                        @if($issue->fine_paid)
                            <span class="inline-block rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">{{ __('dashboard.fine_paid') }}</span>
                        @elseif($issue->late_fee > 0)
                            <span class="inline-block rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">{{ __('Unpaid') }}</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </dd>
                </div>
                <div><dt class="text-gray-500">{{ __('Issued by') }}</dt><dd class="font-medium">{{ $issue->issuedBy?->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">{{ __('Created') }}</dt><dd class="font-medium">{{ $issue->created_at->format('d M Y H:i') }}</dd></div>
            </dl>
        </div>

        <div class="space-y-2">
            @if($issue->status === 'issued')
                <form method="post" action="{{ route('dashboard.library.issues.return', $issue) }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">{{ __('dashboard.return_book') }}</button>
                </form>
                <form method="post" action="{{ route('dashboard.library.issues.lost', $issue) }}" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                    @csrf
                    <button type="submit" class="w-full rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">{{ __('dashboard.mark_lost') }}</button>
                </form>
            @endif
            @can('collect_dues')
                @if($issue->late_fee > 0 && !$issue->fine_paid)
                    <form method="post" action="{{ route('dashboard.library.issues.fine', $issue) }}">
                        @csrf
                        <button type="submit" class="w-full rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">{{ __('dashboard.collect_fine') }}</button>
                    </form>
                @endif
            @endcan
            <form method="post" action="{{ route('dashboard.library.issues.destroy', $issue) }}" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                @csrf @method('delete')
                <button type="submit" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Delete') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
