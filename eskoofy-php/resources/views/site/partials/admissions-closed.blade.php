@php
    use App\Models\AdmissionSetting;
    $admissionSettings = AdmissionSetting::getSettings();
@endphp
<section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-8 text-center shadow-sm">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-600">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-amber-900 sm:text-3xl">
            {{ site_ui('admissions_closed.title') }}
        </h1>
        <p class="mt-3 text-base text-amber-800">
            {{ $admissionSettings->closed_message ?: site_ui('admissions_closed.default_message') }}
        </p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="{{ route('admissions.status') }}" class="inline-flex rounded-md border border-amber-300 bg-white px-5 py-2.5 text-sm font-semibold text-amber-800 hover:bg-amber-100">
                {{ site_ui('admissions_closed.check_status') }}
            </a>
            <a href="{{ route('site.contact') }}" class="inline-flex rounded-md bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-700">
                {{ site_ui('admissions_closed.contact_us') }}
            </a>
        </div>
    </div>
</section>
