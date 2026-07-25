<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Hostel Name') }}</label>
            <input name="name" value="{{ old('name', $hostel->name) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Address') }}</label>
            <input name="address" value="{{ old('address', $hostel->address) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Description') }}</label>
            <textarea name="description" rows="4" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('description', $hostel->description) }}</textarea>
        </div>
    </div>

    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Total Rooms') }}</label>
                <input name="total_rooms" type="number" min="0" value="{{ old('total_rooms', $hostel->total_rooms) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Warden Name') }}</label>
                <input name="warden_name" value="{{ old('warden_name', $hostel->warden_name) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Warden Phone') }}</label>
                <input name="warden_phone" value="{{ old('warden_phone', $hostel->warden_phone) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Status') }}</label>
                <select name="status" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="active" @selected(old('status', $hostel->status) === 'active')>{{ __('Active') }}</option>
                    <option value="inactive" @selected(old('status', $hostel->status) === 'inactive')>{{ __('Inactive') }}</option>
                </select>
            </div>
        </div>
    </div>
</div>
