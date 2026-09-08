@extends('layouts.dashboard')

@section('title', __('Journal entry') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('New journal entry')" :description="__('Manually post a double-entry transaction. Debits must equal credits.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Ledger'), 'url' => route('dashboard.ledger.index')],
                ['label' => __('Journal entry')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <x-card>
        @include('dashboard.partials.form-errors')
        <form method="post" action="{{ route('dashboard.ledger.journal.store') }}" class="space-y-5" x-data="{ lines: [{account_id:'', debit:'', credit:''}, {account_id:'', debit:'', credit:''}] }">
            @csrf
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Date') }}</label>
                    <input type="date" name="date" required value="{{ now()->toDateString() }}" class="admin-input">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Note') }}</label>
                    <input type="text" name="note" maxlength="500" class="admin-input">
                </div>
            </div>

            <div class="rounded-lg border border-slate-200">
                <div class="grid grid-cols-12 gap-2 border-b border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <div class="col-span-6">{{ __('Account') }}</div>
                    <div class="col-span-3 text-right">{{ __('Debit') }}</div>
                    <div class="col-span-3 text-right">{{ __('Credit') }}</div>
                </div>
                <template x-for="(line, idx) in lines" :key="idx">
                    <div class="grid grid-cols-12 gap-2 border-b border-slate-100 px-3 py-2">
                        <div class="col-span-6">
                            <select :name="`lines[${idx}][account_id]`" x-model="line.account_id" class="admin-select" required>
                                <option value="">—</option>
                                @foreach($accounts as $a)
                                    <option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name_en }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-3">
                            <input type="number" step="0.01" min="0" :name="`lines[${idx}][debit]`" x-model="line.debit" class="admin-input text-right" placeholder="0.00">
                        </div>
                        <div class="col-span-3">
                            <input type="number" step="0.01" min="0" :name="`lines[${idx}][credit]`" x-model="line.credit" class="admin-input text-right" placeholder="0.00">
                        </div>
                    </div>
                </template>
                <div class="flex items-center justify-between px-3 py-2">
                    <button type="button" class="text-xs font-semibold text-slate-600 hover:text-slate-900" @click="lines.push({account_id:'',debit:'',credit:''})">+ {{ __('Add line') }}</button>
                    <button type="button" class="text-xs font-semibold text-red-600 hover:text-red-800" x-show="lines.length > 2" @click="lines.pop()">{{ __('Remove last') }}</button>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <x-button :href="route('dashboard.ledger.index')" variant="ghost">{{ __('Cancel') }}</x-button>
                <x-button type="submit">{{ __('Post entry') }}</x-button>
            </div>
        </form>
    </x-card>
@endsection