@extends('layouts.dashboard')

@section('title', __('Compose SMS') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Compose SMS')" :description="__('Choose the audience and write your message.')">
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
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Audience') }}</label>
                <select name="audience_type" class="admin-select" onchange="const v = this.value; document.getElementById('class-row').classList.toggle('hidden', v !== 'class'); document.getElementById('section-row').classList.toggle('hidden', v !== 'section')">
                    <option value="all">{{ __('Everyone (students + staff)') }}</option>
                    <option value="class">{{ __('By class') }}</option>
                    <option value="section">{{ __('By section') }}</option>
                    <option value="staff">{{ __('Staff only') }}</option>
                </select>
            </div>
            <div id="class-row" class="hidden">
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Class') }}</label>
                <select name="school_class_id" class="admin-select">
                    <option value="">{{ __('All classes') }}</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div id="section-row" class="hidden">
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Section') }}</label>
                <select name="section_id" class="admin-select">
                    <option value="">{{ __('All sections') }}</option>
                    @foreach($sections as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Message') }}</label>
                <textarea name="message" required rows="5" maxlength="1000" class="admin-input" placeholder="Dear parents, …"></textarea>
                <p class="mt-1 text-xs text-slate-500">{{ __('Standard SMS is 160 chars; longer messages will be split.') }}</p>
            </div>
            <div class="flex justify-end gap-2">
                <x-button :href="route('dashboard.sms.index')" variant="ghost">{{ __('Cancel') }}</x-button>
                <x-button type="submit">{{ __('Preview recipients') }}</x-button>
            </div>
        </x-card>
    </form>
@endsection