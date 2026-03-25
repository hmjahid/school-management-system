@extends('layouts.dashboard')

@section('title', __('Admission') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Admission') }}</h1>
            <p class="mt-1 text-sm text-gray-600">
                <span class="font-mono">{{ $admission->application_number }}</span> ·
                <span class="font-semibold">{{ $admission->full_name }}</span>
            </p>
        </div>
        <a href="{{ route('dashboard.admissions.index') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ __('Back') }}</a>
    </div>

    @include('dashboard.partials.form-errors')

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Applicant details') }}</h2>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <dt class="text-gray-500">{{ __('Status') }}</dt>
                <dd><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $admission->status_badge }}">{{ $admission->status_label }}</span></dd>
                <dt class="text-gray-500">{{ __('Email') }}</dt>
                <dd class="text-gray-900">{{ $admission->email }}</dd>
                <dt class="text-gray-500">{{ __('Phone') }}</dt>
                <dd class="text-gray-900">{{ $admission->phone }}</dd>
                <dt class="text-gray-500">{{ __('DOB') }}</dt>
                <dd class="text-gray-900">{{ $admission->date_of_birth?->format('Y-m-d') }}</dd>
                <dt class="text-gray-500">{{ __('Session / Batch') }}</dt>
                <dd class="text-gray-900">{{ $admission->academicSession?->name ?? '—' }} / {{ $admission->batch?->name ?? '—' }}</dd>
                <dt class="text-gray-500">{{ __('Submitted') }}</dt>
                <dd class="text-gray-900">{{ $admission->submitted_at?->format('Y-m-d H:i') ?? '—' }}</dd>
            </dl>

            <h3 class="mt-8 text-sm font-semibold uppercase tracking-wide text-gray-500">{{ __('Documents') }}</h3>
            @if($admission->documents->isEmpty())
                <p class="mt-2 text-sm text-gray-600">{{ __('No documents uploaded.') }}</p>
            @else
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach($admission->documents as $doc)
                        <li class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                            <div>
                                <div class="font-medium text-gray-900">{{ $doc->name }}</div>
                                <div class="text-xs text-gray-500">{{ $doc->type }} · {{ $doc->file_type }} · {{ number_format(($doc->file_size ?? 0) / 1024, 1) }} KB</div>
                            </div>
                            <a class="text-blue-600 hover:underline" href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" rel="noreferrer">{{ __('View') }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <aside class="space-y-6">
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Update status') }}</h2>
                <form method="post" action="{{ route('dashboard.admissions.status.update', $admission) }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Status') }}</label>
                        <select name="status" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="under_review">{{ __('Under review') }}</option>
                            <option value="approved">{{ __('Approved') }}</option>
                            <option value="rejected">{{ __('Rejected') }}</option>
                            <option value="waitlisted">{{ __('Waitlisted') }}</option>
                            <option value="cancelled">{{ __('Cancelled') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Notes (optional)') }}</label>
                        <textarea name="admission_notes" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea>
                        <p class="mt-1 text-xs text-gray-500">{{ __('Used for approvals; for rejections add a reason below.') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Rejection reason (optional)') }}</label>
                        <textarea name="rejection_reason" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea>
                    </div>
                    <button class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">{{ __('Update') }}</button>
                    <p class="mt-2 text-xs text-gray-500">{{ __('This will email the applicant.') }}</p>
                </form>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Schedule test') }}</h2>
                <form method="post" action="{{ route('dashboard.admissions.tests.store', $admission) }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Scheduled at') }}</label>
                        <input type="datetime-local" name="scheduled_at" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Venue') }}</label>
                        <input name="venue" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Notes') }}</label>
                        <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea>
                    </div>
                    <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Schedule') }}</button>
                </form>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Test history') }}</h2>
                @if($admission->tests->isEmpty())
                    <p class="mt-3 text-sm text-gray-600">{{ __('No tests scheduled yet.') }}</p>
                @else
                    <div class="mt-3 space-y-3">
                        @foreach($admission->tests as $t)
                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">{{ $t->scheduled_at?->format('Y-m-d H:i') ?? '—' }}</div>
                                        <div class="text-xs text-gray-500">{{ $t->venue ?: '—' }} · {{ $t->status }}</div>
                                    </div>
                                    <form method="post" action="{{ route('dashboard.admissions.tests.destroy', [$admission, $t]) }}" onsubmit="return confirm('{{ __('Remove this test?') }}')">
                                        @csrf
                                        @method('delete')
                                        <button class="text-xs font-semibold text-red-700 hover:underline" type="submit">{{ __('Remove') }}</button>
                                    </form>
                                </div>
                                @if($t->notes)
                                    <div class="mt-2 text-xs text-gray-600">{{ $t->notes }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </aside>
    </div>
@endsection

