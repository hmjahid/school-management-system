@extends('layouts.dashboard')

@section('title', __('Admissions') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Admissions')" :description="__('Review applications and schedule admission tests.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Admissions')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <form method="get" class="flex flex-wrap items-center gap-2">
                <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search…') }}" class="admin-input w-48">
                <select name="status" class="admin-select" onchange="this.form.submit()">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (['draft','submitted','under_review','approved','rejected','waitlisted','enrolled','cancelled'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
                <x-button type="submit" variant="secondary" size="sm">{{ __('Filter') }}</x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-6 {{ $settings->is_open ? '!border-emerald-200' : '!border-amber-200' }}" :padding="true">
        <form method="post" action="{{ route('dashboard.admissions.toggle') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="is_open" value="0">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <span class="mt-1.5 inline-flex h-2.5 w-2.5 shrink-0 rounded-full {{ $settings->is_open ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">
                            {{ __('Public admissions page') }}:
                            <span class="{{ $settings->is_open ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $settings->is_open ? __('Open — accepting applications') : __('Closed — showing a notice') }}
                            </span>
                        </h2>
                        <p class="mt-1 text-xs text-slate-600">
                            {{ __('Toggle to immediately show or hide the application form on the public /admissions page.') }}
                        </p>
                    </div>
                </div>
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2">
                    <input type="checkbox" name="is_open" value="1" @checked($settings->is_open) class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-sm font-medium text-slate-800">{{ __('Accepting applications') }}</span>
                </label>
            </div>

            <details class="rounded-lg border border-slate-200 bg-slate-50/50 p-4" @if($settings->closed_message_en || $settings->closed_message_bn) open @endif>
                <summary class="cursor-pointer text-sm font-medium text-slate-700">{{ __('Custom "closed" notice (optional)') }}</summary>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">{{ __('Closed message') }} <span class="font-normal text-slate-400">(EN)</span></label>
                        <textarea name="closed_message_en" rows="2" class="admin-input">{{ old('closed_message_en', $settings->closed_message_en) }}</textarea>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">{{ __('Closed message') }} <span class="font-normal text-slate-400">(বাংলা)</span></label>
                        <textarea name="closed_message_bn" rows="2" class="admin-input">{{ old('closed_message_bn', $settings->closed_message_bn) }}</textarea>
                    </div>
                </div>
            </details>

            <div class="flex justify-end">
                <x-button type="submit">{{ __('Save changes') }}</x-button>
            </div>
        </form>
    </x-card>

    <x-admin-data-table
        :headers="[
            ['label' => __('Application')],
            ['label' => __('Applicant')],
            ['label' => __('Status')],
            ['label' => __('Test')],
            ['label' => __('Actions'), 'class' => 'text-right'],
        ]"
        :paginator="$rows"
        :empty-title="__('No admissions found')"
        :empty-message="__('Applications will appear here once submitted on the public site.')"
        empty-icon="document"
    >
        @foreach ($rows as $row)
            <tr class="admin-table-row">
                <td class="px-4 py-3.5">
                    <div class="font-mono text-xs font-medium text-slate-800">{{ $row->application_number }}</div>
                    <div class="text-xs text-slate-500">{{ $row->submitted_at?->format('Y-m-d') ?: '—' }}</div>
                </td>
                <td class="px-4 py-3.5">
                    <div class="font-medium text-slate-900">{{ $row->full_name }}</div>
                    <div class="text-xs text-slate-500">{{ $row->email }} · {{ $row->phone }}</div>
                </td>
                <td class="px-4 py-3.5">
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $row->status_badge }}">{{ $row->status_label }}</span>
                </td>
                <td class="px-4 py-3.5 text-slate-700">
                    @if($row->latestTest?->scheduled_at)
                        <div class="text-xs font-medium">{{ $row->latestTest->scheduled_at->format('Y-m-d H:i') }}</div>
                        <div class="text-xs text-slate-500">{{ $row->latestTest->venue ?: '—' }}</div>
                    @else
                        <span class="text-xs text-slate-500">{{ __('Not scheduled') }}</span>
                    @endif
                </td>
                <td class="px-4 py-3.5 text-right">
                    <x-button :href="route('dashboard.admissions.show', $row)" variant="ghost" size="sm">{{ __('View') }}</x-button>
                </td>
            </tr>
        @endforeach
    </x-admin-data-table>
@endsection
