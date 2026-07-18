@extends('layouts.dashboard')

@section('title', __('New route') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('New route')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Transport'), 'url' => route('dashboard.transport.routes.index')],
                ['label' => __('New')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <x-card>
        <form method="post" action="{{ route('dashboard.transport.routes.store') }}" class="space-y-5">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Code') }}</label>
                    <input type="text" name="code" required maxlength="32" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Name') }}</label>
                    <input type="text" name="name" required maxlength="191" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Vehicle') }}</label>
                    <select name="vehicle_id" class="admin-select">
                        <option value="">—</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}">{{ $v->number }} {{ $v->type ? '(' . $v->type . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Fare') }}</label>
                    <input type="number" step="0.01" min="0" name="fare" required value="0" class="admin-input">
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <x-button :href="route('dashboard.transport.routes.index')" variant="ghost">{{ __('Cancel') }}</x-button>
                <x-button type="submit">{{ __('Save') }}</x-button>
            </div>
        </form>
    </x-card>
@endsection