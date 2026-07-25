@extends('layouts.dashboard')
@section('title', __('Testimonials') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Testimonials') }}</h1>
    @can('create', App\Models\Testimonial::class)
        <a href="{{ route('dashboard.testimonials.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('New testimonial') }}</a>
    @endcan
</div>
@include('dashboard.partials.form-errors')
<form method="get" class="mb-6 flex flex-wrap gap-3">
    <input name="search" value="{{ request('search') }}" placeholder="{{ __('Search by name, number...') }}" class="min-w-[200px] flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
    <select name="type" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">{{ __('All types') }}</option>
        @foreach(App\Models\Testimonial::TYPES as $t)
            <option value="{{ $t }}" @selected(request('type') === $t)>{{ __(ucfirst(str_replace('_', ' ', $t))) }}</option>
        @endforeach
    </select>
    <button type="submit" class="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ __('Filter') }}</button>
</form>
<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">#</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Student') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Title') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Type') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Number') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Issue date') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Status') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($testimonials as $t)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $t->id }}</td>
                    <td class="px-4 py-3">{{ $t->student?->user?->name ?? 'N/A' }}</td>
                    <td class="px-4 py-3 font-medium">{{ $t->name }}</td>
                    <td class="px-4 py-3"><span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">{{ __(ucfirst(str_replace('_', ' ', $t->testimonial_type))) }}</span></td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $t->testimonial_number }}</td>
                    <td class="px-4 py-3">{{ $t->issue_date?->format('d M Y') }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $t->status === 'issued' ? 'bg-green-100 text-green-700' : ($t->status === 'revoked' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ __(ucfirst($t->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('dashboard.testimonials.show', $t) }}" class="text-blue-600 hover:text-blue-800">{{ __('View') }}</a>
                        <a href="{{ route('dashboard.testimonials.print', $t) }}" target="_blank" class="ml-2 text-green-600 hover:text-green-800">{{ __('Print') }}</a>
                        @can('update', $t)
                            <a href="{{ route('dashboard.testimonials.edit', $t) }}" class="ml-2 text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</a>
                        @endcan
                        @can('delete', $t)
                            <form method="post" action="{{ route('dashboard.testimonials.destroy', $t) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                @csrf @method('delete')
                                <button type="submit" class="ml-2 text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">{{ __('No testimonials found.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $testimonials->links() }}</div>
@endsection
