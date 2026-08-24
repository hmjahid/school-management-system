@extends('layouts.dashboard')

@section('title', __('Due Fee Reminder') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Due Fee Reminder')" :description="__('Notify students with outstanding fee balances via SMS.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Bulk SMS'), 'url' => route('dashboard.sms.index')],
                ['label' => __('Due Fee Reminder')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <x-card>
        <div class="mb-4 flex items-center justify-between">
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">{{ __('Recipients with outstanding dues') }}</div>
                <div class="mt-1 text-3xl font-semibold text-slate-900">{{ $total }}</div>
            </div>
            @if($total === 0)
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-800">{{ __('No dues') }}</span>
            @endif
        </div>

        @if($total > 0)
            <form method="post" action="{{ route('dashboard.sms.due-reminder.send') }}">
                @csrf
                <div class="mb-4">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Message') }}</label>
                    <textarea name="message" required rows="5" maxlength="1000" class="admin-input">{{ $defaultMessage }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Standard SMS is 160 chars; longer messages will be split. Use {{amount}} as a placeholder for the due amount.') }}</p>
                </div>

                <div class="flex justify-end gap-2">
                    <x-button :href="route('dashboard.sms.index')" variant="ghost">{{ __('Cancel') }}</x-button>
                    <x-button type="submit">{{ __('Send now') }}</x-button>
                </div>
            </form>
        @endif
    </x-card>

    @if($recipients->isNotEmpty())
        <x-card class="mt-4" :padding="false">
            <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">{{ __('Preview (first 50)') }}</div>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">{{ __('Student') }}</th>
                        <th class="px-4 py-3">{{ __('Phone') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Outstanding') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($recipients as $r)
                        <tr>
                            <td class="px-4 py-3 text-slate-700">{{ $r['name'] }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $r['phone'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-700">{{ number_format($r['due'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-card>
    @endif
@endsection
