@extends('layouts.dashboard')

@section('title', __('Edit route') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Edit route') . ' — ' . $route->name">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Transport'), 'url' => route('dashboard.transport.routes.index')],
                ['label' => $route->code],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <form method="post" action="{{ route('dashboard.transport.routes.update', $route) }}" class="space-y-6">
        @csrf @method('put')

        <x-card :title="__('Route details')">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Code') }}</label>
                    <input type="text" name="code" required maxlength="32" value="{{ old('code', $route->code) }}" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Name') }}</label>
                    <input type="text" name="name" required maxlength="191" value="{{ old('name', $route->name) }}" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Vehicle') }}</label>
                    <select name="vehicle_id" class="admin-select">
                        <option value="">—</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" @selected(old('vehicle_id', $route->vehicle_id) == $v->id)>{{ $v->number }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Fare') }}</label>
                    <input type="number" step="0.01" min="0" name="fare" required value="{{ old('fare', $route->fare) }}" class="admin-input">
                </div>
                <label class="inline-flex items-center gap-2 sm:col-span-2">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $route->is_active)) class="h-4 w-4 rounded border-slate-300 text-brand-600">
                    <span class="text-sm text-slate-700">{{ __('Active') }}</span>
                </label>
            </div>
        </x-card>

        <x-card :title="__('Stops')" :padding="false">
            <div x-data="{ stops: {{ json_encode(old('stops', $route->stops->map(fn($s) => ['id'=>$s->id,'name'=>$s->name,'pickup_time'=>$s->pickup_time?->format('H:i'),'drop_time'=>$s->drop_time?->format('H:i'),'sort'=>$s->sort])->all())) }} }">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-3 py-2">{{ __('Order') }}</th>
                            <th class="px-3 py-2">{{ __('Stop name') }}</th>
                            <th class="px-3 py-2">{{ __('Pickup') }}</th>
                            <th class="px-3 py-2">{{ __('Drop') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(stop, idx) in stops" :key="idx">
                            <tr>
                                <td class="px-3 py-2">
                                    <input type="number" :name="`stops[${idx}][sort]`" x-model="stop.sort" class="admin-input w-16">
                                    <input type="hidden" :name="`stops[${idx}][id]`" x-model="stop.id">
                                </td>
                                <td class="px-3 py-2"><input type="text" :name="`stops[${idx}][name]`" x-model="stop.name" required class="admin-input"></td>
                                <td class="px-3 py-2"><input type="time" :name="`stops[${idx}][pickup_time]`" x-model="stop.pickup_time" class="admin-input"></td>
                                <td class="px-3 py-2"><input type="time" :name="`stops[${idx}][drop_time]`" x-model="stop.drop_time" class="admin-input"></td>
                                <td class="px-3 py-2 text-right">
                                    <button type="button" class="text-xs font-semibold text-red-700 hover:underline" @click="stops.splice(idx, 1)">{{ __('Remove') }}</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="border-t border-slate-200 px-4 py-3">
                    <button type="button" class="text-sm font-semibold text-brand-700 hover:underline" @click="stops.push({id:null,name:'',pickup_time:'',drop_time:'',sort:stops.length})">+ {{ __('Add stop') }}</button>
                </div>
            </div>
        </x-card>

        <div class="flex justify-end gap-2">
            <x-button :href="route('dashboard.transport.routes.index')" variant="ghost">{{ __('Cancel') }}</x-button>
            <x-button type="submit">{{ __('Update route') }}</x-button>
        </div>
    </form>
@endsection