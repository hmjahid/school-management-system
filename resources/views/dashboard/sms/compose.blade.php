@extends('layouts.dashboard')

@section('title', __('Compose SMS') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Compose SMS')" :description="__('Choose your recipients and write your message.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Bulk SMS'), 'url' => route('dashboard.sms.index')],
                ['label' => __('Compose')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <form method="post" action="{{ route('dashboard.sms.preview') }}">
        @csrf
        <x-card class="space-y-5">
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Campaign name') }}</label>
                <input type="text" name="name" required maxlength="191" class="admin-input" placeholder="e.g. Notice for Class 6">
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Send to') }}</label>
                <div class="flex flex-wrap gap-2 mb-3">
                    <label class="cursor-pointer rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-700 dark:border-slate-600 dark:text-slate-300 dark:has-[:checked]:border-brand-400 dark:has-[:checked]:bg-brand-900/20 dark:has-[:checked]:text-brand-400">
                        <input type="radio" name="audience_type" value="all_users" class="sr-only" checked onchange="toggleSmsFields()">
                        {{ __('All website users') }}
                    </label>
                    <label class="cursor-pointer rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-700 dark:border-slate-600 dark:text-slate-300 dark:has-[:checked]:border-brand-400 dark:has-[:checked]:bg-brand-900/20 dark:has-[:checked]:text-brand-400">
                        <input type="radio" name="audience_type" value="students_class" class="sr-only" onchange="toggleSmsFields()">
                        {{ __('Students by class') }}
                    </label>
                    <label class="cursor-pointer rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-700 dark:border-slate-600 dark:text-slate-300 dark:has-[:checked]:border-brand-400 dark:has-[:checked]:bg-brand-900/20 dark:has-[:checked]:text-brand-400">
                        <input type="radio" name="audience_type" value="students_section" class="sr-only" onchange="toggleSmsFields()">
                        {{ __('Students by section') }}
                    </label>
                    <label class="cursor-pointer rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-700 dark:border-slate-600 dark:text-slate-300 dark:has-[:checked]:border-brand-400 dark:has-[:checked]:bg-brand-900/20 dark:has-[:checked]:text-brand-400">
                        <input type="radio" name="audience_type" value="students_individual" class="sr-only" onchange="toggleSmsFields()">
                        {{ __('Individual students') }}
                    </label>
                    <label class="cursor-pointer rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-700 dark:border-slate-600 dark:text-slate-300 dark:has-[:checked]:border-brand-400 dark:has-[:checked]:bg-brand-900/20 dark:has-[:checked]:text-brand-400">
                        <input type="radio" name="audience_type" value="staff_role" class="sr-only" onchange="toggleSmsFields()">
                        {{ __('Staff by role') }}
                    </label>
                    <label class="cursor-pointer rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-700 dark:border-slate-600 dark:text-slate-300 dark:has-[:checked]:border-brand-400 dark:has-[:checked]:bg-brand-900/20 dark:has-[:checked]:text-brand-400">
                        <input type="radio" name="audience_type" value="staff_individual" class="sr-only" onchange="toggleSmsFields()">
                        {{ __('Individual staff') }}
                    </label>
                </div>
            </div>

            <div id="sms-class-row" class="hidden">
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Class') }}</label>
                <select name="school_class_id" class="admin-select">
                    <option value="">{{ __('All classes') }}</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div id="sms-section-row" class="hidden">
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Section') }}</label>
                <select name="section_id" class="admin-select">
                    <option value="">{{ __('All sections') }}</option>
                    @foreach($sections as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <div id="sms-role-row" class="hidden">
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Staff role') }}</label>
                <select name="role_name" class="admin-select">
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                    @endforeach
                </select>
            </div>

            <div id="sms-students-row" class="hidden">
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Select students') }}</label>
                <div class="max-h-48 overflow-y-auto rounded-lg border border-slate-200 p-2 dark:border-slate-700">
                    @foreach($students as $s)
                        <label class="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">
                            <input type="checkbox" name="user_ids[]" value="{{ $s['user_id'] }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600">
                            <span>{{ $s['name'] }}</span>
                            <span class="text-xs text-slate-400">({{ $s['phone'] }})</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div id="sms-staff-row" class="hidden">
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Select staff') }}</label>
                <div class="mb-2 flex flex-wrap gap-2">
                    @foreach($roles->whereIn('name', ['admin', 'teacher', 'staff']) as $role)
                        <button type="button" data-sms-role="{{ $role->name }}" class="sms-role-pill rounded-full border border-slate-300 px-3 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-400">
                            {{ ucfirst($role->name) }}
                        </button>
                    @endforeach
                </div>
                <div class="max-h-48 overflow-y-auto rounded-lg border border-slate-200 p-2 dark:border-slate-700">
                    @foreach($users as $u)
                        <label class="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-slate-50 dark:hover:bg-slate-700 sms-user-item" data-sms-roles="{{ strtolower($u['role_names']) }}">
                            <input type="checkbox" name="user_ids[]" value="{{ $u['id'] }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600">
                            <span>{{ $u['name'] }}</span>
                            <span class="text-xs text-slate-400">({{ $u['role_names'] }})</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Message') }}</label>
                <textarea name="message" required rows="5" maxlength="1000" class="admin-input" placeholder="Dear parents, …"></textarea>
                <p class="mt-1 text-xs text-slate-500">{{ __('Standard SMS is 160 chars; longer messages will be split.') }}</p>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Schedule (optional)') }}</label>
                <input type="datetime-local" name="scheduled_at" class="admin-input">
                <p class="mt-1 text-xs text-slate-500">{{ __('Leave empty to send immediately.') }}</p>
            </div>

            <div class="flex justify-end gap-2">
                <x-button :href="route('dashboard.sms.index')" variant="ghost">{{ __('Cancel') }}</x-button>
                <x-button type="submit">{{ __('Preview recipients') }}</x-button>
            </div>
        </x-card>
    </form>
@endsection

@push('scripts')
<script>
function toggleSmsFields() {
    const v = document.querySelector('input[name="audience_type"]:checked')?.value || 'all_users';
    document.getElementById('sms-class-row').classList.toggle('hidden', v !== 'students_class');
    document.getElementById('sms-section-row').classList.toggle('hidden', v !== 'students_section');
    document.getElementById('sms-role-row').classList.toggle('hidden', v !== 'staff_role');
    document.getElementById('sms-students-row').classList.toggle('hidden', v !== 'students_individual');
    document.getElementById('sms-staff-row').classList.toggle('hidden', v !== 'staff_individual');
}

document.querySelectorAll('.sms-role-pill').forEach(pill => {
    pill.addEventListener('click', function () {
        const role = this.dataset.smsRole;
        document.querySelectorAll('.sms-user-item').forEach(item => {
            const roles = item.dataset.smsRoles;
            item.style.display = roles.includes(role) ? '' : 'none';
        });
        document.querySelectorAll('.sms-role-pill').forEach(p => p.classList.remove('bg-slate-100'));
        this.classList.add('bg-slate-100');
    });
});

toggleSmsFields();
</script>
@endpush
