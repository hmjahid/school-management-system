@extends('layouts.dashboard')

@section('title', __('CMS settings') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <x-page-header :title="__('CMS settings')" :description="__('Toggle visibility of homepage sections and sub-pages.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('CMS settings')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <form method="post" action="{{ route('dashboard.settings.update.cms') }}" class="max-w-3xl">
        @csrf
        <x-card :title="__('Section visibility moved')" class="mb-6">
            <p class="text-sm text-slate-500">
                {{ __('Per-section show/hide checkboxes are now managed on each page’s CMS edit screen (Website → CMS → Pages → edit a page).') }}
            </p>
        </x-card>

        <div class="flex justify-end">
            <x-button type="submit">{{ __('Save settings') }}</x-button>
        </div>
    </form>
@endsection
