@extends('layouts.dashboard')

@section('title', __('Preview recipients') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Preview recipients')" :description="__('Total: :count', ['count' => $total])">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Bulk SMS'), 'url' => route('dashboard.sms.index')],
                ['label' => __('Preview')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <x-card :title="__('Message preview')" class="mb-6">
        <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-800">{{ $data['message'] }}</p>
        <p class="mt-2 text-xs text-slate-500">{{ __('Will be sent to :count unique phone numbers.', ['count' => $total]) }}</p>
    </x-card>

    <x-card :title="__('Sample recipients (first 50)')" :padding="false">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">{{ __('Phone') }}</th>
                    <th class="px-4 py-3">{{ __('Type') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($recipients as $r)
                    <tr>
                        <td class="px-4 py-2 font-mono">{{ $r['phone'] }}</td>
                        <td class="px-4 py-2 text-slate-700">{{ ucfirst($r['user_type']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-card>

    <form method="post" action="{{ route('dashboard.sms.send') }}" class="mt-6 flex justify-end gap-2">
        @csrf
        @foreach($data as $k => $v)
            @if(is_array($v))
                @foreach($v as $val)
                    <input type="hidden" name="{{ $k }}[]" value="{{ $val }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endif
        @endforeach
        <x-button :href="route('dashboard.sms.compose')" variant="ghost">{{ __('Edit') }}</x-button>
        <x-button type="submit">{{ __('Send now') }}</x-button>
    </form>
@endsection