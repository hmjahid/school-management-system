@extends('layouts.dashboard')

@section('title', __('Communications Center') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Communications Center')" :description="__('All outgoing messages across channels — status and reach at a glance.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Communications')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-button :href="route('dashboard.sms.index')" variant="ghost" size="sm">{{ __('Bulk SMS') }}</x-button>
            <x-button :href="route('dashboard.announcements.create')">{{ __('New announcement') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <x-card>
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ __('SMS sent') }}</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($counts['sms_sent']) }}</div>
        </x-card>
        <x-card>
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ __('SMS queued') }}</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($counts['sms_queued']) }}</div>
        </x-card>
        <x-card>
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ __('Scheduled pending') }}</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($counts['scheduled_pending']) }}</div>
        </x-card>
        <x-card>
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ __('Announcements') }}</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($counts['announcements']) }}</div>
        </x-card>
        <x-card>
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ __('Messages') }}</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($counts['messages']) }}</div>
        </x-card>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card :padding="false">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <div class="text-sm font-semibold text-slate-700">{{ __('SMS campaigns') }}</div>
                <a href="{{ route('dashboard.sms.index') }}" class="text-xs font-medium text-brand-600 hover:text-brand-800">{{ __('View all') }} →</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($smsCampaigns as $c)
                    @php
$cls = match($c->status) {
    'sent' => 'success',
    'sending' => 'brand',
    'failed' => 'danger',
    default => 'default',
};
                    @endphp
                    <div class="px-4 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-medium text-slate-900">{{ $c->name }}</span>
                            <x-badge :variant="$cls" class="shrink-0 uppercase">{{ $c->status }}</x-badge>
                        </div>
                        <p class="mt-1 line-clamp-1 text-xs text-slate-500">{{ $c->message }}</p>
                        <p class="mt-1 text-xs text-slate-400">
                            <a href="{{ route('dashboard.sms.index') }}" class="font-medium text-slate-500">{{ $c->recipients_count }}</a> {{ __('recipients') }} ·
                            {{ $c->audience_type }} ·
                            {{ $c->sent_at?->format('Y-m-d H:i') ?: __('queued') }}
                        </p>
                    </div>
                @empty
                    <div class="p-12"><x-empty-state :title="__('No SMS campaigns yet')" :message="__('SMS campaigns will appear here once sent.')" icon="inbox" /></div>
                @endforelse
            </div>
        </x-card>

        <x-card :padding="false">
            <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">{{ __('Scheduled notifications') }}</div>
            <div class="divide-y divide-slate-100">
                @forelse($scheduled as $s)
                    @php
$schema = match($s->status) {
    'sent' => 'success',
    'failed' => 'danger',
    'cancelled' => 'default',
    default => 'brand',
};
                    @endphp
                    <div class="px-4 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-medium text-slate-900">{{ $s->name }}</span>
                            <x-badge :variant="$schema" class="shrink-0 uppercase">{{ $s->status }}</x-badge>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $s->channels ? implode(', ', $s->channels) : '—' }} ·
                            {{ is_array($s->recipients) ? count($s->recipients) : '0' }} {{ __('recipients') }} ·
                            @if($s->sent_at)
                                {{ $s->sent_at->format('Y-m-d H:i') }}
                            @else
                                {{ __('scheduled') }} {{ $s->scheduled_at?->format('Y-m-d H:i') ?: '—' }}
                            @endif
                        </p>
                    </div>
                @empty
                    <div class="p-12"><x-empty-state :title="__('Nothing scheduled yet')" :message="__('Scheduled notifications will appear here.')" icon="clock" /></div>
                @endforelse
            </div>
        </x-card>

        <x-card :padding="false">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <div class="text-sm font-semibold text-slate-700">{{ __('Announcements') }}</div>
                <a href="{{ route('dashboard.announcements.index') }}" class="text-xs font-medium text-brand-600 hover:text-brand-800">{{ __('View all') }} →</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($announcements as $a)
                    <div class="px-4 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-medium text-slate-900">{{ $a->title }}</span>
                            <x-badge :variant="$a->is_published ? 'success' : 'warning'" class="shrink-0">{{ $a->is_published ? __('Live') : __('Draft') }}</x-badge>
                        </div>
                        <p class="mt-1 line-clamp-1 text-xs text-slate-500">{{ $a->body }}</p>
                    </div>
                @empty
                    <div class="p-12"><x-empty-state :title="__('No announcements yet')" :message="__('Announcements will appear here once published.')" icon="inbox" /></div>
                @endforelse
            </div>
        </x-card>

        <x-card :padding="false">
            <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">{{ __('In-app notifications') }}</div>
            <div class="divide-y divide-slate-100">
                @forelse($notifications as $n)
                    <div class="px-4 py-3">
                        <div class="text-sm font-medium text-slate-900">{{ \Illuminate\Support\Str::limit(is_string($n->type) ? class_basename($n->type) : '—', 60) }}</div>
                        <p class="mt-0.5 line-clamp-1 text-xs text-slate-500">
                            {{ is_array($n->data) ? \Illuminate\Support\Str::limit(data_get($n->data, 'message') ?: json_encode($n->data), 120) : (is_string($n->data) ? \Illuminate\Support\Str::limit($n->data, 120) : '—') }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ $n->created_at?->diffForHumans() }}</p>
                    </div>
                @empty
                    <div class="p-12"><x-empty-state :title="__('No notifications sent yet')" :message="__('In-app notifications will appear here once sent.')" icon="inbox" /></div>
                @endforelse
            </div>
        </x-card>
    </div>
@endsection