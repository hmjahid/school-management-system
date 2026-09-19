@extends('layouts.dashboard')

@section('title', $hostel->name . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $hostel->name }}</h1>
            <p class="mt-1 text-sm text-gray-600">
                @if($hostel->warden_name){{ __('Warden:') }} {{ $hostel->warden_name }} &middot; @endif
                <x-badge :variant="$hostel->status === 'active' ? 'success' : 'default'">{{ ucfirst($hostel->status) }}</x-badge>
            </p>
        </div>
        <a href="{{ route('dashboard.hostels.index') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ __('Back') }}</a>
    </div>

    {{-- Rooms --}}
    <div class="mb-8 rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Rooms') }} ({{ $hostel->rooms->count() }})</h2>
        </div>

        <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($hostel->rooms as $room)
                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="mb-3 flex items-start justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ __('Room') }} {{ $room->room_number }}</h3>
                            <p class="text-xs text-gray-500">{{ $room->room_type ?? __('Standard') }}</p>
                        </div>
                        <x-badge :variant="$room->status === 'available' ? 'success' : ($room->status === 'maintenance' ? 'warning' : 'brand')">
                            {{ ucfirst($room->status) }}
                        </x-badge>
                    </div>
                    <div class="mb-3 flex items-center gap-4 text-sm text-gray-600">
                        <span>{{ __('Capacity:') }} {{ $room->capacity }}</span>
                        <span>{{ __('Occupied:') }} {{ $room->occupied }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="post" action="{{ route('dashboard.hostels.rooms.destroy', $room) }}" data-confirm="{{ __('Delete room?') }}">
                            @csrf
                            @method('delete')
                            <button class="text-xs font-semibold text-red-600 hover:underline">{{ __('Delete') }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border-2 border-dashed border-slate-200 p-12">
                    <x-empty-state :title="__('No rooms yet')" :message="__('Add your first room using the form below.')" icon="inbox" />
                </div>
            @endforelse
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Add Room Form --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">{{ __('Add Room') }}</h3>
            <form method="post" action="{{ route('dashboard.hostels.rooms.store', $hostel) }}" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Room Number') }}</label>
                        <input name="room_number" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="e.g. 101">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Room Type') }}</label>
                        <input name="room_type" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="e.g. Single, Double">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Capacity') }}</label>
                        <input name="capacity" type="number" min="1" value="1" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Status') }}</label>
                        <select name="status" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="available">{{ __('Available') }}</option>
                            <option value="occupied">{{ __('Occupied') }}</option>
                            <option value="maintenance">{{ __('Maintenance') }}</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 focus:ring-brand-500">{{ __('Add Room') }}</button>
            </form>
        </div>

        {{-- Assign Student Form --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">{{ __('Assign Student') }}</h3>
            <form method="post" action="{{ route('dashboard.hostels.assignments.store', $hostel) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Student') }}</label>
                    <select name="student_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="">{{ __('Select student...') }}</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}">{{ $student->user->name ?? '#' . $student->id }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Room') }}</label>
                    <select name="room_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="">{{ __('Select room...') }}</option>
                        @foreach($hostel->rooms as $room)
                            <option value="{{ $room->id }}">{{ __('Room') }} {{ $room->room_number }} ({{ $room->capacity - $room->occupied }} {{ __('slots available') }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Check-in Date') }}</label>
                        <input name="check_in_date" type="date" value="{{ date('Y-m-d') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Check-out Date') }}</label>
                        <input name="check_out_date" type="date" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Notes') }}</label>
                    <textarea name="notes" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea>
                </div>
                <button type="submit" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">{{ __('Assign Student') }}</button>
            </form>
        </div>
    </div>
@endsection
