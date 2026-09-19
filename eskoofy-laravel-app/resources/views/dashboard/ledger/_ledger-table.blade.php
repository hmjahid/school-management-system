<form method="get" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-3">
    <input type="date" name="from" value="{{ $from }}" class="admin-input">
    <input type="date" name="to" value="{{ $to }}" class="admin-input">
    <x-button type="submit" variant="secondary">{{ __('Filter') }}</x-button>
</form>

<x-card :padding="false">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">{{ __('Date') }}</th>
                    <th class="px-4 py-3">{{ __('Reference') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Debit') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Credit') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Balance') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr class="bg-slate-50/50">
                    <td colspan="4" class="px-4 py-2 text-right text-xs uppercase tracking-wide text-slate-500">{{ __('Opening balance') }}</td>
                    <td class="px-4 py-2 text-right font-mono font-semibold">{{ number_format((float) $opening, 2) }}</td>
                </tr>
                @php $running = $opening; @endphp
                @foreach($entries as $e)
                    @php $running += (float) $e->debit - (float) $e->credit; @endphp
                    <tr>
                        <td class="px-4 py-2 text-slate-700">{{ $e->date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-2 text-xs text-slate-500">{{ $e->reference_type ? class_basename($e->reference_type).' #'.$e->reference_id : '—' }}</td>
                        <td class="px-4 py-2 text-right">{{ $e->debit > 0 ? number_format((float) $e->debit, 2) : '' }}</td>
                        <td class="px-4 py-2 text-right">{{ $e->credit > 0 ? number_format((float) $e->credit, 2) : '' }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ number_format($running, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="bg-slate-50/50 font-semibold">
                    <td colspan="4" class="px-4 py-2 text-right text-xs uppercase tracking-wide text-slate-600">{{ __('Closing balance') }}</td>
                    <td class="px-4 py-2 text-right font-mono">{{ number_format((float) $closing, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</x-card>
