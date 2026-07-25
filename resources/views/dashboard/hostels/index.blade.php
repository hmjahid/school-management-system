@extends('layouts.dashboard')

@section('title', __('Hostels') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Hostels') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Manage hostels, rooms, and student assignments.') }}</p>
        </div>
        <a href="{{ route('dashboard.hostels.create') }}"
            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            {{ __('Add Hostel') }}
        </a>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($hostels as $hostel)
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md">
                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $hostel->name }}</h3>
                        @if($hostel->address)
                            <p class="mt-1 text-sm text-gray-500">{{ $hostel->address }}</p>
                        @endif
                    </div>
                    <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $hostel->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst($hostel->status) }}
                    </span>
                </div>

                <div class="mb-4 grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-gray-50 p-3 text-center">
                        <p class="text-xl font-bold text-gray-900">{{ $hostel->rooms_count }}</p>
                        <p class="text-xs text-gray-500">{{ __('Rooms') }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 text-center">
                        <p class="text-xl font-bold text-gray-900">{{ $hostel->total_rooms ?? 0 }}</p>
                        <p class="text-xs text-gray-500">{{ __('Total Capacity') }}</p>
                    </div>
                </div>

                @if($hostel->warden_name)
                    <p class="mb-4 text-sm text-gray-600">
                        <span class="font-medium">{{ __('Warden:') }}</span> {{ $hostel->warden_name }}
                    </p>
                @endif

                <div class="flex items-center gap-2">
                    <a href="{{ route('dashboard.hostels.show', $hostel) }}"
                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        {{ __('View') }}
                    </a>
                    <a href="{{ route('dashboard.hostels.edit', $hostel) }}"
                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        {{ __('Edit') }}
                    </a>
                    <form method="post" action="{{ route('dashboard.hostels.destroy', $hostel) }}" onsubmit="return confirm('{{ __('Delete this hostel?') }}')">
                        @csrf
                        @method('delete')
                        <button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">{{ __('Delete') }}</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-gray-300 p-12 text-center">
                <p class="text-gray-500">{{ __('No hostels found.') }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $hostels->links() }}</div>
@endsection
