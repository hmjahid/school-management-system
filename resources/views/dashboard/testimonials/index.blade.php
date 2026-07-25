@extends('layouts.dashboard')

@section('title', __('Testimonials') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Testimonials') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Manage student testimonials and reviews.') }}</p>
        </div>
        <a href="{{ route('dashboard.testimonials.create') }}"
            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            {{ __('Add Testimonial') }}
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Author') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Content') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Rating') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Visible') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $row)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $row->author_name }}</div>
                            @if($row->author_designation)
                                <div class="text-xs text-gray-500">{{ $row->author_designation }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700 max-w-xs truncate">{{ \Illuminate\Support\Str::limit($row->content, 80) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-0.5">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="h-4 w-4 {{ $i <= $row->rating ? 'text-amber-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <form method="post" action="{{ route('dashboard.testimonials.toggle', $row) }}">
                                @csrf
                                <button type="submit" class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold {{ $row->is_visible ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }} transition">
                                    {{ $row->is_visible ? __('Yes') : __('No') }}
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a class="text-blue-600 hover:underline" href="{{ route('dashboard.testimonials.edit', $row) }}">{{ __('Edit') }}</a>
                            <form method="post" action="{{ route('dashboard.testimonials.destroy', $row) }}" class="inline" onsubmit="return confirm('{{ __('Delete this testimonial?') }}')">
                                @csrf
                                @method('delete')
                                <button class="ml-3 text-red-600 hover:underline" type="submit">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('No testimonials yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $rows->links() }}</div>
@endsection
