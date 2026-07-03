@extends('layouts.dashboard')

@section('title', __('Notifications'))

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('Notifications') }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                @if ($unreadCount > 0)
                    {{ trans_choice(':count unread notification|:count unread notifications', $unreadCount, ['count' => $unreadCount]) }}
                @else
                    {{ __('All caught up.') }}
                @endif
            </p>
        </div>
        @if ($unreadCount > 0)
            <form method="post" action="{{ route('notifications.markAll') }}" data-notifications-mark-all-form>
                @csrf
                <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ __('Mark all read') }}
                </button>
            </form>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        @forelse ($notifications as $n)
            @php
                $data = (array) $n->data;
                $unread = $n->read_at === null;
                $url = (string) ($data['url'] ?? '#');
            @endphp
            <a href="{{ $url === '#' ? '#' : route('notifications.read', $n->id) }}"
               class="flex items-start gap-3 border-b border-gray-100 px-5 py-4 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/30 {{ $unread ? 'bg-blue-50/40 dark:bg-blue-900/10' : '' }}">
                <div class="mt-1 size-2 shrink-0 rounded-full {{ $unread ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ $data['title'] ?? class_basename($n->type ?? 'Notification') }}
                    </p>
                    @if (! empty($data['message']))
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $data['message'] }}</p>
                    @endif
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $n->created_at?->diffForHumans() }}</p>
                </div>
            </a>
        @empty
            <div class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ __('No notifications yet.') }}
            </div>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="mt-4">{{ $notifications->links() }}</div>
    @endif
@endsection
