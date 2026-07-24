@extends('layouts.dashboard')

@section('title', __('Notification preferences') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Notification preferences')" :description="__('Choose how and when you want to be notified.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Notifications'), 'url' => route('dashboard.notifications.index')],
                ['label' => __('Preferences')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <form method="post" action="{{ route('dashboard.notifications.preferences.update') }}">
        @csrf
        <x-card :padding="false">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">{{ __('Event') }}</th>
                        @foreach($channels as $ch)
                            <th class="px-4 py-3 text-center">{{ ucfirst(str_replace('_',' ', $ch)) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($types as $type)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ ucfirst(str_replace('_',' ', $type)) }}</td>
                            @foreach($channels as $ch)
                                <td class="px-4 py-3 text-center">
                                    <input type="hidden" name="preferences[{{ $type }}][{{ $ch }}]" value="0">
                                    <input type="checkbox" name="preferences[{{ $type }}][{{ $ch }}]" value="1" @checked($preferences[$type][$ch] ?? true) class="h-4 w-4 rounded border-slate-300 text-brand-600">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="flex justify-end border-t border-slate-200 px-5 py-4">
                <x-button type="submit">{{ __('Save preferences') }}</x-button>
            </div>
        </x-card>
    </form>
@endsection