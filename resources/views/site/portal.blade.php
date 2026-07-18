@extends('layouts.app')

@section('title', site_ui('portal.page_title') . ' — ' . ($siteSettings->site_name ?? config('app.name')))

@section('content')
    <div class="bg-white">
        <div class="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center gap-4 md:flex-row md:justify-between">
                    <div>
                        <h1 class="text-4xl font-bold md:text-5xl">{{ site_ui('portal.hero_title') }}</h1>
                        <p class="mt-2 text-lg text-blue-100">{{ site_ui('portal.hero_subtitle_prefix') }} {{ $user->name }}</p>
                    </div>
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white/10 text-2xl font-bold text-white ring-2 ring-white/30">
                        {{ \Illuminate\Support\Str::substr($user->name, 0, 1) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            {{-- Welcome & quick stats --}}
            <div class="grid gap-6 lg:grid-cols-4 reveal">
                <div class="lg:col-span-2 rounded-2xl border border-slate-100 bg-gradient-to-br from-blue-50 to-indigo-50 p-6 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-xl font-bold text-white shadow-lg">
                            {{ \Illuminate\Support\Str::substr($user->name, 0, 1) }}
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">{{ __('Welcome back, :name', ['name' => $user->name]) }}</h2>
                            <p class="text-sm text-slate-500">
                                @if($user->hasRole('student') && $student)
                                    {{ $student->class?->name ?? '' }} · {{ __('Roll') }}: {{ $student->roll_number ?? $student->roll_no ?? '—' }}
                                @elseif($user->hasRole('parent'))
                                    {{ __('Parent Account') }}
                                @else
                                    {{ $user->email }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-100 text-green-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900">{{ $recentAttendance->where('status', 'present')->count() }}/{{ $recentAttendance->count() ?: '—' }}</p>
                            <p class="text-xs text-slate-500">{{ __('Attendance') }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-100 text-orange-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900">{{ $upcomingEvents->count() }}</p>
                            <p class="text-xs text-slate-500">{{ __('Upcoming Exams') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="mt-10 reveal">
                <div class="border-b border-slate-200" data-portal-tabs>
                    <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Tabs">
                        <button type="button" data-tab="profile" class="whitespace-nowrap border-b-2 border-blue-600 px-1 py-4 text-sm font-semibold text-blue-600 transition">{{ site_ui('portal.section_profile') }}</button>
                        <button type="button" data-tab="attendance" class="whitespace-nowrap border-b-2 border-transparent px-1 py-4 text-sm font-medium text-slate-500 transition hover:text-slate-700">{{ __('Attendance') }}</button>
                        <button type="button" data-tab="exams" class="whitespace-nowrap border-b-2 border-transparent px-1 py-4 text-sm font-medium text-slate-500 transition hover:text-slate-700">{{ __('Exams') }}</button>
                        <button type="button" data-tab="fees" class="whitespace-nowrap border-b-2 border-transparent px-1 py-4 text-sm font-medium text-slate-500 transition hover:text-slate-700">{{ __('Fees') }}</button>
                    </nav>
                </div>

                {{-- Tab: Profile --}}
                <div data-tab-panel="profile" class="mt-8">
                    <div class="grid gap-6 lg:grid-cols-2">
                        @if($user->hasRole('student') && $student)
                            <section class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                                <h2 class="text-lg font-semibold text-slate-900">{{ site_ui('portal.section_profile') }}</h2>
                                <dl class="mt-4 space-y-3 text-sm">
                                    <div class="flex justify-between gap-4 border-b border-slate-50 pb-2"><dt class="text-slate-500">{{ site_ui('portal.label_class') }}</dt><dd class="font-medium text-slate-900">{{ $student->class?->name ?? '—' }}</dd></div>
                                    <div class="flex justify-between gap-4 border-b border-slate-50 pb-2"><dt class="text-slate-500">{{ site_ui('portal.label_section') }}</dt><dd class="font-medium text-slate-900">{{ $student->section?->name ?? '—' }}</dd></div>
                                    <div class="flex justify-between gap-4 border-b border-slate-50 pb-2"><dt class="text-slate-500">{{ site_ui('portal.label_roll') }}</dt><dd class="font-medium text-slate-900">{{ $student->roll_number ?? $student->roll_no ?? '—' }}</dd></div>
                                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Email') }}</dt><dd class="font-medium text-slate-900">{{ $user->email }}</dd></div>
                                </dl>
                            </section>
                        @endif

                        @if($user->hasRole('parent') && $children->isNotEmpty())
                            <section class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                                <h2 class="text-lg font-semibold text-slate-900">{{ site_ui('portal.section_linked') }}</h2>
                                <ul class="mt-4 space-y-3">
                                    @foreach ($children as $child)
                                        <li class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600">
                                                {{ \Illuminate\Support\Str::substr($child->user?->name ?? 'S', 0, 1) }}
                                            </div>
                                            <div>
                                                <p class="font-medium text-slate-900">{{ $child->user?->name ?? site_ui('portal.fallback_student') }}</p>
                                                <p class="text-xs text-slate-500">{{ $child->class?->name ?? '' }} {{ $child->section?->name ?? '' }}</p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </section>
                        @endif

                        {{-- Recent activity --}}
                        <section class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                            <h2 class="text-lg font-semibold text-slate-900">{{ __('Recent Activity') }}</h2>
                            <div class="mt-4 space-y-3">
                                <div class="flex items-center gap-3 text-sm">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-slate-700">{{ __('Logged in') }}</p>
                                        <p class="text-xs text-slate-400">{{ now()->format('M j, Y g:i A') }}</p>
                                    </div>
                                </div>
                                @if($examResults->isNotEmpty())
                                    <div class="flex items-center gap-3 text-sm">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 text-green-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-slate-700">{{ __('Results updated') }}</p>
                                            <p class="text-xs text-slate-400">{{ $examResults->first()->updated_at?->format('M j, Y') }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </section>
                    </div>
                </div>

                {{-- Tab: Attendance --}}
                <div data-tab-panel="attendance" class="mt-8 hidden">
                    <section class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-900">{{ site_ui('portal.section_attendance') }}</h2>
                        @if($recentAttendance->isEmpty())
                            <p class="mt-4 text-sm text-slate-600">{{ site_ui('portal.no_attendance') }}</p>
                        @else
                            <div class="mt-4 overflow-hidden rounded-xl border border-slate-100">
                                <table class="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        <tr>
                                            <th class="px-4 py-3">{{ __('Date') }}</th>
                                            <th class="px-4 py-3">{{ __('Status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50 bg-white">
                                        @foreach ($recentAttendance as $row)
                                            <tr class="hover:bg-slate-50 transition-colors">
                                                <td class="px-4 py-2.5 text-slate-700">{{ $row->date?->format('M j, Y') }}</td>
                                                <td class="px-4 py-2.5">
                                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold
                                                        {{ $row->status === 'present' ? 'bg-green-100 text-green-800' : ($row->status === 'absent' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                        <span class="h-1.5 w-1.5 rounded-full {{ $row->status === 'present' ? 'bg-green-500' : ($row->status === 'absent' ? 'bg-red-500' : 'bg-yellow-500') }}"></span>
                                                        {{ ucfirst($row->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>
                </div>

                {{-- Tab: Exams --}}
                <div data-tab-panel="exams" class="mt-8 hidden">
                    <div class="grid gap-6 lg:grid-cols-2">
                        <section class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                            <h2 class="text-lg font-semibold text-slate-900">{{ site_ui('portal.section_exams') }}</h2>
                            @if($examResults->isEmpty())
                                <p class="mt-4 text-sm text-slate-600">{{ site_ui('portal.no_results') }}</p>
                            @else
                                <div class="mt-4 space-y-2">
                                    @foreach ($examResults as $r)
                                        <div class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3 transition hover:bg-slate-50">
                                            <div>
                                                <p class="text-sm font-medium text-slate-900">{{ $r->exam?->name ?? __('Exam') }}</p>
                                                <p class="text-xs text-slate-500">{{ $r->subject?->name ?? '' }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-bold text-slate-900">{{ $r->obtained_marks ?? '—' }}<span class="text-xs font-normal text-slate-400">/{{ $r->exam?->total_marks ?? '—' }}</span></p>
                                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ ($r->grade ?? '') === 'A+' || ($r->grade ?? '') === 'A' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-700' }}">{{ $r->grade ?? '—' }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </section>

                        <section class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                            <h2 class="text-lg font-semibold text-slate-900">{{ site_ui('portal.section_progress') }}</h2>
                            @if($examResults->isEmpty())
                                <p class="mt-4 text-sm text-slate-600">{{ site_ui('portal.no_results') }}</p>
                            @else
                                @php $avg = $examResults->pluck('gpa')->filter(fn($v) => is_numeric($v))->avg(); @endphp
                                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center">
                                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ site_ui('portal.progress_counted') }}</div>
                                        <div class="mt-2 text-2xl font-bold text-slate-900">{{ $examResults->count() }}</div>
                                    </div>
                                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center">
                                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ site_ui('portal.progress_avg_gpa') }}</div>
                                        <div class="mt-2 text-2xl font-bold text-slate-900">{{ $avg ? number_format((float)$avg, 2) : '—' }}</div>
                                    </div>
                                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center">
                                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ site_ui('portal.progress_latest_grade') }}</div>
                                        <div class="mt-2 text-2xl font-bold text-slate-900">{{ $examResults->first()->grade ?? '—' }}</div>
                                    </div>
                                </div>
                                <p class="mt-3 text-xs text-slate-500">{{ site_ui('portal.progress_note') }}</p>
                                <a href="{{ route('portal.progress') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800">{{ site_ui('portal.progress_link') }} →</a>
                            @endif
                        </section>
                    </div>
                </div>

                {{-- Tab: Fees --}}
                <div data-tab-panel="fees" class="mt-8 hidden">
                    <section class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-900">{{ site_ui('portal.section_fees') }}</h2>
                        @if($feePayments->isEmpty())
                            <p class="mt-4 text-sm text-slate-600">{{ site_ui('portal.no_fee_payments') }}</p>
                        @else
                            <div class="mt-4 space-y-2">
                                @foreach ($feePayments->take(8) as $fp)
                                    <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-100 px-4 py-3 transition hover:bg-slate-50">
                                        <div>
                                            <p class="text-xs font-mono text-slate-500">{{ $fp->invoice_number }}</p>
                                            <p class="text-xs text-slate-400">{{ $fp->created_at?->format('M j, Y') }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-slate-900">৳ {{ number_format((float) $fp->paid_amount, 2) }}</p>
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $fp->status === 'paid' ? 'bg-green-100 text-green-800' : ($fp->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">{{ ucfirst($fp->status) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <a href="{{ route('site.payments') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800">{{ site_ui('portal.full_payment_portal') }} →</a>
                        @endif
                    </section>
                </div>
            </div>

            {{-- Assignments --}}
            @if($user->hasRole('student') && $assignments->isNotEmpty())
                <section class="mt-10 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm reveal">
                    <h2 class="text-lg font-semibold text-slate-900">{{ site_ui('portal.section_homework') }}</h2>
                    <div class="mt-4 divide-y divide-slate-100">
                        @foreach ($assignments as $a)
                            <div class="flex items-center justify-between gap-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-slate-900">{{ $a->title }}</p>
                                    @if($a->due_date)
                                        <p class="text-xs text-slate-500">{{ site_ui('portal.due_label') }}: {{ $a->due_date->format('M j, Y') }}</p>
                                    @endif
                                </div>
                                @if($a->due_date)
                                    <span class="shrink-0 text-xs font-semibold {{ $a->due_date->isPast() ? 'text-red-600' : 'text-blue-600' }}">
                                        {{ $a->due_date->isPast() ? __('Overdue') : $a->due_date->diffForHumans() }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Announcements --}}
            <section class="mt-10 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm reveal">
                <h2 class="text-lg font-semibold text-slate-900">{{ site_ui('portal.section_communication') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ site_ui('portal.communication_intro') }}</p>
                @if(($announcements ?? collect())->isEmpty())
                    <p class="mt-4 text-sm text-slate-600">{{ site_ui('portal.no_announcements') }}</p>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach ($announcements as $a)
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <div class="font-semibold text-slate-900">{{ $a->title }}</div>
                                    <div class="text-xs text-slate-500">{{ ($a->starts_at ?? $a->created_at)?->format('M j, Y') }}</div>
                                </div>
                                @if($a->body)
                                    <div class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $a->body }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
