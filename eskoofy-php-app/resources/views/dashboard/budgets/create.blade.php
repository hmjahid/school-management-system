@extends('layouts.dashboard')

@section('title', __('New budget') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('New budget')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Budgets'), 'url' => route('dashboard.budgets.index')],
                ['label' => __('New')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <x-card>
        @include('dashboard.partials.form-errors')
        <form method="post" action="{{ route('dashboard.budgets.store') }}" class="space-y-5">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Category') }}</label>
                    <select name="expense_category_id" class="admin-select">
                        <option value="">{{ __('Uncategorized') }}</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Period type') }}</label>
                    <select name="period_type" class="admin-select">
                        <option value="monthly">{{ __('Monthly') }}</option>
                        <option value="yearly">{{ __('Yearly') }}</option>
                        <option value="custom">{{ __('Custom') }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Period start') }}</label>
                    <input type="date" name="period_start" required value="{{ old('period_start', now()->startOfMonth()->toDateString()) }}" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Period end') }}</label>
                    <input type="date" name="period_end" required value="{{ old('period_end', now()->endOfMonth()->toDateString()) }}" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Amount') }}</label>
                    <input type="number" name="amount" required step="0.01" min="0" class="admin-input">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Notes') }}</label>
                <textarea name="notes" rows="3" class="admin-input"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <x-button :href="route('dashboard.budgets.index')" variant="ghost">{{ __('Cancel') }}</x-button>
                <x-button type="submit">{{ __('Save') }}</x-button>
            </div>
        </form>
    </x-card>
@endsection
