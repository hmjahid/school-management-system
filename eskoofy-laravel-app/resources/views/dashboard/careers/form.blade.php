@extends('layouts.dashboard')

@section('title', ($job->exists ? __('Edit position') : __('New position')) . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="$job->exists ? __('Edit position') : __('New position')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Careers'), 'url' => route('dashboard.careers.postings')],
                ['label' => $job->exists ? __('Edit') : __('New')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <x-card>
        <form method="post" action="{{ $job->exists ? route('dashboard.careers.update', $job) : route('dashboard.careers.store') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            @if ($job->exists)
                @method('PUT')
            @endif

            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Title') }}</label>
                <input name="title" value="{{ old('title', $job->title) }}" required class="admin-input w-full">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Type') }}</label>
                <select name="type" class="admin-select w-full">
                    @foreach (['full-time', 'part-time', 'contract', 'internship'] as $t)
                        <option value="{{ $t }}" @selected(old('type', $job->type) === $t)>{{ ucfirst(str_replace('-', ' ', $t)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Location') }}</label>
                <input name="location" value="{{ old('location', $job->location) }}" required class="admin-input w-full">
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Description') }}</label>
                <textarea name="description" rows="5" required class="admin-input w-full">{{ old('description', $job->description) }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Requirements') }}</label>
                <textarea name="requirements" rows="5" required class="admin-input w-full">{{ old('requirements', $job->requirements) }}</textarea>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Salary min') }}</label>
                <input type="number" step="0.01" name="salary_min" value="{{ old('salary_min', $job->salary_min) }}" class="admin-input w-full">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Salary max') }}</label>
                <input type="number" step="0.01" name="salary_max" value="{{ old('salary_max', $job->salary_max) }}" class="admin-input w-full">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Deadline') }}</label>
                <input type="date" name="deadline" value="{{ old('deadline', $job->deadline?->format('Y-m-d')) }}" required class="admin-input w-full">
            </div>
            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $job->is_published)) class="rounded border-slate-300">
                    {{ __('Published') }}
                </label>
            </div>

            <div class="flex justify-end gap-2 sm:col-span-2">
                <a href="{{ route('dashboard.careers.postings') }}" class="admin-button admin-button-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="admin-button">{{ $job->exists ? __('Save changes') : __('Create position') }}</button>
            </div>
        </form>
    </x-card>
@endsection