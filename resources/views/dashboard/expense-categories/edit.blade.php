@extends('layouts.dashboard')

@section('title', __('Edit category') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Edit category')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Expense categories'), 'url' => route('dashboard.expense-categories.index')],
                ['label' => __('Edit')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <x-card>
        @include('dashboard.partials.form-errors')
        <form method="post" action="{{ route('dashboard.expense-categories.update', $category) }}" class="space-y-5">
            @csrf @method('put')
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Name') }}</label>
                    <input type="text" name="name" required maxlength="64" value="{{ old('name', $category->name) }}" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Color') }}</label>
                    <input type="text" name="color" maxlength="16" value="{{ old('color', $category->color) }}" class="admin-input" placeholder="#3b82f6">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Description') }}</label>
                <textarea name="description" rows="3" class="admin-input">{{ old('description', $category->description) }}</textarea>
            </div>
            <div>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active)) class="admin-input">
                    {{ __('Active') }}
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <x-button :href="route('dashboard.expense-categories.index')" variant="ghost">{{ __('Cancel') }}</x-button>
                <x-button type="submit">{{ __('Update') }}</x-button>
            </div>
        </form>
    </x-card>
@endsection
