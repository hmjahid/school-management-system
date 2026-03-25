@extends('layouts.dashboard')

@section('title', __('CMS pages') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Website pages') }}</h1>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dashboard.cms.edit', ['page' => 'site-ui']) }}" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                {{ __('Global labels (nav, footer, …)') }}
            </a>
            <a href="{{ route('dashboard.cms.edit', ['page' => 'home']) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                {{ __('Home content') }}
            </a>
        </div>
    </div>

    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-700 shadow-sm">
        <p class="font-semibold text-gray-900">{{ __('Quick edit by route') }}</p>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ([
                'site-ui' => __('All UI labels'),
                'home' => __('Home'),
                'about' => __('About'),
                'academics' => __('Academics'),
                'admissions' => __('Admissions'),
                'students' => __('Students'),
                'faculty' => __('Faculty'),
                'contact' => __('Contact'),
                'payments' => __('Payments'),
            ] as $slug => $label)
                <a href="{{ route('dashboard.cms.edit', ['page' => $slug]) }}" class="rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 font-medium text-indigo-700 hover:bg-gray-100">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Slug') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Title') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Active') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($pages as $p)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-gray-900">{{ $p->page }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $p->title }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $p->is_active ? __('Yes') : __('No') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('dashboard.cms.edit', ['page' => $p->page]) }}" class="font-medium text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                {{ __('No pages yet. Create one with the button above or edit Header/Footer from the sidebar.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pages->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">{{ $pages->links() }}</div>
        @endif
    </div>
@endsection
