@extends('layouts.app')

@section('title', __('Online application') . ' — ' . ($siteSettings->school_name ?? config('app.name')))

@section('content')
    <div class="bg-white">
        @include('site.partials.inner-hero', [
            'title' => __('Online admission application'),
            'subtitle' => __('Complete all sections. You will receive an application number after submission.'),
        ])
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('site.admissions') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">← {{ __('Back to admissions') }}</a>
        @php
            $c = is_array($content->content ?? null) ? $content->content : [];
        @endphp
        @if(!empty($c['apply_intro']))
            <p class="mt-3 max-w-3xl text-gray-600">{{ $c['apply_intro'] }}</p>
        @endif
    </div>

    @if($sessions->isEmpty() || $batches->isEmpty())
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-900">
            {{ __('Admissions are not fully configured yet (missing academic session or batch). Please contact the school office.') }}
        </div>
    @else
        <form method="post" action="{{ route('admissions.apply.store') }}" enctype="multipart/form-data" class="space-y-10 rounded-lg border border-gray-200 bg-gray-50 p-6 shadow-md sm:p-8">
            @csrf
            <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

            <fieldset>
                <legend class="text-sm font-semibold text-gray-900">{{ __('Programme') }}</legend>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Academic session') }} *</label>
                        <select name="academic_session_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="">{{ __('Select…') }}</option>
                            @foreach ($sessions as $s)
                                <option value="{{ $s->id }}" @selected(old('academic_session_id') == $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Class / batch') }} *</label>
                        <select name="batch_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="">{{ __('Select…') }}</option>
                            @foreach ($batches as $b)
                                <option value="{{ $b->id }}" @selected(old('batch_id') == $b->id)>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-sm font-semibold text-gray-900">{{ __('Student') }}</legend>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('First name') }} *</label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Last name') }} *</label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Gender') }} *</label>
                        <select name="gender" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="">{{ __('Select…') }}</option>
                            <option value="male" @selected(old('gender') === 'male')>{{ __('Male') }}</option>
                            <option value="female" @selected(old('gender') === 'female')>{{ __('Female') }}</option>
                            <option value="other" @selected(old('gender') === 'other')>{{ __('Other') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Date of birth') }} *</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Blood group') }}</label>
                        <input type="text" name="blood_group" value="{{ old('blood_group') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Religion') }}</label>
                        <input type="text" name="religion" value="{{ old('religion') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Nationality') }}</label>
                        <input type="text" name="nationality" value="{{ old('nationality', 'Bangladeshi') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">{{ __('Photo') }}</label>
                        <input type="file" name="photo" accept="image/*" class="mt-1 w-full text-sm text-gray-600">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-sm font-semibold text-gray-900">{{ __('Contact') }}</legend>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Email') }} *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Phone') }} *</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">{{ __('Address') }} *</label>
                        <input type="text" name="address" value="{{ old('address') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('City') }} *</label>
                        <input type="text" name="city" value="{{ old('city') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('State / division') }}</label>
                        <input type="text" name="state" value="{{ old('state') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Country') }}</label>
                        <input type="text" name="country" value="{{ old('country', 'Bangladesh') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Postal code') }} *</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-sm font-semibold text-gray-900">{{ __('Parents / guardians') }}</legend>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Father name') }} *</label>
                        <input type="text" name="father_name" value="{{ old('father_name') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Father phone') }} *</label>
                        <input type="text" name="father_phone" value="{{ old('father_phone') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">{{ __('Father occupation') }}</label>
                        <input type="text" name="father_occupation" value="{{ old('father_occupation') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Mother name') }} *</label>
                        <input type="text" name="mother_name" value="{{ old('mother_name') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Mother phone') }} *</label>
                        <input type="text" name="mother_phone" value="{{ old('mother_phone') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">{{ __('Mother occupation') }}</label>
                        <input type="text" name="mother_occupation" value="{{ old('mother_occupation') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Guardian name') }}</label>
                        <input type="text" name="guardian_name" value="{{ old('guardian_name') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Guardian relation') }}</label>
                        <input type="text" name="guardian_relation" value="{{ old('guardian_relation') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">{{ __('Guardian phone') }}</label>
                        <input type="text" name="guardian_phone" value="{{ old('guardian_phone') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-sm font-semibold text-gray-900">{{ __('Previous education') }}</legend>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">{{ __('Previous school') }}</label>
                        <input type="text" name="previous_school" value="{{ old('previous_school') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Previous class') }}</label>
                        <input type="text" name="previous_class" value="{{ old('previous_class') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Previous grade') }}</label>
                        <input type="text" name="previous_grade" value="{{ old('previous_grade') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-sm font-semibold text-gray-900">{{ __('Documents') }}</legend>
                <p class="mt-2 text-sm text-gray-600">{{ __('Upload PDF or images (max 10 MB each).') }}</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Transfer certificate') }}</label>
                        <input type="file" name="transfer_certificate" class="mt-1 w-full text-sm text-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Birth certificate') }}</label>
                        <input type="file" name="birth_certificate" class="mt-1 w-full text-sm text-gray-600">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">{{ __('Other documents') }}</label>
                        <input type="file" name="other_documents[]" multiple class="mt-1 w-full text-sm text-gray-600">
                    </div>
                </div>
            </fieldset>

            <p class="text-sm text-gray-600">{{ __('After submission you will receive an application number. Fee payment and admission test scheduling are coordinated by the admissions office once your file is under review.') }}</p>

            <button type="submit" class="rounded-md bg-orange-500 px-8 py-3 text-sm font-semibold text-white transition-colors hover:bg-orange-600">{{ __('Submit application') }}</button>
        </form>
    @endif
        </div>
    </div>
@endsection
