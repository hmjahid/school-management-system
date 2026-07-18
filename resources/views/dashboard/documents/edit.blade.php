@extends('layouts.dashboard')

@section('title', __('Edit document') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Edit document') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $document->title }}</p>
        </div>
        <a href="{{ route('dashboard.documents.index') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ __('Back') }}</a>
    </div>

    <div class="flex justify-end mb-4">
        <form method="post" action="{{ route('dashboard.documents.destroy', $document) }}" onsubmit="return confirm('{{ __('Delete this item?') }}')">
            @csrf
            @method('delete')
            <button class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-900 hover:bg-red-100">{{ __('Delete') }}</button>
        </form>
    </div>

    <form method="post" action="{{ route('dashboard.documents.update', $document) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('put')
        @include('dashboard.partials.form-errors')
        @include('dashboard.documents._form', ['document' => $document])
        <button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save changes') }}</button>
    </form>
@endsection

