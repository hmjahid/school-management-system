@extends('layouts.dashboard')

@section('title', __('Salary structures') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Salary structures')" :description="__('Define basic pay, allowances and deductions per teacher.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Payroll')],
                ['label' => __('Structures')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-button :href="route('dashboard.payroll.payslips')" variant="ghost" size="sm">{{ __('Payslips') }}</x-button>
            <x-button :href="route('dashboard.payroll.generate')">{{ __('Generate payslips') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2" :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3">{{ __('Teacher') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Basic') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Allowances') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Deductions') }}</th>
                            <th class="px-4 py-3">{{ __('Effective from') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rows as $r)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $r->teacher?->user?->name }}</td>
                                <td class="px-4 py-3 text-right font-mono">{{ number_format((float) $r->basic, 2) }}</td>
                                <td class="px-4 py-3 text-right font-mono">{{ number_format($r->totalAllowances(), 2) }}</td>
                                <td class="px-4 py-3 text-right font-mono">{{ number_format($r->totalDeductions(), 2) }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $r->effective_from->format('Y-m-d') }}</td>
                                <td class="px-4 py-3">
                                    @if($r->is_active)
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">{{ __('Active') }}</span>
                                    @else
                                        <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-semibold text-slate-700">{{ __('Archived') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No salary structures yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($rows->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">{{ $rows->links() }}</div>
            @endif
        </x-card>

        <x-card :title="__('New structure')">
            <form method="post" action="{{ route('dashboard.payroll.structures.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Teacher') }}</label>
                    <select name="teacher_id" required class="admin-select">
                        <option value="">—</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}">{{ $t->user?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Basic salary') }}</label>
                    <input type="number" step="0.01" min="0" name="basic" required class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Allowances') }}</label>
                    <input type="text" name="allowances" placeholder="House:2000;Medical:1000" class="admin-input">
                    <p class="mt-1 text-xs text-slate-500">{{ __('Format: name:amount;name:amount') }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Deductions') }}</label>
                    <input type="text" name="deductions" placeholder="Tax:500;PF:1000" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Effective from') }}</label>
                    <input type="date" name="effective_from" required value="{{ now()->toDateString() }}" class="admin-input">
                </div>
                <x-button type="submit" class="w-full">{{ __('Save structure') }}</x-button>
            </form>
        </x-card>
    </div>
@endsection