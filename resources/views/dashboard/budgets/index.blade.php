@extends('layouts.dashboard')

@section('title', __('Budgets') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Budgets')" :description="__('Set spending limits per category and period.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Budgets')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-button :href="route('dashboard.budgets.create')">{{ __('New budget') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">{{ __('Category') }}</th>
                        <th class="px-4 py-3">{{ __('Period') }}</th>
                        <th class="px-4 py-3">{{ __('Range') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Amount') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $row->category?->name ?? __('Uncategorized') }}</td>
                            <td class="px-4 py-3 capitalize text-slate-700">{{ $row->period_type }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $row->period_start?->format('Y-m-d') }} → {{ $row->period_end?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format((float) $row->amount, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-button :href="route('dashboard.budgets.edit', $row)" variant="ghost" size="sm">{{ __('Edit') }}</x-button>
                                <form method="post" action="{{ route('dashboard.budgets.destroy', $row) }}" class="inline" onsubmit="return confirm('{{ __('Delete this budget?') }}')">
                                    @csrf @method('delete')
                                    <button class="text-xs font-semibold text-red-700 hover:underline" type="submit">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No budgets yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $rows->links() }}</div>
        @endif
    </x-card>
@endsection
