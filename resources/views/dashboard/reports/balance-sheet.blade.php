@extends('layouts.dashboard')

@section('title', __('Balance sheet') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Balance sheet')" :description="__('As of') . ' ' . $asOf">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Reports')],
                ['label' => __('Balance sheet')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <form method="get" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-3">
        <input type="date" name="as_of" value="{{ $asOf }}" class="admin-input">
        <x-button type="submit" variant="secondary">{{ __('Refresh') }}</x-button>
    </form>

    <x-card>
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-700">{{ __('Assets') }}</h2>
                <table class="mt-2 w-full text-sm">
                    @foreach($assets as $r)
                        <tr class="border-b border-slate-100">
                            <td class="py-1.5 text-slate-700">{{ $r['account']->name_en }}</td>
                            <td class="py-1.5 text-right font-mono">{{ number_format((float) $r['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-semibold">
                        <td class="py-2 text-slate-900">{{ __('Total assets') }}</td>
                        <td class="py-2 text-right font-mono">{{ number_format((float) $totalAssets, 2) }}</td>
                    </tr>
                </table>
            </div>
            <div class="space-y-6">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-700">{{ __('Liabilities') }}</h2>
                    <table class="mt-2 w-full text-sm">
                        @foreach($liabilities as $r)
                            <tr class="border-b border-slate-100">
                                <td class="py-1.5 text-slate-700">{{ $r['account']->name_en }}</td>
                                <td class="py-1.5 text-right font-mono">{{ number_format((float) $r['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="font-semibold">
                            <td class="py-2 text-slate-900">{{ __('Total liabilities') }}</td>
                            <td class="py-2 text-right font-mono">{{ number_format((float) $totalLiabilities, 2) }}</td>
                        </tr>
                    </table>
                </div>
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-700">{{ __('Equity') }}</h2>
                    <table class="mt-2 w-full text-sm">
                        @foreach($equity as $r)
                            <tr class="border-b border-slate-100">
                                <td class="py-1.5 text-slate-700">{{ $r['account']->name_en }}</td>
                                <td class="py-1.5 text-right font-mono">{{ number_format((float) $r['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="font-semibold">
                            <td class="py-2 text-slate-900">{{ __('Total equity') }}</td>
                            <td class="py-2 text-right font-mono">{{ number_format((float) $totalEquity, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </x-card>
@endsection