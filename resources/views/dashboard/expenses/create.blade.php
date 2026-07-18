@extends('layouts.dashboard')

@section('title', __('New expense') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('New expense')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Expenses'), 'url' => route('dashboard.expenses.index')],
                ['label' => __('New')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <x-card>
        @include('dashboard.partials.form-errors')
        <form method="post" action="{{ route('dashboard.expenses.store') }}" class="space-y-5">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Category') }}</label>
                    <input type="text" name="category" required maxlength="64" class="admin-input" placeholder="e.g. Utilities">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Amount') }}</label>
                    <input type="number" name="amount" required step="0.01" min="0.01" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Date') }}</label>
                    <input type="date" name="date" required value="{{ now()->toDateString() }}" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Vendor') }}</label>
                    <input type="text" name="vendor" maxlength="191" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Payment method') }}</label>
                    <select name="payment_method" class="admin-select">
                        <option value="cash">{{ __('Cash') }}</option>
                        <option value="bank">{{ __('Bank') }}</option>
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                        <option value="card">{{ __('Card') }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Expense account') }}</label>
                    <select name="chart_of_account_id" class="admin-select">
                        <option value="">{{ __('Default (Miscellaneous Expense)') }}</option>
                        @foreach($accounts as $a)
                            <option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name_en }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Note') }}</label>
                <textarea name="note" rows="3" class="admin-input"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <x-button :href="route('dashboard.expenses.index')" variant="ghost">{{ __('Cancel') }}</x-button>
                <x-button type="submit">{{ __('Save') }}</x-button>
            </div>
        </form>
    </x-card>
@endsection