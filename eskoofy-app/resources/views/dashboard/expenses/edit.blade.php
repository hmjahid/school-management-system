@extends('layouts.dashboard')

@section('title', __('Edit expense') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Edit expense')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Expenses'), 'url' => route('dashboard.expenses.index')],
                ['label' => __('Edit')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <x-card>
        @include('dashboard.partials.form-errors')
        <form method="post" action="{{ route('dashboard.expenses.update', $expense) }}" class="space-y-5">
            @csrf @method('put')
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Category') }}</label>
                    <select name="expense_category_id" class="admin-select">
                        <option value="">{{ __('Uncategorized') }}</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('expense_category_id', $expense->expense_category_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Amount') }}</label>
                    <input type="number" name="amount" required step="0.01" min="0.01" value="{{ old('amount', $expense->amount) }}" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Date') }}</label>
                    <input type="date" name="date" required value="{{ old('date', $expense->date?->toDateString()) }}" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Vendor') }}</label>
                    <input type="text" name="vendor" maxlength="191" value="{{ old('vendor', $expense->vendor) }}" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Payment method') }}</label>
                    <select name="payment_method" class="admin-select">
                        @foreach(['cash','bank','bkash','nagad','card'] as $m)
                            <option value="{{ $m }}" @selected(old('payment_method', $expense->payment_method) === $m)>{{ ucfirst($m) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Expense account') }}</label>
                    <select name="chart_of_account_id" class="admin-select">
                        <option value="">{{ __('Default') }}</option>
                        @foreach($accounts as $a)
                            <option value="{{ $a->id }}" @selected(old('chart_of_account_id', $expense->chart_of_account_id) == $a->id)>{{ $a->code }} — {{ $a->name_en }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Note') }}</label>
                <textarea name="note" rows="3" class="admin-input">{{ old('note', $expense->note) }}</textarea>
            </div>
            <div class="flex justify-end gap-2">
                <x-button :href="route('dashboard.expenses.index')" variant="ghost">{{ __('Cancel') }}</x-button>
                <x-button type="submit">{{ __('Update') }}</x-button>
            </div>
        </form>
    </x-card>
@endsection