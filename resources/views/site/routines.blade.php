@extends('layouts.app')
@section('title', __('Class routine') . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold md:text-5xl">{{ __('Class Routine') }}</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-blue-100">{{ __('View the weekly class schedule') }}</p>
        </div>
    </div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <form method="get" class="mb-8 flex flex-wrap gap-4">
            <select name="class_id" class="rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm">
                <option value="">{{ __('Select class') }}</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
            <select name="section_id" class="rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm">
                <option value="">{{ __('All sections') }}</option>
                @foreach($sections as $s)
                    <option value="{{ $s->id }}" @selected($sectionId == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700 shadow-sm">{{ __('View routine') }}</button>
        </form>

        @if($routines->isEmpty())
            <div class="rounded-xl border-2 border-dashed border-gray-300 p-12 text-center">
                <p class="text-gray-500">{{ __('Select a class to view the routine.') }}</p>
            </div>
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach([1,2,3,4,5,6,7] as $day)
                    @if(isset($routines[$day]))
                        <div class="rounded-xl bg-white p-6 shadow-md">
                            <h3 class="mb-4 text-lg font-bold text-blue-800">{{ __(App\Models\Routine::DAYS[$day]) }}</h3>
                            <div class="space-y-3">
                                @foreach($routines[$day] as $r)
                                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3 text-sm">
                                        <p class="font-semibold text-gray-900">{{ $r->subject?->name }}</p>
                                        <p class="text-xs text-gray-500">{{ substr($r->start_time, 0, 5) }} - {{ substr($r->end_time, 0, 5) }}</p>
                                        <p class="text-xs text-gray-400">{{ $r->teacher?->user?->name }} @if($r->room_number) | {{ __('Room') }} {{ $r->room_number }} @endif</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection