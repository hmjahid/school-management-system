@extends('layouts.dashboard')

@section('title', __('Form submissions') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Website form submissions') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Contact, newsletter sign-ups, feedback, complaints, and scholarship inquiries from the public site.') }}</p>
        </div>
        <form method="get" class="flex flex-wrap items-center gap-2">
            <select name="type" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" onchange="this.form.submit()">
                <option value="">{{ __('All types') }}</option>
                <option value="contact" @selected(request('type') === 'contact')>{{ __('Contact') }}</option>
                <option value="feedback" @selected(request('type') === 'feedback')>{{ __('Feedback') }}</option>
                <option value="complaint" @selected(request('type') === 'complaint')>{{ __('Complaint') }}</option>
                <option value="scholarship" @selected(request('type') === 'scholarship')>{{ __('Scholarship') }}</option>
                <option value="newsletter" @selected(request('type') === 'newsletter')>{{ __('Newsletter') }}</option>
            </select>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Date') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Type') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Email') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Message') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($rows as $row)
                    <tr class="align-top">
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $row->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $row->type }}</td>
                        <td class="px-4 py-3 text-gray-900">{{ $row->name }}</td>
                        <td class="px-4 py-3"><a href="mailto:{{ $row->email }}" class="text-blue-600 hover:underline">{{ $row->email }}</a></td>
                        <td class="px-4 py-3 max-w-md text-gray-600">{{ \Illuminate\Support\Str::limit($row->message, 200) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('No submissions yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $rows->links() }}
    </div>
@endsection
