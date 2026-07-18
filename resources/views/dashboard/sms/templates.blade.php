@extends('layouts.dashboard')

@section('title', __('SMS templates') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('SMS templates')" :description="__('Reusable message templates.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Bulk SMS'), 'url' => route('dashboard.sms.index')],
                ['label' => __('Templates')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <x-card :padding="false">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">{{ __('Name') }}</th>
                    <th class="px-4 py-3">{{ __('Subject') }}</th>
                    <th class="px-4 py-3">{{ __('Body') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($templates as $t)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $t->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $t->subject }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ \Illuminate\Support\Str::limit($t->body, 80) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No SMS templates configured yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
@endsection