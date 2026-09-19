@extends('layouts.app')

@section('title', ($content->title ?? site_ui('nav.admissions')) . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('meta_description', $content->meta_description ?? '')

@section('content')
    <div class="bg-white">
        {{-- Hero Banner --}}
        @if($siteSettings->section_visibility['adm_hero'] ?? true)
        @if(! empty($admissionsClosed))
        <div class="relative overflow-hidden bg-gradient-to-r from-slate-700 via-slate-800 to-slate-900 py-20 text-white">
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
                <div class="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
            </div>
            <div class="relative z-10 mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-sm font-semibold backdrop-blur-sm">
                    {{ site_ui('admissions_closed.title') }}
                </span>
                <h1 class="mt-6 text-4xl font-bold md:text-5xl lg:text-6xl">{{ $content->title ?? site_ui('nav.admissions') }}</h1>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-300">{{ site_ui('admissions_closed.default_message') }}</p>
                <div class="mt-8 flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="{{ route('admissions.status') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-semibold text-slate-800 shadow-lg transition-all hover:bg-slate-50 hover:shadow-xl">
                        {{ site_ui('admissions_closed.check_status') }}
                    </a>
                    <a href="{{ route('site.contact') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-white/30 bg-white/10 px-8 py-4 text-base font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20">
                        {{ site_ui('admissions_closed.contact_us') }}
                    </a>
                </div>
            </div>
        </div>
        @else
        <div class="relative overflow-hidden bg-gradient-to-r from-orange-500 via-orange-600 to-red-600 py-20 text-white">
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
                <div class="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
            </div>
            <div class="relative z-10 mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-sm font-semibold backdrop-blur-sm">
                    <span class="h-2 w-2 rounded-full bg-white animate-pulse"></span>
                    {{ str_replace([':year', ':next'], [date('Y'), date('Y', strtotime('+1 year'))], site_ui('admissions_landing.badge')) }}
                </span>
                <h1 class="mt-6 text-4xl font-bold md:text-5xl lg:text-6xl">{{ $content->title ?? site_ui('nav.admissions') }}</h1>
                @if($content->meta_description ?? false)
                    <p class="mx-auto mt-4 max-w-2xl text-lg text-orange-100">{{ $content->meta_description }}</p>
                @endif
                <div class="mt-8 flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="{{ route('admissions.apply') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-semibold text-orange-700 shadow-lg transition-all hover:bg-orange-50 hover:shadow-xl">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ site_ui('admissions_landing.cta_apply') }}
                    </a>
                    <a href="{{ route('site.contact') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-white/30 bg-white/10 px-8 py-4 text-base font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20">
                        {{ site_ui('home.cta_contact') }}
                    </a>
                </div>
            </div>
        </div>
        @endif
        @endif

        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            @include('site.partials.sections', ['content' => $content])

            @if(! empty($admissionsClosed))
                @include('site.partials.admissions-closed')
            @else
                {{-- Quick action buttons --}}
                <div class="flex flex-wrap gap-3 reveal">
                    <a href="{{ route('admissions.apply') }}" class="inline-flex items-center gap-2 rounded-xl bg-orange-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-orange-500/20 transition-all hover:bg-orange-600 hover:shadow-xl">{{ site_ui('admissions_landing.cta_apply') }}</a>
                    <a href="{{ route('admissions.status') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-blue-600 bg-white px-6 py-3 text-sm font-semibold text-blue-700 transition-all hover:bg-blue-50">{{ site_ui('admissions_landing.cta_status') }}</a>
                    <a href="{{ route('site.payments') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-800 transition-all hover:bg-slate-50">{{ site_ui('admissions_landing.cta_payments') }}</a>
                </div>

                {{-- Admission Process Timeline --}}
                @if($siteSettings->section_visibility['adm_process'] ?? true)
                <section class="mt-16 reveal">
                    <h2 class="text-2xl font-bold text-slate-900">{{ __('Admission Process') }}</h2>
                    <div class="mt-2 h-1 w-16 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full"></div>
                    <div class="mt-10 grid gap-8 md:grid-cols-4">
                        <div class="relative text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-100 to-orange-200 text-2xl font-bold text-orange-700 shadow-md">1</div>
                            <div class="mt-4">
                                <h3 class="text-base font-semibold text-slate-900">{{ __('Submit Application') }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ __('Fill in the online form with student and guardian details.') }}</p>
                            </div>
                        </div>
                        <div class="relative text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-100 to-orange-200 text-2xl font-bold text-orange-700 shadow-md">2</div>
                            <div class="mt-4">
                                <h3 class="text-base font-semibold text-slate-900">{{ __('Document Review') }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ __('Upload required documents for verification by our team.') }}</p>
                            </div>
                        </div>
                        <div class="relative text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-100 to-orange-200 text-2xl font-bold text-orange-700 shadow-md">3</div>
                            <div class="mt-4">
                                <h3 class="text-base font-semibold text-slate-900">{{ __('Entrance Test') }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ __('Candidates may be called for a written test and interview.') }}</p>
                            </div>
                        </div>
                        <div class="relative text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-100 to-orange-200 text-2xl font-bold text-orange-700 shadow-md">4</div>
                            <div class="mt-4">
                                <h3 class="text-base font-semibold text-slate-900">{{ __('Confirmation') }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ __('Pay fees and confirm admission. Welcome to the family!') }}</p>
                            </div>
                        </div>
                    </div>
                </section>
                @endif

                {{-- Fee Structure --}}
                @if($siteSettings->section_visibility['adm_fee'] ?? true)
                <section class="mt-16 reveal">
                    <h2 class="text-2xl font-bold text-slate-900">{{ __('Fee Structure') }}</h2>
                    <div class="mt-2 h-1 w-16 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full"></div>
                    <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">{{ __('Class') }}</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">{{ __('Tuition Fee') }}</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">{{ __('Admission Fee') }}</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">{{ __('Annual Charge') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ __('Play to KG-2') }}</td>
                                    <td class="px-6 py-4 text-slate-600">৳ 2,500</td>
                                    <td class="px-6 py-4 text-slate-600">৳ 5,000</td>
                                    <td class="px-6 py-4 text-slate-600">৳ 3,000</td>
                                </tr>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ __('Primary (Class 1-5)') }}</td>
                                    <td class="px-6 py-4 text-slate-600">৳ 3,000</td>
                                    <td class="px-6 py-4 text-slate-600">৳ 6,000</td>
                                    <td class="px-6 py-4 text-slate-600">৳ 3,500</td>
                                </tr>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ __('Junior (Class 6-8)') }}</td>
                                    <td class="px-6 py-4 text-slate-600">৳ 3,500</td>
                                    <td class="px-6 py-4 text-slate-600">৳ 7,000</td>
                                    <td class="px-6 py-4 text-slate-600">৳ 4,000</td>
                                </tr>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ __('Secondary (Class 9-10)') }}</td>
                                    <td class="px-6 py-4 text-slate-600">৳ 4,000</td>
                                    <td class="px-6 py-4 text-slate-600">৳ 8,000</td>
                                    <td class="px-6 py-4 text-slate-600">৳ 4,500</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
                @endif

                {{-- Download Prospectus --}}
                @if($siteSettings->section_visibility['adm_prospectus'] ?? true)
                <section class="mt-16 rounded-2xl bg-gradient-to-br from-slate-50 to-blue-50 border border-blue-100 p-8 text-center reveal">
                    <svg class="mx-auto h-12 w-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <h2 class="mt-4 text-xl font-bold text-slate-900">{{ __('Download Prospectus') }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Get detailed information about our programs, facilities, and admission policies.') }}</p>
                    <a href="#" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        {{ __('Download PDF (2.5 MB)') }}
                    </a>
                </section>
                @endif

                {{-- FAQ Accordion --}}
                @if($siteSettings->section_visibility['adm_faq'] ?? true)
                <section class="mt-16 reveal">
                    <h2 class="text-2xl font-bold text-center text-slate-900">{{ __('Admission FAQs') }}</h2>
                    <div class="mx-auto mt-8 max-w-3xl space-y-3">
                        <details class="group rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:shadow-md">
                            <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-900">
                                {{ __('What is the minimum age for admission?') }}
                                <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <p class="mt-3 text-sm text-slate-600">{{ __('For Play/Nursery, the minimum age is 3 years as of January 1 of the admission year. For KG-1 it is 4 years, and for KG-2 it is 5 years.') }}</p>
                        </details>
                        <details class="group rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:shadow-md">
                            <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-900">
                                {{ __('Is there an entrance test?') }}
                                <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <p class="mt-3 text-sm text-slate-600">{{ __('Yes, students applying for Class 1 and above must take a written entrance test in English, Mathematics, and Bengali. An oral interview may also be conducted.') }}</p>
                        </details>
                        <details class="group rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:shadow-md">
                            <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-900">
                                {{ __('Can I apply for a scholarship?') }}
                                <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <p class="mt-3 text-sm text-slate-600">{{ __('Merit-based and need-based scholarships are available. Please contact the admissions office or fill out the scholarship inquiry form on this page.') }}</p>
                        </details>
                    </div>
                </section>
                @endif

                {{-- CTA --}}
                @if($siteSettings->section_visibility['adm_cta'] ?? true)
                <section class="mt-16 rounded-2xl bg-gradient-to-r from-orange-500 to-red-600 p-10 text-center text-white shadow-xl reveal">
                    <h2 class="text-3xl font-bold">{{ __('Ready to Join Us?') }}</h2>
                    <p class="mx-auto mt-3 max-w-2xl text-orange-100">{{ __('Take the first step towards quality education. Apply now for the academic year 2025-26.') }}</p>
                    <a href="{{ route('admissions.apply') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-orange-700 shadow-lg transition-all hover:bg-orange-50">
                        {{ site_ui('admissions_landing.cta_apply') }}
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </section>
                @endif

                {{-- Scholarship section --}}
                @if($siteSettings->section_visibility['adm_scholarship'] ?? true)
                <section class="mt-16 rounded-2xl border border-slate-200 bg-slate-50 p-8 shadow-md reveal">
                    <h2 class="text-lg font-bold text-slate-900">{{ site_ui('admissions_landing.scholarship_title') }}</h2>
                    <div class="mt-2 h-1 w-16 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full"></div>
                    <p class="mt-4 text-sm text-slate-600">{{ site_ui('admissions_landing.scholarship_intro') }}</p>
                    <form method="post" action="{{ route('admissions.scholarship.store') }}" class="mt-6 grid gap-4 sm:grid-cols-2">
                        @csrf
                        <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">{{ site_ui('admissions_landing.scholarship_full_name') }}</label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ site_ui('admissions_landing.scholarship_email') }}</label>
                            <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ site_ui('admissions_landing.scholarship_phone') }}</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">{{ site_ui('admissions_landing.scholarship_message') }}</label>
                            <textarea name="message" rows="4" required class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">{{ old('message') }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="rounded-xl bg-orange-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-orange-500/20 transition-all hover:bg-orange-600">{{ site_ui('admissions_landing.scholarship_submit') }}</button>
                        </div>
                    </form>
                </section>
                @endif
            @endif
        </div>
    </div>
@endsection
