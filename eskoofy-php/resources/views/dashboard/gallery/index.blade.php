@extends('layouts.dashboard')

@section('title', __('Gallery') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Gallery') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Manage published photos shown on the public gallery page.') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="get" class="flex flex-wrap items-center gap-2">
                <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search...') }}"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                <select name="category" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
                <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">{{ __('Filter') }}</button>
            </form>
            <a href="{{ route('dashboard.gallery.create') }}"
                class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                {{ __('New') }}
            </a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @forelse($rows as $row)
            <a href="{{ route('dashboard.gallery.edit', $row) }}" class="group overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow">
                <div class="aspect-video bg-gray-100">
                    <img src="{{ asset('storage/' . $row->image_path) }}" alt="{{ $row->title }}" class="h-full w-full object-cover">
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-semibold text-gray-900 truncate">{{ $row->title }}</h3>
                        @if($row->is_published)
                            <x-badge variant="success">{{ __('On') }}</x-badge>
                        @else
                            <x-badge variant="warning">{{ __('Off') }}</x-badge>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ $row->category }}</p>
                </div>
            </a>
        @empty
            <div class="col-span-full rounded-2xl border-2 border-dashed border-slate-200 p-12">
                <x-empty-state :title="__('No gallery items yet')" :message="__('Upload your first photo to get started.')" icon="sparkles" />
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $rows->links() }}</div>
@endsection

