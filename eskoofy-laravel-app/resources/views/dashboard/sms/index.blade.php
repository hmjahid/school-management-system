@extends('layouts.dashboard')

@section('title', __('Bulk SMS') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Bulk SMS')" :description="__('Compose, target, and send SMS messages.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Bulk SMS')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-button :href="route('dashboard.sms.templates')" variant="ghost" size="sm">{{ __('Templates') }}</x-button>
            <x-button :href="route('dashboard.sms.compose')">{{ __('New campaign') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card :padding="false">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">{{ __('Campaign') }}</th>
                    <th class="px-4 py-3">{{ __('Audience') }}</th>
                    <th class="px-4 py-3">{{ __('Recipients') }}</th>
                    <th class="px-4 py-3">{{ __('Status') }}</th>
                    <th class="px-4 py-3">{{ __('Sent') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $c)
                    @php
$cls = match($c->status) {
    'sent' => 'success',
    'scheduled' => 'info',
    'sending' => 'brand',
    'failed' => 'danger',
    default => 'default',
};
                    @endphp
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-900">{{ $c->name }}</div>
                            <div class="text-xs text-slate-500">{{ $c->creator?->name }}</div>
                        </td>
                        <td class="px-4 py-3 capitalize text-slate-700">{{ str_replace('_',' ',$c->audience_type) }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $c->recipients_count }}</td>
                        <td class="px-4 py-3"><x-badge :variant="$cls">{{ ucfirst($c->status) }}</x-badge></td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $c->sent_at?->format('Y-m-d H:i') ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-16"><x-empty-state :title="__('No campaigns yet')" :message="__('Send your first SMS campaign to get started.')" icon="inbox" /></td></tr>
                @endforelse
            </tbody>
        </table>
        @if($rows->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $rows->links() }}</div>
        @endif
    </x-card>
@endsection