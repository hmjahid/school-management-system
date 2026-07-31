@extends(request('select') === '1' ? 'layouts.media-picker' : 'layouts.dashboard')

@section('title', __('Media library') . ' — ' . config('app.name'))

@php $selectMode = request('select') === '1'; @endphp

@section('content')
    @if($selectMode)
    <div class="flex flex-col" style="height:100%;">
        <div class="border-b border-slate-200 bg-white/90 px-5 py-4 backdrop-blur">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-base font-bold text-gray-900">{{ __('Media Library') }}</h1>
                        <p class="text-xs text-gray-500">{{ __('Click an image to insert it into the page.') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <form method="get" class="relative">
                        <input type="hidden" name="select" value="1">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search media…') }}"
                            class="w-56 rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    </form>
                    <select name="category" form="media-filter" class="rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-8 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" onchange="this.form.submit()">
                        <option value="">{{ __('All categories') }}</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                    <button type="button" onclick="window.parent.postMessage({ type: 'media-close' }, '*')" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        {{ __('Close') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-5 py-5">
            <div class="mx-auto max-w-6xl">
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
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        @forelse($rows as $row)
            <div class="media-select-item group relative cursor-pointer overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-400 hover:shadow-lg"
                 data-url="{{ $row->url() }}">
                <div class="relative aspect-[4/3] bg-gray-100">
                    @if($row->isImage())
                        <img src="{{ $row->url() }}" alt="{{ $row->title }}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
                    @else
                        <div class="flex h-full w-full items-center justify-center bg-gray-50">
                            <svg class="h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                    @endif
                    <div class="absolute inset-0 bg-indigo-600/0 transition-all duration-200 group-hover:bg-indigo-600/20"></div>
                    <div class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 shadow-md">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ __('Select') }}
                        </span>
                    </div>
                </div>
                <div class="p-3">
                    <h3 class="truncate text-sm font-semibold text-gray-900">{{ $row->title }}</h3>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $row->category ?: '—' }} · {{ number_format(($row->file_size ?? 0) / 1024, 1) }} KB</p>
                </div>
            </div>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 bg-white p-14 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-50">
                    <svg class="h-7 w-7 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="mt-4 text-sm font-semibold text-gray-900">{{ __('No media yet') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('Upload images in the media library first.') }}</p>
                <a href="{{ route('dashboard.media.index') }}" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('Open media library') }}
                </a>
            </div>
        @endforelse
    </div>

    @if($rows->hasPages())
    <div class="mt-6 flex justify-center">{{ $rows->links() }}</div>
    @endif
    @endif

    @if($selectMode)
            </div>
        </div>
    </div>
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
