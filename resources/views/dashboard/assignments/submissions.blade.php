@extends('layouts.dashboard')
@section('title', __('Submissions') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div><h1 class="text-2xl font-bold text-gray-900">{{ __('Submissions') }}</h1><p class="text-sm text-gray-500">{{ $assignment->title }} ({{ $assignment->subject?->name }})</p></div>
    <a href="{{ route('dashboard.assignments.show', $assignment) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back') }}</a>
</div>
<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Student') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Submitted at') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Status') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Marks') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Guardian Notes') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Actions') }}</th></tr></thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($assignment->submissions as $sub)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $sub->student?->user?->name ?? 'N/A' }}</td>
                    <td class="px-4 py-3">{{ $sub->submitted_at?->format('d M Y H:i') ?? __('Not submitted') }}</td>
                    <td class="px-4 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $sub->status === 'graded' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">{{ __(ucfirst(str_replace('_', ' ', $sub->status))) }}</span></td>
                    <td class="px-4 py-3">{{ $sub->marks ?? '-' }} / {{ $assignment->total_marks ?? '-' }}</td>
                    <td class="px-4 py-3 max-w-xs">
                        @if($sub->guardian_notes)
                            <div class="text-xs text-gray-600">
                                <p class="italic">"{{ $sub->guardian_notes }}"</p>
                                @if($sub->guardian)
                                    <p class="mt-0.5 text-gray-400">— {{ $sub->guardian->user?->name ?? __('Guardian') }}</p>
                                @endif
                            </div>
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($sub->status !== 'graded')
                            <form method="post" action="{{ route('dashboard.assignments.grade', $sub) }}" class="flex items-center gap-2">
                                @csrf
                                <input type="number" name="marks" placeholder="{{ __('Marks') }}" max="{{ $assignment->total_marks }}" class="w-20 rounded border border-gray-300 px-2 py-1 text-xs" required>
                                <input name="feedback" placeholder="{{ __('Feedback') }}" class="w-32 rounded border border-gray-300 px-2 py-1 text-xs">
                                <button type="submit" class="rounded bg-green-600 px-2 py-1 text-xs font-medium text-white hover:bg-green-700">{{ __('Grade') }}</button>
                            </form>
                        @else
                            <span class="text-xs text-gray-500">{{ $sub->feedback }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">{{ __('No submissions yet.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection