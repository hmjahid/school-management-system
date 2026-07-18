@extends('layouts.dashboard')

@section('title', __('Transport routes') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Transport routes')" :description="__('Each route defines stops and a fare applied to assigned students.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Transport')],
                ['label' => __('Routes')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-button :href="route('dashboard.transport.assignments.index')" variant="ghost" size="sm">{{ __('Assignments') }}</x-button>
            <x-button :href="route('dashboard.transport.routes.create')">{{ __('New route') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card :padding="false">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">{{ __('Code') }}</th>
                    <th class="px-4 py-3">{{ __('Name') }}</th>
                    <th class="px-4 py-3">{{ __('Vehicle') }}</th>
                    <th class="px-4 py-3">{{ __('Stops') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Fare') }}</th>
                    <th class="px-4 py-3">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $r)
                    <tr>
                        <td class="px-4 py-3 font-mono font-semibold">{{ $r->code }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $r->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $r->vehicle?->number ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $r->stops->count() }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ number_format((float) $r->fare, 2) }}</td>
                        <td class="px-4 py-3">
                            @if($r->is_active)
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">{{ __('Active') }}</span>
                            @else
                                <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-semibold text-slate-700">{{ __('Inactive') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <x-button :href="route('dashboard.transport.routes.edit', $r)" variant="ghost" size="sm">{{ __('Edit') }}</x-button>
                            <form method="post" action="{{ route('dashboard.transport.routes.destroy', $r) }}" class="inline" onsubmit="return confirm('{{ __('Delete this route?') }}')">
                                @csrf @method('delete')
                                <button class="text-xs font-semibold text-red-700 hover:underline" type="submit">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No routes yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($rows->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $rows->links() }}</div>
        @endif
    </x-card>
@endsection