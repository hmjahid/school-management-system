@extends('layouts.dashboard')

@section('title', __('dashboard.report_builder') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <x-page-header :title="__('dashboard.report_builder')" description="{{ __('Choose a dataset, fields, and filters, then export to CSV.') }}">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Reports'), 'url' => route('dashboard.reports')],
                ['label' => __('dashboard.report_builder')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('dashboard.build_report') }}</h2>
        </div>
        <div class="admin-card-body">
            <form method="post" action="{{ route('dashboard.reports.builder.export') }}" class="space-y-6">
                @csrf

                {{-- Entity --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('dashboard.dataset') }}</label>
                    <select name="entity" id="report-entity" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                        @foreach($config as $key => $entity)
                            <option value="{{ $key }}">{{ $entity['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Columns --}}
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('dashboard.columns') }}</label>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" id="report-columns">
                        @foreach($config as $entityKey => $entity)
                            @foreach($entity['columns'] as $col)
                                <label class="report-col-group flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-700/50" data-entity="{{ $entityKey }}" style="{{ $loop->parent->first ? '' : 'display:none' }}">
                                    <input type="checkbox" name="columns[]" value="{{ $col['key'] }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600" {{ $loop->parent->first && $loop->index < 5 ? 'checked' : '' }}>
                                    <span class="text-slate-700 dark:text-slate-300">{{ $col['label'] }}</span>
                                </label>
                            @endforeach
                        @endforeach
                    </div>
                </div>

                {{-- Filters --}}
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Date from') }}</label>
                        <input type="date" name="date_from" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Date to') }}</label>
                        <input type="date" name="date_to" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Status') }}</label>
                        <input type="text" name="status" placeholder="e.g. active, completed" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder-slate-400 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500">
                    </div>
                    <div id="class-filter" class="hidden">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Class') }}</label>
                        <select name="class_id" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                            <option value="">{{ __('dashboard.all_classes') }}</option>
                            @foreach($classes as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4-4m0 0L8 8m4-4v12"/></svg>
                        {{ __('dashboard.export_csv') }}
                    </button>
                    <a href="{{ route('dashboard.reports') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600">{{ __('dashboard.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function(){
        var entity = document.getElementById('report-entity');
        var groups = document.querySelectorAll('.report-col-group');
        var classFilter = document.getElementById('class-filter');
        if (!entity) return;

        function update() {
            var key = entity.value;
            groups.forEach(function(g) {
                g.style.display = g.dataset.entity === key ? 'flex' : 'none';
                var input = g.querySelector('input');
                if (g.dataset.entity !== key) input.checked = false;
            });
            if (classFilter) classFilter.classList.toggle('hidden', key !== 'students');
        }

        entity.addEventListener('change', update);
        update();
    })();
    </script>
@endsection