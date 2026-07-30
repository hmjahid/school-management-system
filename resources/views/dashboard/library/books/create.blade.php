@extends('layouts.dashboard')
@section('title', __('dashboard.add_book') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('dashboard.add_book') }}</h1>
    <a href="{{ route('dashboard.library.books.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back') }}</a>
</div>
@include('dashboard.partials.form-errors')
<form method="post" action="{{ route('dashboard.library.books.store') }}" enctype="multipart/form-data" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    @csrf
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.title') }} *</label>
            <input name="title" value="{{ old('title') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.author') }}</label>
            <input name="author" value="{{ old('author') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.publisher') }}</label>
            <input name="publisher" value="{{ old('publisher') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.isbn') }}</label>
            <input name="isbn" value="{{ old('isbn') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.category') }}</label>
            <select name="category_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">{{ __('Select category') }}</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.shelf_location') }}</label>
            <input name="shelf_location" value="{{ old('shelf_location') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.quantity') }} *</label>
            <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.purchase_date') }}</label>
            <input type="date" name="purchase_date" value="{{ old('purchase_date') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.price') }}</label>
            <input type="number" step="0.01" name="price" value="{{ old('price') }}" min="0" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.cover_image') }}</label>
            <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div class="flex items-center gap-3 sm:col-span-2">
            <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" name="status" value="1" @checked(old('status', true)) class="peer sr-only">
                <div class="h-6 w-11 rounded-full bg-gray-300 peer-checked:bg-blue-600 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-full"></div>
            </label>
            <span class="text-sm font-medium text-gray-700">{{ __('Active') }}</span>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">{{ __('Description') }}</label>
        <textarea name="description" rows="4" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('description') }}</textarea>
    </div>
    <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save') }}</button>
</form>
@endsection
