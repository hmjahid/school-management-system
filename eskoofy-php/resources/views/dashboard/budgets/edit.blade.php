@extends('layouts.dashboard')

@section('title', __('Edit budget') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Edit budget')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Budgets'), 'url' => route('dashboard.budgets.index')],
                ['label' => __('Edit')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <x-card>
        @include('dashboard.partials.form-errors')
        <form method="post" action="{{ route('dashboard.budgets.update', $budget) }}" class="space-y-5">
            @csrf @method('put')
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Category') }}</label>
                    <select name="expense_category_id" class="admin-select">
                        <option value="">{{ __('Uncategorized') }}</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('expense_category_id', $budget->expense_category_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Period type') }}</label>
                    <select name="period_type" class="admin-select">
                        @foreach(['monthly','yearly','custom'] as $pt)
                            <option value="{{ $pt }}" @selected(old('period_type', $budget->period_type) === $pt)>{{ ucfirst($pt) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Period start') }}</label>
                    <input type="date" name="period_start" required value="{{ old('period_start', $budget->period_start?->toDateString()) }}" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Period end') }}</label>
                    <input type="date" name="period_end" required value="{{ old('period_end', $budget->period_end?->toDateString()) }}" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Amount') }}</label>
                    <input type="number" name="amount" required step="0.01" min="0" value="{{ old('amount', $budget->amount) }}" class="admin-input">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Notes') }}</label>
                <textarea name="notes" rows="3" class="admin-input">{{ old('notes', $budget->notes) }}</textarea>
            </div>
            <div class="flex justify-end gap-2">
                <x-button :href="route('dashboard.budgets.index')" variant="ghost">{{ __('Cancel') }}</x-button>
                <x-button type="submit">{{ __('Update') }}</x-button>
            </div>
        </form>
    </x-card>
@endsection
