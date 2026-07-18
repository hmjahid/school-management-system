@extends('layouts.dashboard')

@section('title', __('Transport assignments') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Transport assignments')" :description="__('Assign students to a route. Transport fare is applied to the student automatically.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Transport')],
                ['label' => __('Assignments')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <x-card :title="__('New assignment')" class="mb-6">
        <form method="post" action="{{ route('dashboard.transport.assignments.store') }}" class="grid gap-4 sm:grid-cols-5">
            @csrf
            <select name="student_id" required class="admin-select sm:col-span-2">
                <option value="">{{ __('Student') }} —</option>
                @foreach($students as $s)
                    <option value="{{ $s->id }}">{{ $s->user?->name ?? $s->admission_number }}</option>
                @endforeach
            </select>
            <select name="route_id" required class="admin-select" onchange="const stops = JSON.parse(this.options[this.selectedIndex].dataset.stops || '[]'); const sel = document.getElementById('stop-select'); sel.innerHTML = '<option value=\"\">—</option>' + stops.map(s => `<option value=\"${s.id}\">${s.name}</option>`).join('')">
                <option value="">{{ __('Route') }} —</option>
                @foreach($routes as $r)
                    <option value="{{ $r->id }}" data-stops='@json($r->stops->map(fn($s) => ["id"=>$s->id,"name"=>$s->name]))'>{{ $r->code }} — {{ $r->name }} ({{ number_format((float) $r->fare, 2) }})</option>
                @endforeach
            </select>
            <select name="stop_id" id="stop-select" class="admin-select">
                <option value="">{{ __('Stop') }} —</option>
            </select>
            <input type="date" name="effective_from" required value="{{ now()->toDateString() }}" class="admin-input">
            <x-button type="submit" class="sm:col-span-5">{{ __('Assign') }}</x-button>
        </form>
    </x-card>

    <x-card :padding="false">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">{{ __('Student') }}</th>
                    <th class="px-4 py-3">{{ __('Route') }}</th>
                    <th class="px-4 py-3">{{ __('Stop') }}</th>
                    <th class="px-4 py-3">{{ __('From') }}</th>
                    <th class="px-4 py-3">{{ __('To') }}</th>
                    <th class="px-4 py-3">{{ __('Active') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $a)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $a->student?->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $a->route?->code }} — {{ $a->route?->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $a->stop?->name ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $a->effective_from->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $a->effective_to?->format('Y-m-d') ?: '—' }}</td>
                        <td class="px-4 py-3">
                            @if($a->isActive())
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">{{ __('Active') }}</span>
                            @else
                                <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-semibold text-slate-700">{{ __('Expired') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="post" action="{{ route('dashboard.transport.assignments.destroy', $a) }}" class="inline" onsubmit="return confirm('{{ __('Remove this assignment?') }}')">
                                @csrf @method('delete')
                                <button class="text-xs font-semibold text-red-700 hover:underline" type="submit">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No assignments yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($rows->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $rows->links() }}</div>
        @endif
    </x-card>
@endsection