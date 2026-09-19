<div id="confirm-modal-root" class="hidden" aria-hidden="true">
    <div class="modal-backdrop" data-confirm-backdrop></div>
    <div class="fixed inset-0 z-[91] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title">
        <div class="modal-panel">
            <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-full bg-red-50 text-red-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
            </div>
            <h3 id="confirm-modal-title" class="text-lg font-semibold text-slate-900" data-confirm-title>{{ __('Are you sure?') }}</h3>
            <p class="mt-2 text-sm text-slate-600" data-confirm-message>{{ __('This action cannot be undone.') }}</p>
            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" data-confirm-cancel>
                    {{ __('Cancel') }}
                </button>
                <button type="button" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700" data-confirm-ok>
                    {{ __('Confirm') }}
                </button>
            </div>
        </div>
    </div>
</div>
