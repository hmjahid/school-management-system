@extends('layouts.dashboard')

@section('title', __('Careers') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Careers')" :description="__('Manage job postings and review applications.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Careers')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-button :href="route('dashboard.careers.create')" size="sm">+ {{ __('New position') }}</x-button>
            <x-button :href="route('dashboard.careers.applications')" variant="secondary" size="sm">{{ __('Applications') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">{{ __('Title') }}</th>
                        <th class="px-4 py-3">{{ __('Type') }}</th>
                        <th class="px-4 py-3">{{ __('Location') }}</th>
                        <th class="px-4 py-3">{{ __('Deadline') }}</th>
                        <th class="px-4 py-3">{{ __('Salary') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Applications') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($jobs as $job)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $job->title }}</td>
                            <td class="px-4 py-3 capitalize">{{ $job->type }}</td>
                            <td class="px-4 py-3">{{ $job->location }}</td>
                            <td class="px-4 py-3">{{ $job->deadline?->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                @if ($job->salary_min || $job->salary_max)
                                    {{ number_format((float) $job->salary_min ?? 0) }}–{{ number_format((float) $job->salary_max) }}
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('dashboard.careers.applications', ['career_id' => $job->id]) }}" class="text-brand-700 hover:underline">{{ $job->applications_count }}</a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $job->is_published ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' }}">{{ $job->is_published ? __('Published') : __('Draft') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('dashboard.careers.edit', $job) }}" class="text-xs font-semibold text-brand-700 hover:underline">{{ __('Edit') }}</a>
                                    <form method="post" action="{{ route('dashboard.careers.destroy', $job) }}" onsubmit="return confirm('{{ __('Delete this position?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs font-semibold text-red-600 hover:underline">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400">{{ __('No positions yet — add your first job posting.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
@endsection