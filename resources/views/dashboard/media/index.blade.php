@extends('layouts.dashboard')

@section('title', __('Media library') . ' — ' . config('app.name'))

@php $selectMode = request('select') === '1'; @endphp

@section('content')
    @if($selectMode)
    <style>
        #sidebar, .admin-shell aside, nav.admin-sidebar-nav { display: none !important; }
        .admin-shell { display: block !important; }
        .admin-shell main { margin-left: 0 !important; padding: 16px !important; overflow: visible !important; }
        body { overflow: auto !important; }
    </style>
    @endif

    @if(! $selectMode)
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Media library') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Upload images and files, then reuse them in CMS pages.') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="get" class="flex flex-wrap items-center gap-2">
                <input name="search" value="{{ request('search') }}" placeholder="{{ __('Search...') }}"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <select name="category" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
                <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">{{ __('Filter') }}</button>
            </form>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc space-y-0.5 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('dashboard.media.store') }}" enctype="multipart/form-data" class="mb-8 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        @csrf
        <h2 class="mb-4 text-sm font-semibold text-gray-900">{{ __('Upload media') }}</h2>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('File') }}</label>
                <input type="file" name="file" required
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Title') }}</label>
                <input type="text" name="title" value="{{ old('title') }}" placeholder="{{ __('Auto from filename') }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Category') }}</label>
                <input type="text" name="category" value="{{ old('category') }}" placeholder="{{ __('e.g. Events') }}" list="media-categories"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none">
                <datalist id="media-categories">
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}"></option>
                    @endforeach
                </datalist>
            </div>
        </div>
        <div class="mt-4">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('Upload') }}</button>
        </div>
    </form>
    @else
    <div class="mb-4">
        <h1 class="text-lg font-bold text-gray-900">{{ __('Select media') }}</h1>
        <p class="text-sm text-gray-500">{{ __('Click an item to select it.') }}</p>
    </div>
    @endif

    @if(! $selectMode)
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @forelse($rows as $row)
            <div class="group overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="aspect-video bg-gray-100">
                    @if($row->isImage())
                        <img src="{{ $row->url() }}" alt="{{ $row->title }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center bg-gray-50">
                            <svg class="h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                    @endif
                </div>
                <div class="p-4">
                    <h3 class="truncate font-semibold text-gray-900">{{ $row->title }}</h3>
                    <p class="mt-1 text-xs text-gray-500">{{ $row->category ?: '—' }} · {{ number_format(($row->file_size ?? 0) / 1024, 1) }} KB</p>
                    <div class="mt-3 flex items-center gap-3 text-xs">
                        <button type="button" data-copy-url="{{ $row->url() }}" class="text-indigo-600 hover:underline">{{ __('Copy URL') }}</button>
                        <a href="{{ route('dashboard.media.download', $row) }}" class="text-gray-600 hover:underline">{{ __('Download') }}</a>
                        <form method="post" action="{{ route('dashboard.media.destroy', $row) }}" class="inline" onsubmit="return confirm('{{ __('Delete this item?') }}')">
                            @csrf
                            @method('delete')
                            <button type="submit" class="text-red-600 hover:underline">{{ __('Delete') }}</button>
                        </form>
                    </div>
                    <input type="text" readonly value="{{ $row->url() }}" class="mt-2 w-full rounded bg-gray-50 px-2 py-1 font-mono text-[10px] text-gray-500">
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-600">
                {{ __('No media yet. Upload your first file above.') }}
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $rows->links() }}</div>
    @else
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @forelse($rows as $row)
            <div class="media-select-item group cursor-pointer overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all hover:border-indigo-400 hover:shadow-md"
                 data-url="{{ $row->url() }}">
                <div class="aspect-video bg-gray-100">
                    @if($row->isImage())
                        <img src="{{ $row->url() }}" alt="{{ $row->title }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center bg-gray-50">
                            <svg class="h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                    @endif
                </div>
                <div class="p-3">
                    <h3 class="truncate text-sm font-semibold text-gray-900">{{ $row->title }}</h3>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $row->category ?: '—' }}</p>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-600">
                {{ __('No media yet.') }}
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $rows->links() }}</div>
    @endif

    <script>
        @if(! $selectMode)
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-copy-url]');
            if (! btn) return;
            navigator.clipboard.writeText(btn.dataset.copyUrl).then(function () {
                var original = btn.textContent;
                btn.textContent = '{{ __('Copied!') }}';
                setTimeout(function () { btn.textContent = original; }, 1500);
            });
        });
        @endif

        @if($selectMode)
        document.addEventListener('click', function (e) {
            var item = e.target.closest('.media-select-item');
            if (! item) return;
            var url = item.dataset.url;
            if (window.parent && window.parent !== window) {
                window.parent.postMessage({ type: 'media-selected', url: url }, '*');
            }
        });
        @endif
    </script>
@endsection
