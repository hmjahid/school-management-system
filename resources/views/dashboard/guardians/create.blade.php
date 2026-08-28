@extends('layouts.dashboard')

@section('title', __('Add parent / guardian') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Add parent / guardian') }}</h1>
        <a href="{{ route('dashboard.parents') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back to list') }}</a>
    </div>
    @include('dashboard.partials.form-errors')

    @php
        $tabs = [
            'account' => __('Account'),
            'personal' => __('Personal'),
            'address' => __('Address'),
        ];
        $activeTab = array_key_first($tabs);
        $field = 'mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm';
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @include('dashboard.partials.tabs-nav', ['tabs' => $tabs, 'active' => $activeTab])

        <form method="post" action="{{ route('dashboard.parents.store') }}" class="space-y-6">
            @csrf

            <section data-tab-panel="account" class="grid gap-4 sm:grid-cols-2">
                <div><label class="block text-sm font-medium text-gray-700">{{ __('Name') }} *</label><input name="name" value="{{ old('name') }}" required class="{{ $field }}"></div>
                <div><label class="block text-sm font-medium text-gray-700">{{ __('Email') }} *</label><input type="email" name="email" value="{{ old('email') }}" required class="{{ $field }}"></div>
                <div><label class="block text-sm font-medium text-gray-700">{{ __('Password') }} *</label><input type="password" name="password" id="guardian-password-input" required class="{{ $field }}"></div>
                <div><label class="block text-sm font-medium text-gray-700">{{ __('Confirm password') }} *</label><input type="password" name="password_confirmation" required class="{{ $field }}" data-inline-match="#guardian-password-input" data-error-for="guardian-password-mismatch"><p id="guardian-password-mismatch" class="mt-1 hidden text-xs font-medium text-red-600">{{ __('Passwords do not match.') }}</p></div>
                <div><label class="block text-sm font-medium text-gray-700">{{ __('Phone') }} *</label><input name="phone" value="{{ old('phone') }}" required class="{{ $field }}"></div>
                <div><label class="block text-sm font-medium text-gray-700">{{ __('Office phone') }}</label><input name="office_phone" value="{{ old('office_phone') }}" class="{{ $field }}"></div>
            </section>

            <section data-tab-panel="personal" class="hidden grid gap-4 sm:grid-cols-2">
                <div><label class="block text-sm font-medium text-gray-700">{{ __('Occupation') }}</label><input name="occupation" value="{{ old('occupation') }}" class="{{ $field }}"></div>
                <div><label class="block text-sm font-medium text-gray-700">{{ __('Company') }}</label><input name="company" value="{{ old('company') }}" class="{{ $field }}"></div>
                <div><label class="block text-sm font-medium text-gray-700">{{ __('Relationship to student') }}</label><input name="relationship" value="{{ old('relationship') }}" class="{{ $field }}"></div>
                <div><label class="block text-sm font-medium text-gray-700">{{ __('Nationality') }}</label><input name="nationality" value="{{ old('nationality') }}" class="{{ $field }}"></div>
                <div><label class="block text-sm font-medium text-gray-700">{{ __('Religion') }}</label><input name="religion" value="{{ old('religion') }}" class="{{ $field }}"></div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Blood group') }}</label>
                    <select name="blood_group" class="{{ $field }}">
                        <option value="">—</option>
                        @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                            <option value="{{ $bg }}" @selected(old('blood_group') === $bg)>{{ $bg }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="block text-sm font-medium text-gray-700">{{ __('NID number') }}</label><input name="nid_number" value="{{ old('nid_number') }}" class="{{ $field }}"></div>
                <div><label class="block text-sm font-medium text-gray-700">{{ __('Education level') }}</label><input name="education_level" value="{{ old('education_level') }}" class="{{ $field }}"></div>
            </section>

            <section data-tab-panel="address" class="hidden grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700">{{ __('Present address') }} *</label><textarea name="present_address" rows="2" required class="{{ $field }}">{{ old('present_address') }}</textarea></div>
                <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700">{{ __('Permanent address') }}</label><textarea name="permanent_address" rows="2" class="{{ $field }}">{{ old('permanent_address') }}</textarea></div>
                <div><label class="block text-sm font-medium text-gray-700">{{ __('City') }}</label><input name="city" value="{{ old('city') }}" class="{{ $field }}"></div>
                <div><label class="block text-sm font-medium text-gray-700">{{ __('State / Division') }}</label><input name="state" value="{{ old('state') }}" class="{{ $field }}"></div>
                <div><label class="block text-sm font-medium text-gray-700">{{ __('ZIP / Post code') }}</label><input name="zip_code" value="{{ old('zip_code') }}" class="{{ $field }}"></div>
                <div><label class="block text-sm font-medium text-gray-700">{{ __('Country') }}</label><input name="country" value="{{ old('country') }}" class="{{ $field }}"></div>
            </section>

            <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
                <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
    @include('dashboard.partials.inline-validation')

    <p class="mt-4 text-sm text-gray-600">{{ __('Link students after saving, or use the edit screen to attach children.') }}</p>
@endsection