@extends('layouts.dashboard')

@section('title', __('Document Designs') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Document Designs')" :description="__('Themes and watermarks for certificates, testimonials, marksheets, admit cards and ID cards.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Document Designs')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-button :href="route('dashboard.document-designs.create')">{{ __('New design') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-6">
        @foreach ($types as $type)
            @php($rows = $designs->get($type, collect()))
            <x-card :title="__('dashboard.document_type_'.$type)" :padding="false">
                <x-slot:header>
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">{{ __('dashboard.document_type_'.$type) }}</h2>
                            <p class="mt-0.5 text-xs text-slate-500">
                                @if ($active[$type])
                                    {{ __('Active: :name (:template)', ['name' => $active[$type]->name, 'template' => $active[$type]->template]) }}
                                @else
                                    {{ __('Using the shipped default look.') }}
                                @endif
                            </p>
                        </div>
                        <x-button :href="route('dashboard.document-designs.create', ['document_type' => $type])" size="sm" variant="secondary">
                            {{ __('Add for this type') }}
                        </x-button>
                    </div>
                </x-slot:header>

                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-2">{{ __('Name') }}</th>
                            <th class="px-4 py-2">{{ __('Template') }}</th>
                            <th class="px-4 py-2">{{ __('Watermark') }}</th>
                            <th class="px-4 py-2 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            <tr class="{{ $row->is_active ? '' : 'opacity-60' }}">
                                <td class="px-4 py-2">
                                    <span class="font-medium">{{ $row->name }}</span>
                                    @if ($row->is_default)
                                        <span class="ms-2 rounded-full bg-emerald-100 px-2 py-0.5 text-[0.65rem] font-semibold text-emerald-800">{{ __('default') }}</span>
                                    @endif
                                    @unless ($row->is_active)
                                        <span class="ms-2 rounded-full bg-slate-100 px-2 py-0.5 text-[0.65rem] font-semibold text-slate-600">{{ __('inactive') }}</span>
                                    @endunless
                                </td>
                                <td class="px-4 py-2 text-xs">{{ __('dashboard.document_template_'.$row->template) }}</td>
                                <td class="px-4 py-2 text-xs">
                                    {{ ! empty($row->watermark['enabled']) ? __('on') : __('off') }}
                                    @if (! empty($row->watermark['text']))
                                        <span class="text-slate-500">— {{ Str::limit((string) $row->watermark['text'], 30) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <x-button :href="route('dashboard.document-designs.edit', $row)" size="sm" variant="ghost">{{ __('Edit') }}</x-button>
                                    <form method="post" action="{{ route('dashboard.document-designs.destroy', $row) }}" class="inline" data-confirm="{{ __('Delete this design?') }}">
                                        @csrf @method('delete')
                                        <button class="text-xs font-semibold text-red-700 hover:underline" type="submit">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-xs text-slate-500">
                                    {{ __('No custom design for this type — the shipped default is used.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-card>
        @endforeach
    </div>
@endsection
