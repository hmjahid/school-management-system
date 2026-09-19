@extends('layouts.dashboard')

@section('title', __('Inbox') . ' — ' . config('app.name', 'School'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Inbox') }}</h1>
            <p class="mt-1 text-sm text-gray-600">
                {{ __('You have :count unread message(s).', ['count' => $unreadCount]) }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('messages.sent') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                {{ __('Sent') }}
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
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('From') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Subject') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Date') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($messages as $message)
                    <tr class="{{ !$message->read_at ? 'bg-blue-50/50' : '' }}">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @if(!$message->read_at)
                                    <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                                @endif
                                <span class="font-medium {{ !$message->read_at ? 'text-gray-900' : 'text-gray-700' }}">
                                    {{ $message->sender->name ?? __('Unknown') }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('messages.show', $message) }}" class="{{ !$message->read_at ? 'font-semibold text-gray-900' : 'text-gray-700' }} hover:text-blue-600">
                                {{ $message->subject }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $message->created_at?->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('messages.show', $message) }}" class="text-blue-600 hover:underline">{{ __('View') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">{{ __('Your inbox is empty.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $messages->links() }}</div>
@endsection
