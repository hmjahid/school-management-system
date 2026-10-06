@extends('layouts.dashboard')

@section('title', __('Payment gateways') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Payment gateways')" :description="__('Enable a gateway to offer it to payers, or add your own gateway with its credentials and endpoints. Only enabled gateways appear on the public payments page.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Payment gateways')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-button :href="route('dashboard.settings.general', ['tab' => 'payment'])" variant="ghost">{{ __('Payment settings') }}</x-button>
            <x-button :href="route('dashboard.payment-gateways.create')">{{ __('Add gateway') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">{{ __('Name') }}</th>
                        <th class="px-4 py-3">{{ __('Code') }}</th>
                        <th class="px-4 py-3">{{ __('Type') }}</th>
                        <th class="px-4 py-3">{{ __('Currency') }}</th>
                        <th class="px-4 py-3">{{ __('Enabled') }}</th>
                        <th class="px-4 py-3">{{ __('Configured') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ $row->name }}
                                @if($row->is_online)
                                    <span class="ml-1 text-xs text-slate-400">{{ __('Online') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $row->code }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $row->type_label }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $row->currency }}</td>
                            <td class="px-4 py-3">
                                @if($row->is_active)
                                    <x-badge variant="success">{{ __('Enabled') }}</x-badge>
                                @else
                                    <x-badge variant="default">{{ __('Disabled') }}</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($row->is_configured)
                                    <x-badge variant="success">{{ __('Ready') }}</x-badge>
                                @else
                                    <x-badge variant="warning">{{ __('Needs setup') }}</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <x-button :href="route('dashboard.payment-gateways.edit', $row)" variant="ghost" size="sm">{{ __('Edit') }}</x-button>
                                <form method="post" action="{{ route('dashboard.payment-gateways.destroy', $row) }}" class="inline" data-confirm="{{ __('Delete this gateway?') }}">
                                    @csrf @method('delete')
                                    <button class="text-xs font-semibold text-red-700 hover:underline" type="submit">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-16"><x-empty-state :title="__('No gateways yet')" :message="__('Add a payment gateway to start collecting fees online.')" icon="credit-card" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
    <x-card :title="__('Gateway diagnostics')" :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">{{ __('Code') }}</th>
                        <th class="px-4 py-3">{{ __('Label') }}</th>
                        <th class="px-4 py-3">{{ __('Active') }}</th>
                        <th class="px-4 py-3">{{ __('Test mode') }}</th>
                        <th class="px-4 py-3">{{ __('Configured') }}</th>
                        <th class="px-4 py-3">{{ __('Adapter') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($diagnostics as $item)
                        @php($row = $item['gateway'])
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $row->code }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $row->name }}</td>
                            <td class="px-4 py-3">{{ $row->is_active ? __('Yes') : __('No') }}</td>
                            <td class="px-4 py-3">{{ $row->test_mode ? __('Yes') : __('No') }}</td>
                            <td class="px-4 py-3">{{ $row->is_configured ? __('Yes') : __('No') }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $item['adapter'] }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($row->code === \App\Models\PaymentGateway::GATEWAY_TEST_GATEWAY)
                                    <x-button :href="route('payments.sandbox')" variant="ghost" size="sm">{{ __('Run test payment') }}</x-button>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-16"><x-empty-state :title="__('No gateways yet')" :message="__('Add a payment gateway to start collecting fees online.')" icon="credit-card" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
@endsection
