@extends('layouts.dashboard')

@section('title', __('Career Applications') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Career Applications')" :description="__('Review job applications and manage hiring workflow.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Career Applications')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <form method="get" class="flex flex-wrap items-center gap-2">
                <input name="search" value="{{ request('search') }}" placeholder="{{ __('Search…') }}" class="admin-input w-48">
                <select name="status" class="admin-select" onchange="this.form.submit()">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (['pending','reviewed','shortlisted','rejected','hired'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <select name="career_id" class="admin-select" onchange="this.form.submit()">
                    <option value="">{{ __('All positions') }}</option>
                    @foreach ($careers as $c)
                        <option value="{{ $c->id }}" @selected(request('career_id') == $c->id)>{{ $c->title }}</option>
                    @endforeach
                </select>
                <x-button type="submit" variant="secondary" size="sm">{{ __('Filter') }}</x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <x-admin-data-table
        :headers="[
            ['label' => __('Applicant')],
            ['label' => __('Position')],
            ['label' => __('Contact')],
            ['label' => __('Status')],
            ['label' => __('Applied')],
            ['label' => __('Actions'), 'class' => 'text-right'],
        ]"
        :paginator="$applications"
        :empty-title="__('No applications found')"
        :empty-message="__('Job applications will appear here once candidates apply.')"
        empty-icon="users"
    >
        @foreach ($applications as $app)
            @php
                $statusVariants = [
                    'pending' => 'warning',
                    'reviewed' => 'info',
                    'shortlisted' => 'brand',
                    'rejected' => 'danger',
                    'hired' => 'success',
                ];
            @endphp
            <tr class="admin-table-row">
                <td class="px-4 py-3.5">
                    <div class="font-medium text-slate-900">{{ $app->name }}</div>
                    <div class="text-xs text-slate-500">{{ $app->email }}</div>
                </td>
                <td class="px-4 py-3.5">
                    <div class="text-sm text-slate-800">{{ $app->career?->title ?? '—' }}</div>
                </td>
                <td class="px-4 py-3.5">
                    <div class="text-sm text-slate-700">{{ $app->phone }}</div>
                </td>
                <td class="px-4 py-3.5">
                    <x-badge :variant="$statusVariants[$app->status] ?? 'default'">{{ ucfirst($app->status) }}</x-badge>
                </td>
                <td class="px-4 py-3.5 text-sm text-slate-600">
                    {{ $app->created_at->format('Y-m-d') }}
                </td>
                <td class="px-4 py-3.5 text-right">
                    <x-button :href="route('dashboard.careers.show', $app)" variant="ghost" size="sm">{{ __('View') }}</x-button>
                </td>
            </tr>
        @endforeach
    </x-admin-data-table>
@endsection
