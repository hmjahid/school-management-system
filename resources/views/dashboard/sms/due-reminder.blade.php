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
        @php $total = $recipientCount ?? 0; @endphp
        <div class="mb-4 grid gap-4 sm:grid-cols-3">
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">{{ __('Recipients with outstanding dues') }}</div>
                <div class="mt-1 text-3xl font-semibold text-slate-900">{{ $total }}</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">{{ __('Total outstanding') }}</div>
                <div class="mt-1 text-3xl font-semibold text-slate-900">{{ number_format($totalDue ?? 0, 2) }}</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">{{ __('SMS count (160 chars each)') }}</div>
                <div class="mt-1 text-3xl font-semibold text-slate-900" id="sms-count-display">—</div>
            </div>
            @if($total === 0)
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-800 sm:col-span-3">{{ __('No dues') }}</span>
            @endif
        </div>

        @if($total > 0)
            <form method="post" action="{{ route('dashboard.sms.due-reminder.send') }}">
                @csrf
                <div class="mb-4">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Message') }}</label>
                    <textarea name="message" id="due-message" required rows="5" maxlength="1000" class="admin-input">{{ $defaultMessage }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Standard SMS is 160 chars; longer messages will be split. Use {{amount}} as a placeholder for the due amount.') }}</p>
                </div>

                <div class="mb-5 rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs font-semibold text-slate-600">{{ __('Sample message') }}</div>
                    <p class="mt-1 text-sm text-slate-700" id="message-sample">
                        {{ \Illuminate\Support\Str::limit(str_replace('{{amount}}', number_format($recipients->first()['due'] ?? 0, 2), $defaultMessage), 200) }}
                    </p>
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
            <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">
                {{ __('Recipient preview (first :n)', ['n' => $recipients->count()]) }}
                @if(($recipientCount ?? 0) > $recipients->count())
                    <span class="font-normal text-slate-500">· {{ __(':n more will be included', ['n' => ($recipientCount ?? 0) - $recipients->count()]) }}</span>
                @endif
            </div>
            <div class="overflow-x-auto">
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
            </div>
        </x-card>
    @endif

    <script>
        var msgInput = document.getElementById('due-message');
        var countDisplay = document.getElementById('sms-count-display');
        var sample = document.getElementById('message-sample');
        var firstDue = {{ number_format($recipients->first()['due'] ?? 0, 2) }};
        var totalRecipients = {{ $recipientCount ?? 0 }};

        function updateSmsStats() {
            if (!msgInput) return;
            var len = msgInput.value.length;
            var segments = Math.max(1, Math.ceil(len / 160));
            if (countDisplay) countDisplay.textContent = (segments * totalRecipients).toLocaleString() + ' (' + segments + ' per recipient)';
            if (sample) {
                sample.textContent = msgInput.value.replaceAll('{{amount}}', firstDue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            }
        }
        if (msgInput) {
            msgInput.addEventListener('input', updateSmsStats);
            updateSmsStats();
        }
    </script>
@endsection