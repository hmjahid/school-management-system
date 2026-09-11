@extends('layouts.dashboard')

@section('title', $message->subject . ' — ' . config('app.name', 'School'))

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('messages.index') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">
            &larr; {{ __('Back to Inbox') }}
        </a>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-xl font-bold text-gray-900">{{ $message->subject }}</h1>
            <div class="mt-3 flex flex-wrap items-center gap-4 text-sm text-gray-500">
                <div class="flex items-center gap-2">
                    <span class="font-medium text-gray-700">{{ __('From:') }}</span>
                    <span>{{ $message->sender->name ?? __('Unknown') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-medium text-gray-700">{{ __('To:') }}</span>
                    <span>{{ $message->receiver->name ?? __('Unknown') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-medium text-gray-700">{{ __('Date:') }}</span>
                    <span>{{ $message->created_at?->format('M d, Y h:i A') }}</span>
                </div>
            </div>
        </div>

        <div class="prose prose-sm max-w-none text-gray-800">
            {!! nl2br(e($message->body)) !!}
        </div>

        <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-gray-200 pt-4">
            <a href="{{ route('messages.create', ['reply_to' => $message->id]) }}"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                {{ __('Reply') }}
            </a>
            <form method="post" action="{{ route('messages.destroy', $message) }}" onsubmit="return confirm('{{ __('Delete this message?') }}')">
                @csrf
                @method('delete')
                <button type="submit" class="rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 transition-colors hover:bg-red-50">
                    {{ __('Delete') }}
                </button>
            </form>
        </div>
    </div>
@endsection
