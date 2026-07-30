@extends('layouts.dashboard')
@section('title', __('dashboard.book_categories') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('dashboard.book_categories') }}</h1>
</div>

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    @include('dashboard.partials.form-errors')
    <form method="post" action="{{ route('dashboard.library.categories.store') }}" class="flex flex-wrap items-end gap-3">
        @csrf
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('dashboard.category') }} *</label>
            <input name="name" placeholder="{{ __('Category name') }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Description') }}</label>
            <input name="description" placeholder="{{ __('Description (optional)') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Add') }}</button>
    </form>
</div>

<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('dashboard.category') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Description') }}</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">{{ __('dashboard.books') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($categories as $cat)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">{{ $cat->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $cat->description ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">{{ $cat->books_count }}</td>
                    <td class="px-4 py-3">
                        <button type="button" onclick="editCategory({{ $cat->id }}, '{{ $cat->name }}', '{{ $cat->description }}')" class="text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</button>
                        <form method="post" action="{{ route('dashboard.library.categories.destroy', $cat) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                            @csrf @method('delete')
                            <button type="submit" class="ml-2 text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">{{ __('No categories found.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">{{ __('Edit category') }}</h3>
        <form method="post" id="editForm" class="space-y-4">
            @csrf @method('put')
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.category') }} *</label>
                <input name="name" id="editName" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Description') }}</label>
                <input name="description" id="editDescription" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Cancel') }}</button>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Update') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(id, name, description) {
    document.getElementById('editName').value = name;
    document.getElementById('editDescription').value = description;
    document.getElementById('editForm').action = '{{ url("dashboard/library/categories") }}/' + id;
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
}
</script>
@endsection
