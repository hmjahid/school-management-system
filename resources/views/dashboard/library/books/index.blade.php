@extends('layouts.dashboard')
@section('title', __('dashboard.books') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('dashboard.books') }}</h1>
    @can('manage_books')
        <a href="{{ route('dashboard.library.books.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('dashboard.add_book') }}</a>
    @endcan
</div>
<form method="get" class="mb-6 flex flex-wrap gap-3">
    <input name="search" value="{{ request('search') }}" placeholder="{{ __('dashboard.search_books') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm flex-1 min-w-[200px]">
    <select name="category_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">{{ __('dashboard.category') }}</option>
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
        @endforeach
    </select>
    <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">{{ __('All status') }}</option>
        <option value="1" @selected(request('status') === '1')>{{ __('Active') }}</option>
        <option value="0" @selected(request('status') === '0')>{{ __('Inactive') }}</option>
    </select>
    <button type="submit" class="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ __('dashboard.filter') }}</button>
    <a href="{{ route('dashboard.library.books.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">{{ __('dashboard.clear') }}</a>
</form>
<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('dashboard.cover_image') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('dashboard.title') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('dashboard.author') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('dashboard.isbn') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('dashboard.category') }}</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">{{ __('dashboard.quantity') }}/{{ __('dashboard.available') }}</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">{{ __('Status') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($books as $book)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        @if($book->cover_url)
                            <img src="{{ $book->cover_url }}" alt="" class="h-10 w-8 rounded object-cover">
                        @else
                            <span class="flex h-10 w-8 items-center justify-center rounded bg-gray-100 text-xs text-gray-400">N/A</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-medium">{{ $book->title }}</td>
                    <td class="px-4 py-3">{{ $book->author ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $book->isbn ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $book->category?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center gap-1">
                            <span class="font-medium">{{ $book->quantity }}</span>
                            <span class="text-gray-400">/</span>
                            <span class="{{ $book->available_quantity > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $book->available_quantity }}</span>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($book->status)
                            <span class="inline-block rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">{{ __('Active') }}</span>
                        @else
                            <span class="inline-block rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">{{ __('Inactive') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('dashboard.library.books.show', $book) }}" class="text-blue-600 hover:text-blue-800">{{ __('View') }}</a>
                        <a href="{{ route('dashboard.library.books.edit', $book) }}" class="ml-2 text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</a>
                        <form method="post" action="{{ route('dashboard.library.books.destroy', $book) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                            @csrf @method('delete')
                            <button type="submit" class="ml-2 text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">{{ __('dashboard.no_books_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $books->links() }}</div>
@endsection
