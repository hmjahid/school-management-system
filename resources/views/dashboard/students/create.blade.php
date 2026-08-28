@extends('layouts.dashboard')

@section('title', __('Add student') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Add student') }}</h1>
        <a href="{{ route('dashboard.students') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back to list') }}</a>
    </div>

    @include('dashboard.partials.form-errors')

    @php
        $tabs = [
            'account' => __('Account'),
            'personal' => __('Personal'),
            'academic' => __('Academic'),
            'guardian' => __('Guardian'),
            'address' => __('Address'),
        ];
        $activeTab = array_key_first($tabs);
        $field = 'mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm';
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @include('dashboard.partials.tabs-nav', ['tabs' => $tabs, 'active' => $activeTab])

        <form method="post" action="{{ route('dashboard.students.store') }}" class="space-y-6">
            @csrf

            <section data-tab-panel="account" class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Full name') }} *</label>
                    <input name="name" value="{{ old('name') }}" required class="{{ $field }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Email') }} *</label>
                    <input name="email" type="email" value="{{ old('email') }}" required class="{{ $field }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Password') }} *</label>
                    <input name="password" id="password_input" type="password" required class="{{ $field }}" autocomplete="new-password">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Confirm password') }} *</label>
                    <input name="password_confirmation" type="password" required class="{{ $field }}" autocomplete="new-password" data-inline-match="#password_input" data-error-for="password-mismatch">
                    <p id="password-mismatch" class="mt-1 hidden text-xs font-medium text-red-600">{{ __('Passwords do not match.') }}</p>
                </div>
            </section>

            <section data-tab-panel="personal" class="hidden grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Blood group') }}</label>
                    <select name="blood_group" class="{{ $field }}">
                        <option value="">—</option>
                        @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                            <option value="{{ $bg }}" @selected(old('blood_group') === $bg)>{{ $bg }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Religion') }}</label>
                    <input name="religion" value="{{ old('religion') }}" class="{{ $field }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Nationality') }}</label>
                    <input name="nationality" value="{{ old('nationality') }}" class="{{ $field }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Mobile (own)') }}</label>
                    <input name="phone_1" value="{{ old('phone_1') }}" class="{{ $field }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('NID number') }}</label>
                    <input name="nid_number" value="{{ old('nid_number') }}" class="{{ $field }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Birth certificate number') }}</label>
                    <input name="birth_certificate_number" value="{{ old('birth_certificate_number') }}" class="{{ $field }}">
                </div>
            </section>

            <section data-tab-panel="academic" class="hidden grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Admission number') }} *</label>
                    <input name="admission_number" value="{{ old('admission_number') }}" required class="{{ $field }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Admission date') }} *</label>
                    <input name="admission_date" type="date" value="{{ old('admission_date') }}" required class="{{ $field }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Class') }} *</label>
                    <select name="class_id" required class="{{ $field }}">
                        <option value="">{{ __('Select') }}</option>
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}" @selected(old('class_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Section') }}</label>
                    <select name="section_id" class="{{ $field }}">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($sections as $s)
                            <option value="{{ $s->id }}" @selected(old('section_id') == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Batch') }}</label>
                    <select name="batch_id" class="{{ $field }}">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($batches as $b)
                            <option value="{{ $b->id }}" @selected(old('batch_id') == $b->id)>{{ $b->name ?? $b->code ?? ('#'.$b->id) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Roll number') }}</label>
                    <input name="roll_number" value="{{ old('roll_number') }}" class="{{ $field }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">{{ __('Status') }} *</label>
                    <select name="status" required class="{{ $field }}">
                        @foreach (['active' => __('Active'), 'inactive' => __('Inactive'), 'graduated' => __('Graduated'), 'transferred' => __('Transferred')] as $val => $label)
                            <option value="{{ $val }}" @selected(old('status', 'active') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </section>

            <section data-tab-panel="guardian" class="hidden grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">{{ __('Existing guardian / parent') }}</label>
                    <select name="guardian_id" class="{{ $field }}">
                        <option value="">{{ __('None — enter details below') }}</option>
                        @foreach ($guardians as $g)
                            <option value="{{ $g->id }}" @selected(old('guardian_id') == $g->id)>{{ $g->user?->name }} ({{ $g->phone }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 border-t border-gray-100 pt-4">
                    <p class="mb-3 text-sm font-semibold text-gray-600">{{ __('Or create accompanying guardian') }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Parent name') }}</label>
                    <input name="parent_name" value="{{ old('parent_name') }}" class="{{ $field }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Parent phone') }}</label>
                    <input name="parent_phone" value="{{ old('parent_phone') }}" class="{{ $field }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Parent email') }}</label>
                    <input name="parent_email" type="email" value="{{ old('parent_email') }}" class="{{ $field }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Parent occupation') }}</label>
                    <input name="parent_occupation" value="{{ old('parent_occupation') }}" class="{{ $field }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">{{ __('Parent address') }}</label>
                    <textarea name="parent_address" rows="2" class="{{ $field }}">{{ old('parent_address') }}</textarea>
                </div>
            </section>

            <section data-tab-panel="address" class="hidden grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">{{ __('Present address') }} *</label>
                    <textarea name="present_address" rows="2" required class="{{ $field }}">{{ old('present_address') }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">{{ __('Permanent address') }}</label>
                    <textarea name="permanent_address" rows="2" class="{{ $field }}">{{ old('permanent_address') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('City') }}</label>
                    <input name="city" value="{{ old('city') }}" class="{{ $field }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('State / Division') }}</label>
                    <input name="state" value="{{ old('state') }}" class="{{ $field }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('ZIP / Post code') }}</label>
                    <input name="zip_code" value="{{ old('zip_code') }}" class="{{ $field }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Country') }}</label>
                    <input name="country" value="{{ old('country') }}" class="{{ $field }}">
                </div>
            </section>

            <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
                <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save student') }}</button>
            </div>
        </form>
    </div>
    @include('dashboard.partials.inline-validation')
@endsection