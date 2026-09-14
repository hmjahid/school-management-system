@extends('layouts.dashboard')

@section('title', __('Careers') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Careers')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Careers')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <x-card :title="__('Open positions')" :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">{{ __('Title') }}</th>
                        <th class="px-4 py-3">{{ __('Type') }}</th>
                        <th class="px-4 py-3">{{ __('Location') }}</th>
                        <th class="px-4 py-3">{{ __('Deadline') }}</th>
                        <th class="px-4 py-3">{{ __('Salary') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Applications') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($jobs ?? [] as $job)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-900">
                                <a href="{{ '/dashboard/careers/' . $job['id'] }}" class="text-brand-700 hover:underline">{{ $job['title'] }}</a>
                            </td>
                            <td class="px-4 py-3 capitalize">{{ $job['type'] }}</td>
                            <td class="px-4 py-3">{{ $job['location'] }}</td>
                            <td class="px-4 py-3">{{ date('d M Y', strtotime($job['deadline'])) }}</td>
                            <td class="px-4 py-3">
                                @if ($job['salary_min'] || $job['salary_max'])
                                    {{ number_format((float) ($job['salary_min'] ?? 0)) }}–{{ number_format((float) $job['salary_max']) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ((int) ($job['is_published'] ?? 0) === 1)
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">{{ __('Published') }}</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ __('Draft') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ '/dashboard/careers/' . $job['id'] }}" class="text-sm font-semibold text-brand-700 hover:underline">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-400">{{ __('No job openings yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
@endsection