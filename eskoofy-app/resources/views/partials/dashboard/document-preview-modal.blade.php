<div id="document-preview-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4" aria-hidden="true">
    <div data-document-preview-backdrop class="modal-backdrop"></div>
    <div class="modal-panel flex h-[85vh] w-full max-w-4xl flex-col overflow-hidden" role="dialog" aria-modal="true" aria-labelledby="document-preview-title">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-700">
            <h2 id="document-preview-title" class="text-base font-semibold text-slate-900 dark:text-white">{{ __('Document preview') }}</h2>
            <div class="flex items-center gap-2">
                <a href="#" data-document-preview-print target="_blank" class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-brand-700">{{ __('Print / Download') }}</a>
                <button type="button" data-document-preview-close class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-300" aria-label="{{ __('Close') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        <div class="flex-1 overflow-hidden bg-slate-100 dark:bg-slate-900">
            <iframe data-document-preview-frame class="h-full w-full border-0" title="{{ __('Document preview') }}"></iframe>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('document-preview-modal');
    if (!modal) return;

    var frame = modal.querySelector('[data-document-preview-frame]');
    var printLink = modal.querySelector('[data-document-preview-print]');

    function open(url) {
        if (!frame || !url) return;
        frame.src = url;
        if (printLink) printLink.href = url.replace('/preview', '/print');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    }

    function close() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        if (frame) frame.src = 'about:blank';
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-preview-url]');
        if (btn) {
            e.preventDefault();
            open(btn.dataset.previewUrl);
        }
    });

    modal.querySelector('[data-document-preview-close]')?.addEventListener('click', close);
    modal.querySelector('[data-document-preview-backdrop]')?.addEventListener('click', close);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });
});
</script>