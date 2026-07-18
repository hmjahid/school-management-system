@extends('layouts.dashboard')

@section('title', __('Activity log') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Activity log')" :description="__('Audit trail of model changes and admin actions.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Activity')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <form method="get" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-4">
        <select name="log_name" class="admin-select">
            <option value="">{{ __('All logs') }}</option>
            @foreach($logNames as $ln)
                <option value="{{ $ln }}" @selected(request('log_name') === $ln)>{{ $ln }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="admin-input">
        <input type="date" name="to" value="{{ request('to') }}" class="admin-input">
        <x-button type="submit" variant="secondary">{{ __('Filter') }}</x-button>
    </form>

    <x-card :padding="false">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">{{ __('When') }}</th>
                    <th class="px-4 py-3">{{ __('Log') }}</th>
                    <th class="px-4 py-3">{{ __('Causer') }}</th>
                    <th class="px-4 py-3">{{ __('Subject') }}</th>
                    <th class="px-4 py-3">{{ __('Description') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $a)
                    <tr>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $a->created_at->format('Y-m-d H:i:s') }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $a->log_name ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $a->causer?->name ?: __('System') }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            {{ $a->subject_type ? class_basename($a->subject_type).' #'.$a->subject_id : '—' }}
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $a->description ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No activity recorded yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($rows->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $rows->links() }}</div>
        @endif
    </x-card>
@endsection