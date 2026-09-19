@extends('layouts.dashboard')

@section('title', __('Compose Message') . ' — ' . config('app.name', 'School'))

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Compose Message') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Send a message to another user.') }}</p>
        </div>
        <a href="{{ route('messages.index') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ __('Back to Inbox') }}</a>
    </div>

    <form method="post" action="{{ route('messages.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Target Role') }}</label>
                <div class="flex flex-wrap gap-2" id="role-pills">
                    <button type="button" data-role="" class="role-pill rounded-full px-4 py-1.5 text-sm font-medium border border-gray-300 text-gray-600 hover:bg-gray-100 bg-gray-100">
                        {{ __('All') }}
                    </button>
                    @foreach($roles as $role)
                        <button type="button" data-role="{{ $role->name }}" class="role-pill rounded-full px-4 py-1.5 text-sm font-medium border border-gray-300 text-gray-600 hover:bg-gray-100">
                            {{ ucfirst($role->name) }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div>
                <label for="receiver_id" class="block text-sm font-medium text-gray-700">{{ __('Recipient') }}</label>
                <select id="receiver_id" name="receiver_id" required
                    class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="">{{ __('Select recipient...') }}</option>
                </select>
                @error('receiver_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="subject" class="block text-sm font-medium text-gray-700">{{ __('Subject') }}</label>
                <input id="subject" name="subject" type="text" value="{{ old('subject', $replyTo ? 'Re: ' . $replyTo->subject : '') }}" required
                    class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                @error('subject')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="body" class="block text-sm font-medium text-gray-700">{{ __('Message') }}</label>
                <textarea id="body" name="body" rows="8" required
                    class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">{{ old('body', $replyTo ? "\n\n--- Original Message ---\nFrom: {$replyTo->sender->name}\nSubject: {$replyTo->subject}\n\n{$replyTo->body}" : '') }}</textarea>
                @error('body')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                {{ __('Send Message') }}
            </button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    const allUsers = @json($allUsers);
    const receiverSelect = document.getElementById('receiver_id');
    const rolePills = document.querySelectorAll('.role-pill');
    let selectedRole = '';

    function filterUsers() {
        const options = allUsers.filter(u => {
            if (!selectedRole) return true;
            return u.role_names.toLowerCase().includes(selectedRole.toLowerCase());
        });
        receiverSelect.innerHTML = '<option value="">{{ __('Select recipient...') }}</option>';
        options.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.textContent = u.name + ' (' + u.role_names + ')';
            receiverSelect.appendChild(opt);
        });
    }

    rolePills.forEach(pill => {
        pill.addEventListener('click', function () {
            rolePills.forEach(p => p.classList.remove('bg-gray-100'));
            this.classList.add('bg-gray-100');
            selectedRole = this.dataset.role;
            filterUsers();
        });
    });

    filterUsers();
</script>
@endpush
