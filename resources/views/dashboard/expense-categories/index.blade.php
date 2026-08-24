@extends('layouts.dashboard')

@section('title', __('Expense categories') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Expense categories')" :description="__('Group expenses into categories for budgeting and reporting.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Expense categories')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-button :href="route('dashboard.expense-categories.create')">{{ __('New category') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">{{ __('Name') }}</th>
                        <th class="px-4 py-3">{{ __('Description') }}</th>
                        <th class="px-4 py-3">{{ __('Color') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Expenses') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $row->name }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $row->description ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if($row->color)
                                    <span class="inline-block h-4 w-4 rounded-full" style="background-color: {{ $row->color }}"></span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($row->is_active)
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">{{ __('Active') }}</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ $row->expenses_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-button :href="route('dashboard.expense-categories.edit', $row)" variant="ghost" size="sm">{{ __('Edit') }}</x-button>
                                <form method="post" action="{{ route('dashboard.expense-categories.destroy', $row) }}" class="inline" onsubmit="return confirm('{{ __('Delete this category?') }}')">
                                    @csrf @method('delete')
                                    <button class="text-xs font-semibold text-red-700 hover:underline" type="submit">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No categories yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $rows->links() }}</div>
        @endif
    </x-card>
@endsection
