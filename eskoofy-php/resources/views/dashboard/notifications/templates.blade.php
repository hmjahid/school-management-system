@extends('layouts.dashboard')

@section('title', __('Notification templates'))

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('Notification templates') }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Reusable email, SMS and in-app messages for the notifications your school sends.') }}</p>
        </div>
        <button type="button" data-template-toggle class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">
            {{ __('New template') }}
        </button>
    </div>

    <div class="mb-6 hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800" data-template-create>
        <form method="post" action="{{ route('notifications.templates.store') }}" class="grid gap-4 lg:grid-cols-2">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Name') }}</label>
                <input name="name" required maxlength="191" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Key') }}</label>
                <input name="key" required maxlength="191" placeholder="e.g. fee_due" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Subject') }}</label>
                <input name="subject" maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('SMS content') }}</label>
                <textarea name="sms_content" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Email content') }}</label>
                <textarea name="content" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('In-app content') }}</label>
                <textarea name="in_app_content" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"></textarea>
            </div>
            <div class="flex items-end justify-between gap-4 lg:col-span-2">
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 dark:border-gray-600">
                    {{ __('Active') }}
                </label>
                <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-500">{{ __('Create template') }}</button>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <table class="w-full min-w-[760px] text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                <tr>
                    <th class="px-5 py-3">{{ __('Name') }}</th>
                    <th class="px-5 py-3">{{ __('Key') }}</th>
                    <th class="px-5 py-3">{{ __('Channels') }}</th>
                    <th class="px-5 py-3">{{ __('Status') }}</th>
                    <th class="px-5 py-3 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($templates as $template)
                    <tr>
                        <td colspan="5" class="px-5 py-0">
                            <details class="group">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 py-3 [&::-webkit-details-marker]:hidden">
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $template->name }}</span>
                                    <span class="flex items-center gap-3">
                                        <span class="hidden font-mono text-xs text-gray-500 sm:inline">{{ $template->key }}</span>
                                        <span class="text-xs text-gray-400 group-open:rotate-180">▾</span>
                                    </span>
                                </summary>
                                <form method="post" action="{{ route('notifications.templates.update', $template) }}" class="mb-4 grid gap-4 rounded-lg border border-gray-100 bg-gray-50 p-4 lg:grid-cols-2 dark:border-gray-700 dark:bg-gray-900/40">
                                    @csrf
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Name') }}</label>
                                        <input name="name" value="{{ $template->name }}" maxlength="191" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Key') }}</label>
                                        <input name="key" value="{{ $template->key }}" maxlength="191" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono dark:border-gray-600 dark:bg-gray-900">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Subject') }}</label>
                                        <input name="subject" value="{{ $template->subject }}" maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('SMS content') }}</label>
                                        <textarea name="sms_content" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">{{ $template->sms_content }}</textarea>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Email content') }}</label>
                                        <textarea name="content" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">{{ $template->content }}</textarea>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('In-app content') }}</label>
                                        <textarea name="in_app_content" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">{{ $template->in_app_content }}</textarea>
                                    </div>
                                    <div class="flex flex-wrap items-center justify-between gap-3 lg:col-span-2">
                                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                            <input type="checkbox" name="is_active" value="1" @checked($template->is_active) class="rounded border-gray-300 dark:border-gray-600">
                                            {{ __('Active') }}
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-500">{{ __('Save') }}</button>
                                            <button type="submit" form="delete-{{ $template->id }}" class="rounded-lg border border-red-200 px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-900 dark:text-red-400">{{ __('Delete') }}</button>
                                        </div>
                                    </div>
                                </form>
                                <form id="delete-{{ $template->id }}" method="post" action="{{ route('notifications.templates.destroy', $template) }}" class="hidden">
                                    @csrf
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-400">{{ __('No templates yet — create your first one.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var toggle = document.querySelector('[data-template-toggle]');
            var panel = document.querySelector('[data-template-create]');
            if (!toggle || !panel) return;
            toggle.addEventListener('click', function () {
                panel.classList.toggle('hidden');
            });
        })();
    </script>
@endpush