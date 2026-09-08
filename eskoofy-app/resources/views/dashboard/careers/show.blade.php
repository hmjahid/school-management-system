@extends('layouts.dashboard')

@section('title', $application->name . ' — ' . __('Application') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="$application->name" :description="__('Job application for :position', ['position' => $application->career?->title ?? '—'])">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Career Applications'), 'url' => route('dashboard.careers.index')],
                ['label' => $application->name],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Application details --}}
        <div class="lg:col-span-2 space-y-6">
            <x-card :title="__('Applicant Information')">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">{{ __('Name') }}</label>
                        <p class="text-sm text-slate-900">{{ $application->name }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">{{ __('Email') }}</label>
                        <p class="text-sm text-slate-900">{{ $application->email }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">{{ __('Phone') }}</label>
                        <p class="text-sm text-slate-900">{{ $application->phone }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">{{ __('Position') }}</label>
                        <p class="text-sm text-slate-900">{{ $application->career?->title ?? '—' }}</p>
                    </div>
                </div>
            </x-card>

            @if($application->cover_letter)
                <x-card :title="__('Cover Letter')">
                    <p class="whitespace-pre-wrap text-sm text-slate-700">{{ $application->cover_letter }}</p>
                </x-card>
            @endif

            @if($application->resume_path)
                <x-card :title="__('Resume')">
                    <a href="{{ Storage::disk('public')->url($application->resume_path) }}" target="_blank" class="inline-flex items-center gap-2 text-sm font-medium text-brand-600 hover:text-brand-700">
                        <x-icon name="document" class="h-4 w-4" />
                        {{ __('Download resume') }}
                    </a>
                </x-card>
            @endif
        </div>

        {{-- Sidebar: status + actions --}}
        <div class="space-y-6">
            <x-card :title="__('Status')">
                @php
                    $statusVariants = [
                        'pending' => 'warning',
                        'reviewed' => 'info',
                        'shortlisted' => 'brand',
                        'rejected' => 'danger',
                        'hired' => 'success',
                    ];
                @endphp
                <div class="mb-4">
                    <x-badge :variant="$statusVariants[$application->status] ?? 'default'" class="text-sm">{{ ucfirst($application->status) }}</x-badge>
                </div>

                <form method="post" action="{{ route('dashboard.careers.status', $application) }}" class="space-y-3">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="admin-select w-full">
                        @foreach (['pending','reviewed','shortlisted','rejected','hired'] as $s)
                            <option value="{{ $s }}" @selected($application->status === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    <x-button type="submit" variant="primary" size="sm" class="w-full">{{ __('Update status') }}</x-button>
                </form>
            </x-card>

            <x-card>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-600">{{ __('Applied') }}</dt>
                        <dd class="text-slate-900">{{ $application->created_at->format('Y-m-d H:i') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-600">{{ __('ID') }}</dt>
                        <dd class="font-mono text-xs text-slate-900">#{{ $application->id }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card>
                <form method="post" action="{{ route('dashboard.careers.destroy', $application) }}" onsubmit="return confirm('{{ __('Delete this application?') }}')">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" size="sm" class="w-full">{{ __('Delete application') }}</x-button>
                </form>
            </x-card>
        </div>
    </div>
@endsection
