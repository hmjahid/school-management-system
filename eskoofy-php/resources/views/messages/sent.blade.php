@extends('layouts.dashboard')

@section('title', __('Sent Messages') . ' — ' . config('app.name', 'School'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Sent Messages') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Messages you have sent.') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('messages.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                {{ __('Inbox') }}
            </a>
            <a href="{{ route('messages.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                {{ __('Compose') }}
            </a>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('To') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Subject') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Date') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($messages as $message)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $message->receiver->name ?? __('Unknown') }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $message->subject }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $message->created_at?->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('messages.show', $message) }}" class="text-blue-600 hover:underline">{{ __('View') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">{{ __('No sent messages.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $messages->links() }}</div>
@endsection
