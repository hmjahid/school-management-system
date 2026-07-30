@extends('layouts.dashboard')
@section('title', $book->title . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('dashboard.book_details') }}</h1>
    <div class="flex gap-2">
        <a href="{{ route('dashboard.library.books.index') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">{{ __('Back') }}</a>
        <a href="{{ route('dashboard.library.books.edit', $book) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ __('Edit') }}</a>
    </div>
</div>
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-500">{{ __('dashboard.title') }}</dt><dd class="font-medium">{{ $book->title }}</dd></div>
                <div><dt class="text-gray-500">{{ __('dashboard.author') }}</dt><dd class="font-medium">{{ $book->author ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">{{ __('dashboard.publisher') }}</dt><dd class="font-medium">{{ $book->publisher ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">{{ __('dashboard.isbn') }}</dt><dd class="font-mono font-medium">{{ $book->isbn ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">{{ __('dashboard.category') }}</dt><dd class="font-medium">{{ $book->category?->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">{{ __('dashboard.shelf_location') }}</dt><dd class="font-medium">{{ $book->shelf_location ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">{{ __('dashboard.quantity') }}</dt><dd class="font-medium">{{ $book->quantity }}</dd></div>
                <div><dt class="text-gray-500">{{ __('dashboard.available_quantity') }}</dt><dd class="font-medium {{ $book->available_quantity > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $book->available_quantity }}</dd></div>
                <div><dt class="text-gray-500">{{ __('dashboard.purchase_date') }}</dt><dd class="font-medium">{{ $book->purchase_date?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">{{ __('dashboard.price') }}</dt><dd class="font-medium">{{ $book->price ? number_format($book->price, 2) : '—' }}</dd></div>
            </dl>
            @if($book->description)
                <div class="mt-4 border-t border-gray-100 pt-4">
                    <dt class="mb-1 text-sm text-gray-500">{{ __('Description') }}</dt>
                    <dd class="text-sm text-gray-700">{{ $book->description }}</dd>
                </div>
            @endif
        </div>

        @if($book->currentIssues->isNotEmpty())
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('dashboard.currently_issued') }}</h2>
                </div>
                <div class="divide-y divide-gray-200 text-sm">
                    @foreach($book->currentIssues as $issue)
                        <div class="flex items-center justify-between px-6 py-3">
                            <div>
                                <span class="font-medium">{{ $issue->student?->first_name ? trim($issue->student->first_name . ' ' . $issue->student->last_name) : ($issue->teacher?->name ?? '—') }}</span>
                                <span class="ml-2 text-gray-500">{{ $issue->issue_date->format('d M Y') }} → {{ $issue->due_date->format('d M Y') }}</span>
                            </div>
                            <a href="{{ route('dashboard.library.issues.show', $issue) }}" class="text-blue-600 hover:text-blue-800">{{ __('View') }}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    <div class="space-y-4">
        @if($book->cover_url)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <img src="{{ $book->cover_url }}" alt="{{ $book->title }}" class="w-full rounded-lg object-cover">
            </div>
        @endif
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <dl class="space-y-3 text-sm">
                <div><dt class="text-gray-500">{{ __('Status') }}</dt><dd class="font-medium">@if($book->status)<span class="text-green-600">{{ __('Active') }}</span>@else<span class="text-red-600">{{ __('Inactive') }}</span>@endif</dd></div>
                <div><dt class="text-gray-500">{{ __('Added by') }}</dt><dd class="font-medium">{{ $book->createdBy?->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">{{ __('Created') }}</dt><dd class="font-medium">{{ $book->created_at->format('d M Y') }}</dd></div>
            </dl>
        </div>
    </div>
</div>
@endsection
