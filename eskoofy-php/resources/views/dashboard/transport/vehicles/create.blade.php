@extends('layouts.dashboard')

@section('title', __('New vehicle') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('New vehicle')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Transport'), 'url' => route('dashboard.transport.vehicles.index')],
                ['label' => __('New')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <x-card>
        <form method="post" action="{{ route('dashboard.transport.vehicles.store') }}" class="space-y-5">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Vehicle number') }}</label>
                    <input type="text" name="number" required maxlength="64" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Type') }}</label>
                    <input type="text" name="type" maxlength="64" placeholder="Bus / Van / Mini" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Capacity') }}</label>
                    <input type="number" name="capacity" min="0" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Driver name') }}</label>
                    <input type="text" name="driver_name" maxlength="191" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Driver phone') }}</label>
                    <input type="text" name="driver_phone" maxlength="32" class="admin-input">
                </div>
                <label class="inline-flex items-center gap-2 self-end">
                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-slate-300 text-brand-600">
                    <span class="text-sm text-slate-700">{{ __('Active') }}</span>
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <x-button :href="route('dashboard.transport.vehicles.index')" variant="ghost">{{ __('Cancel') }}</x-button>
                <x-button type="submit">{{ __('Save') }}</x-button>
            </div>
        </form>
    </x-card>
@endsection