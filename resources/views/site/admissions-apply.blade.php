@extends('layouts.app')

@section('title', site_ui('admissions_apply.page_title') . ' — ' . ($siteSettings->school_name ?? config('app.name')))

@section('content')
    <div class="bg-white">
        <div class="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <h1 class="text-4xl font-bold md:text-5xl">{{ site_ui('admissions_apply.hero_title') }}</h1>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-blue-100">{{ site_ui('admissions_apply.hero_subtitle') }}</p>
            </div>
        </div>

        <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
            <a href="{{ route('site.admissions') }}" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                {{ site_ui('admissions_apply.back') }}
            </a>

            @php
                $c = is_array($content->content ?? null) ? $content->content : [];
            @endphp
            @if(!empty($c['apply_intro']))
                <p class="mt-4 text-slate-600">{{ $c['apply_intro'] }}</p>
            @endif

            @if($sessions->isEmpty() || $batches->isEmpty())
                <div class="mt-8 rounded-xl border border-yellow-200 bg-yellow-50 px-6 py-4 text-sm text-yellow-900">
                    {{ site_ui('admissions_apply.not_configured') }}
                </div>
            @else
                {{-- Step progress bar --}}
                <div class="mt-8" data-step-progress>
                    <div class="flex items-center justify-between">
                        @foreach([1 => __('Personal Info'), 2 => __('Academic Info'), 3 => __('Guardian Info'), 4 => __('Documents'), 5 => __('Review')] as $step => $label)
                            <div class="flex flex-col items-center">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold transition-all duration-300 data-[active=true]:bg-blue-600 data-[active=true]:text-white data-[completed=true]:bg-green-500 data-[completed=true]:text-white bg-slate-200 text-slate-500" data-step-dot="{{ $step }}">
                                    <span data-step-number>{{ $step }}</span>
                                    <svg class="hidden h-5 w-5" data-step-check fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <span class="mt-1.5 hidden text-xs font-medium text-slate-500 sm:block" data-step-label="{{ $step }}">{{ $label }}</span>
                            </div>
                            @if(!$loop->last)
                                <div class="h-0.5 flex-1 mx-2 bg-slate-200" data-step-connector="{{ $step }}">
                                    <div class="h-full w-0 bg-green-500 transition-all duration-500" data-step-connector-fill></div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <form method="post" action="{{ route('admissions.apply.store') }}" enctype="multipart/form-data" class="mt-10" data-multistep>
                    @csrf
                    <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

                    {{-- Step 1: Personal Info --}}
                    <div data-step-panel="1" class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                        <h2 class="text-xl font-bold text-slate-900">{{ __('Personal Information') }}</h2>
                        <div class="mt-6 grid gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Full Name') }} <span class="text-red-500">*</span></label>
                                <input type="text" name="full_name" value="{{ old('full_name') }}" required
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Date of Birth') }} <span class="text-red-500">*</span></label>
                                <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Gender') }} <span class="text-red-500">*</span></label>
                                <select name="gender" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option value="">{{ __('Select') }}</option>
                                    <option value="male" @selected(old('gender') === 'male')>{{ __('Male') }}</option>
                                    <option value="female" @selected(old('gender') === 'female')>{{ __('Female') }}</option>
                                    <option value="other" @selected(old('gender') === 'other')>{{ __('Other') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Blood Group') }}</label>
                                <input type="text" name="blood_group" value="{{ old('blood_group') }}" placeholder="A+"
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Religion') }}</label>
                                <input type="text" name="religion" value="{{ old('religion') }}"
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Address') }} <span class="text-red-500">*</span></label>
                                <textarea name="address" rows="2" required
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">{{ old('address') }}</textarea>
                            </div>
                        </div>
                        <div class="mt-8 flex justify-end">
                            <button type="button" data-step-next class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all hover:bg-blue-700">
                                {{ __('Next Step') }}
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Step 2: Academic Info --}}
                    <div data-step-panel="2" class="hidden rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                        <h2 class="text-xl font-bold text-slate-900">{{ __('Academic Information') }}</h2>
                        <div class="mt-6 grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Academic Session') }} <span class="text-red-500">*</span></label>
                                <select name="academic_session_id" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option value="">{{ __('Select') }}</option>
                                    @foreach ($sessions as $s)
                                        <option value="{{ $s->id }}" @selected(old('academic_session_id') == $s->id)>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Class / Batch') }} <span class="text-red-500">*</span></label>
                                <select name="batch_id" required class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option value="">{{ __('Select') }}</option>
                                    @foreach ($batches as $b)
                                        <option value="{{ $b->id }}" @selected(old('batch_id') == $b->id)>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Previous School') }}</label>
                                <input type="text" name="previous_school" value="{{ old('previous_school') }}"
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Previous Class') }}</label>
                                <input type="text" name="previous_class" value="{{ old('previous_class') }}"
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Previous Grade / GPA') }}</label>
                                <input type="text" name="previous_grade" value="{{ old('previous_grade') }}"
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                        </div>
                        <div class="mt-8 flex items-center justify-between">
                            <button type="button" data-step-prev class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition-all hover:bg-slate-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                {{ __('Previous') }}
                            </button>
                            <button type="button" data-step-next class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all hover:bg-blue-700">
                                {{ __('Next Step') }}
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Step 3: Guardian Info --}}
                    <div data-step-panel="3" class="hidden rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                        <h2 class="text-xl font-bold text-slate-900">{{ __('Guardian Information') }}</h2>
                        <div class="mt-6 grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Father\'s Name') }} <span class="text-red-500">*</span></label>
                                <input type="text" name="father_name" value="{{ old('father_name') }}" required
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Father\'s Phone') }} <span class="text-red-500">*</span></label>
                                <input type="text" name="father_phone" value="{{ old('father_phone') }}" required
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Father\'s Occupation') }}</label>
                                <input type="text" name="father_occupation" value="{{ old('father_occupation') }}"
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Mother\'s Name') }} <span class="text-red-500">*</span></label>
                                <input type="text" name="mother_name" value="{{ old('mother_name') }}" required
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Mother\'s Phone') }} <span class="text-red-500">*</span></label>
                                <input type="text" name="mother_phone" value="{{ old('mother_phone') }}" required
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Mother\'s Occupation') }}</label>
                                <input type="text" name="mother_occupation" value="{{ old('mother_occupation') }}"
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div class="sm:col-span-2 border-t border-slate-100 pt-4">
                                <p class="text-sm font-medium text-slate-500">{{ __('Guardian (if different from parents)') }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Guardian Name') }}</label>
                                <input type="text" name="guardian_name" value="{{ old('guardian_name') }}"
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Relation') }}</label>
                                <input type="text" name="guardian_relation" value="{{ old('guardian_relation') }}"
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Guardian Phone') }}</label>
                                <input type="text" name="guardian_phone" value="{{ old('guardian_phone') }}"
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                        </div>
                        <div class="mt-8 flex items-center justify-between">
                            <button type="button" data-step-prev class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition-all hover:bg-slate-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                {{ __('Previous') }}
                            </button>
                            <button type="button" data-step-next class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all hover:bg-blue-700">
                                {{ __('Next Step') }}
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Step 4: Documents --}}
                    <div data-step-panel="4" class="hidden rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                        <h2 class="text-xl font-bold text-slate-900">{{ __('Documents Upload') }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ site_ui('admissions_apply.documents_help') }}</p>
                        <div class="mt-6 grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Transfer Certificate') }}</label>
                                <div class="mt-1.5 flex items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 p-6 text-center transition hover:border-blue-300 hover:bg-blue-50">
                                    <div>
                                        <svg class="mx-auto h-8 w-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        <p class="mt-2 text-xs text-slate-500">{{ __('Click or drag to upload') }}</p>
                                        <input type="file" name="transfer_certificate" accept=".pdf,.jpg,.png" class="mt-2 text-xs text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1 file:text-xs file:font-medium file:text-blue-700 hover:file:bg-blue-100">
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Birth Certificate') }}</label>
                                <div class="mt-1.5 flex items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 p-6 text-center transition hover:border-blue-300 hover:bg-blue-50">
                                    <div>
                                        <svg class="mx-auto h-8 w-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        <p class="mt-2 text-xs text-slate-500">{{ __('Click or drag to upload') }}</p>
                                        <input type="file" name="birth_certificate" accept=".pdf,.jpg,.png" class="mt-2 text-xs text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1 file:text-xs file:font-medium file:text-blue-700 hover:file:bg-blue-100">
                                    </div>
                                </div>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Other Documents') }}</label>
                                <div class="mt-1.5 flex items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 p-6 text-center transition hover:border-blue-300 hover:bg-blue-50">
                                    <div>
                                        <svg class="mx-auto h-8 w-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <p class="mt-2 text-xs text-slate-500">{{ __('Upload multiple files (optional)') }}</p>
                                        <input type="file" name="other_documents[]" multiple accept=".pdf,.jpg,.png" class="mt-2 text-xs text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1 file:text-xs file:font-medium file:text-blue-700 hover:file:bg-blue-100">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 flex items-center justify-between">
                            <button type="button" data-step-prev class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition-all hover:bg-slate-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                {{ __('Previous') }}
                            </button>
                            <button type="button" data-step-next class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all hover:bg-blue-700">
                                {{ __('Review & Submit') }}
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Step 5: Review & Submit --}}
                    <div data-step-panel="5" class="hidden rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                        <h2 class="text-xl font-bold text-slate-900">{{ __('Review Your Application') }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ __('Please review all information before submitting. You can go back to edit any section.') }}</p>
                        <div class="mt-8 space-y-4" data-review-summary>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                                <h3 class="text-sm font-semibold text-slate-900">{{ __('Personal Information') }}</h3>
                                <div class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-2" data-review-personal></div>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                                <h3 class="text-sm font-semibold text-slate-900">{{ __('Academic Information') }}</h3>
                                <div class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-2" data-review-academic></div>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                                <h3 class="text-sm font-semibold text-slate-900">{{ __('Guardian Information') }}</h3>
                                <div class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-2" data-review-guardian></div>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-5">
                                <h3 class="text-sm font-semibold text-slate-900">{{ __('Documents') }}</h3>
                                <div class="mt-3 text-sm text-slate-600" data-review-documents></div>
                            </div>
                        </div>
                        <div class="mt-8 flex items-center justify-between">
                            <button type="button" data-step-prev class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition-all hover:bg-slate-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                {{ __('Previous') }}
                            </button>
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-8 py-3.5 text-sm font-semibold text-white shadow-lg shadow-green-600/20 transition-all hover:bg-green-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ site_ui('admissions_apply.submit') }}
                            </button>
                        </div>
                    </div>
                </form>

                {{-- Footer note --}}
                <p class="mt-8 text-sm text-slate-500">{{ site_ui('admissions_apply.footer_note') }}</p>
            @endif
        </div>
    </div>
@endsection
