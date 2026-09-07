@extends('layouts.dashboard')

@section('title', __('Fees') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    @php
        $u = auth()->user();
        $canManageFees = $u && ($u->hasAnyRole(['admin', 'accountant']) || $u->hasAnyPermission(['collect_fees', 'view_financial_reports', 'manage_fee_categories', 'manage_fee_types']));
        $statusVariants = [
            'active' => 'success',
            'inactive' => 'default',
            'archived' => 'warning',
        ];
        $hasFilters = request()->filled('search')
            || request()->filled('class_id')
            || request()->filled('section_id')
            || request()->filled('status')
            || request()->filled('fee_type');
    @endphp

    <x-page-header :title="__('Fees')" :description="__('Fee definitions used to generate student invoices.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Fees')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            @if ($canManageFees)
                <x-button :href="route('dashboard.fees.create')">{{ __('Add fee') }}</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <form method="get" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800 md:grid-cols-2 xl:grid-cols-6">
        <div class="xl:col-span-2">
            <label for="filter-search" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('Search') }}</label>
            <input id="filter-search" type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search by name or code...') }}"
                class="admin-input">
        </div>
        <div>
            <label for="filter-class" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('Class') }}</label>
            <select id="filter-class" name="class_id" class="admin-select">
                <option value="">{{ __('All classes') }}</option>
                @foreach($classes ?? [] as $class)
                    <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-section" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('Section') }}</label>
            <select id="filter-section" name="section_id" class="admin-select">
                <option value="">{{ __('All sections') }}</option>
                @foreach($sections ?? [] as $section)
                    <option value="{{ $section->id }}" @selected(request('section_id') == $section->id)>{{ $section->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-status" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('Status') }}</label>
            <select id="filter-status" name="status" class="admin-select">
                <option value="">{{ __('All statuses') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
                <option value="archived" @selected(request('status') === 'archived')>{{ __('Archived') }}</option>
            </select>
        </div>
        <div>
            <label for="filter-fee-type" class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('Type') }}</label>
            <select id="filter-fee-type" name="fee_type" class="admin-select">
                <option value="">{{ __('All types') }}</option>
                @foreach(\App\Models\Fee::getFeeTypes() as $typeValue => $typeLabel)
                    <option value="{{ $typeValue }}" @selected(request('fee_type') === $typeValue)>{{ __($typeLabel) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2 md:col-span-2 xl:col-span-6">
            <x-button type="submit" size="sm">{{ __('Filter') }}</x-button>
            @if ($hasFilters)
                <x-button :href="route('dashboard.fees')" variant="secondary" size="sm">{{ __('Reset') }}</x-button>
            @endif
        </div>
    </form>

    <x-admin-data-table
        :headers="[
            ['label' => __('Name')],
            ['label' => __('Type')],
            ['label' => __('Amount'), 'class' => 'text-right'],
            ['label' => __('Class')],
            ['label' => __('Section')],
            ['label' => __('Status')],
            ['label' => __('Actions'), 'class' => 'text-right'],
        ]"
        :paginator="$fees"
        empty-icon="tag"
        :empty-title="__('No fee definitions found.')"
        :empty-message="__('Try adjusting your filters, or add a fee to get started.')"
    >
        @forelse ($fees as $fee)
            <tr class="admin-table-row">
                <td class="px-4 py-3">
                    <div class="font-medium text-slate-900 dark:text-slate-100">{{ $fee->name }}</div>
                    @if($fee->code)
                        <div class="font-mono text-xs text-slate-400 dark:text-slate-500">{{ $fee->code }}</div>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <x-badge variant="info">{{ __(ucfirst($fee->fee_type ?? 'other')) }}</x-badge>
                </td>
                <td class="px-4 py-3 text-right text-slate-900 dark:text-slate-100">{{ number_format((float) ($fee->amount ?? 0), 2) }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $fee->schoolClass?->name ?? '—' }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $fee->section?->name ?? '—' }}</td>
                <td class="px-4 py-3">
                    <x-badge :variant="$statusVariants[$fee->status ?? 'active'] ?? 'default'">{{ __(ucfirst($fee->status ?? 'active')) }}</x-badge>
                </td>
                <td class="px-4 py-3 text-right">
                    @if ($canManageFees)
                        <div class="flex items-center justify-end gap-2">
                            <x-button :href="route('dashboard.fees.edit', $fee)" variant="ghost" size="sm">{{ __('Edit') }}</x-button>
                            <form method="post" action="{{ route('dashboard.fees.destroy', $fee) }}" class="inline" data-confirm="{{ __('Delete this fee definition?') }}" data-confirm-title="{{ __('Delete fee') }}">
                                @csrf @method('delete')
                                <x-button type="submit" variant="ghost" size="sm" class="text-red-600 hover:text-red-800 dark:text-red-400">{{ __('Delete') }}</x-button>
                            </form>
                        </div>
                    @endif
                </td>
            </tr>
        @empty
        @endforelse
    </x-admin-data-table>
@endsection