@extends('layouts.app')

@section('title', site_ui('portal_admission.page_title') . ' — ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-10">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ site_ui('portal_admission.page_title') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ site_ui('portal_admission.intro') }}</p>
            </div>
            <a href="{{ route('portal') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ site_ui('portal_admission.back_portal') }}</a>
        </div>

        @if(! $admission)
            <div class="mt-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-gray-700">{{ str_replace(':email', auth()->user()->email, site_ui('portal_admission.no_app')) }}</p>
                <p class="mt-2 text-sm text-gray-600">{{ site_ui('portal_admission.no_app_hint') }}</p>
                <a href="{{ route('admissions.status') }}" class="mt-4 inline-block text-sm font-semibold text-blue-700 hover:underline">{{ site_ui('portal_admission.track_status') }}</a>
            </div>
        @else
            <div class="mt-8 grid gap-6 lg:grid-cols-3">
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="text-sm text-gray-500">{{ site_ui('portal_admission.application_number') }}</div>
                            <div class="font-mono text-lg font-semibold text-gray-900">{{ $admission->application_number }}</div>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $admission->status_badge }}">{{ $admission->status_label }}</span>
                    </div>

                    <dl class="mt-6 grid gap-3 text-sm sm:grid-cols-2">
                        <dt class="text-gray-500">{{ site_ui('portal_admission.name') }}</dt>
                        <dd class="text-gray-900">{{ $admission->full_name }}</dd>
                        <dt class="text-gray-500">{{ site_ui('portal_admission.email') }}</dt>
                        <dd class="text-gray-900">{{ $admission->email }}</dd>
                        <dt class="text-gray-500">{{ site_ui('portal_admission.phone') }}</dt>
                        <dd class="text-gray-900">{{ $admission->phone }}</dd>
                        <dt class="text-gray-500">{{ site_ui('portal_admission.session_batch') }}</dt>
                        <dd class="text-gray-900">{{ $admission->academicSession?->name ?? '—' }} / {{ $admission->batch?->name ?? '—' }}</dd>
                        <dt class="text-gray-500">{{ site_ui('portal_admission.submitted') }}</dt>
                        <dd class="text-gray-900">{{ $admission->submitted_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                    </dl>

                    @if($admission->status === \App\Models\Admission::STATUS_REJECTED && $admission->rejection_reason)
                        <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                            <div class="font-semibold">{{ site_ui('portal_admission.rejection_reason') }}</div>
                            <div class="mt-1">{{ $admission->rejection_reason }}</div>
                        </div>
                    @endif
                </section>

                <aside class="space-y-6">
                    <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-gray-900">{{ site_ui('portal_admission.test_schedule') }}</h2>
                        @php($latest = $admission->tests->first())
                        @if(! $latest)
                            <p class="mt-3 text-sm text-gray-600">{{ site_ui('portal_admission.no_test') }}</p>
                        @else
                            <div class="mt-3 text-sm">
                                <div class="font-semibold text-gray-900">{{ $latest->scheduled_at?->format('Y-m-d H:i') ?? '—' }}</div>
                                <div class="mt-1 text-gray-700">{{ str_replace(':venue', $latest->venue ?: '—', site_ui('portal_admission.venue_line')) }}</div>
                                <div class="mt-1 text-gray-700">{{ str_replace(':status', $latest->status, site_ui('portal_admission.status_line')) }}</div>
                                @if($latest->notes)
                                    <div class="mt-2 text-xs text-gray-600">{{ $latest->notes }}</div>
                                @endif
                            </div>
                        @endif
                    </section>

                    <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-gray-900">{{ site_ui('portal_admission.documents') }}</h2>
                        @if($admission->documents->isEmpty())
                            <p class="mt-3 text-sm text-gray-600">{{ site_ui('portal_admission.no_documents') }}</p>
                        @else
                            <ul class="mt-3 space-y-2 text-sm">
                                @foreach($admission->documents as $doc)
                                    <li class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                                        <div class="truncate pr-3">
                                            <div class="truncate font-medium text-gray-900">{{ $doc->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $doc->type }} · {{ number_format(($doc->file_size ?? 0) / 1024, 1) }} KB</div>
                                        </div>
                                        <a class="shrink-0 text-blue-600 hover:underline" href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" rel="noreferrer">{{ site_ui('portal_admission.view') }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </aside>
            </div>
        @endif
    </div>
@endsection

