@php
    $allSections = __('dashboard.help.sections');
    $sectionKey = $dashboardHelpSection ?? 'getting_started';
    $section = $allSections[$sectionKey] ?? $allSections['getting_started'];
@endphp

<div id="help-modal-root" class="fixed inset-0 z-[95] hidden items-center justify-center p-4" aria-hidden="true">
    <div data-help-modal-backdrop class="modal-backdrop"></div>
    <div class="modal-panel max-h-[85vh] w-full max-w-2xl overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="help-modal-title">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 dark:border-slate-700">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-600 dark:bg-brand-900/40 dark:text-brand-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <div>
                    <h2 id="help-modal-title" class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $section['title'] }}</h2>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $section['description'] }}</p>
                </div>
            </div>
            <button type="button" data-help-modal-close class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-300" aria-label="{{ __('Close') }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="px-6 py-5">
            <ol class="space-y-3">
                @foreach($section['steps'] as $i => $step)
                    <li class="flex items-start gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">{{ $i + 1 }}</span>
                        <span class="pt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $step }}</span>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="border-t border-slate-100 px-6 py-4 dark:border-slate-700">
            <div class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ __('dashboard.help_search_topics') }}</div>
            <div class="grid grid-cols-1 gap-1 sm:grid-cols-2" data-help-modal-topics>
                @foreach($allSections as $key => $s)
                    @php $active = $key === $sectionKey; @endphp
                    <button type="button" data-help-modal-topic data-topic="{{ $key }}"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-left text-sm transition
                        {{ $active ? 'bg-brand-50 font-semibold text-brand-700 dark:bg-brand-900/20 dark:text-brand-400' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-700' }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="truncate">{{ $s['title'] }}</span>
                    </button>
                @endforeach
            </div>
            <div class="mt-4 flex items-center justify-between gap-3">
                <a href="{{ route('dashboard.help') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    {{ __('dashboard.help_view_docs') }}
                </a>
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('dashboard.help_esc_close') }}</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('help-modal-root');
    if (!root) return;

    var topicsData = {!! json_encode($allSections) !!};
    var topicBtns = root.querySelectorAll('[data-help-modal-topic]');
    var titleEl = document.getElementById('help-modal-title');
    var body = root.querySelector('ol');

    var ESC = function (s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    };

    function openHelp() {
        root.classList.remove('hidden');
        root.classList.add('flex');
        root.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        (root.querySelector('[data-help-modal-close]') || root).focus();
    }

    function closeHelp() {
        root.classList.add('hidden');
        root.classList.remove('flex');
        root.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
    }

    document.querySelector('[data-help-modal-open]')?.addEventListener('click', openHelp);
    document.querySelector('[data-help-modal-close]')?.addEventListener('click', closeHelp);
    root.querySelector('[data-help-modal-backdrop]')?.addEventListener('click', closeHelp);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !root.classList.contains('hidden')) closeHelp();
    });

    topicBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var section = topicsData[btn.dataset.topic] || { title: '', description: '', steps: [] };
            titleEl.textContent = section.title || btn.textContent.trim();
            body.innerHTML = (section.steps || []).map(function (step, i) {
                return '<li class="flex items-start gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">' + (i + 1) + '</span><span class="pt-0.5 text-sm text-slate-700 dark:text-slate-300">' + ESC(step) + '</span></li>';
            }).join('');
            topicBtns.forEach(function (b) {
                b.classList.remove('bg-brand-50', 'font-semibold', 'text-brand-700', 'dark:bg-brand-900/20', 'dark:text-brand-400');
            });
            btn.classList.add('bg-brand-50', 'font-semibold', 'text-brand-700', 'dark:bg-brand-900/20', 'dark:text-brand-400');
        });
    });
});
</script>
@endpush