<div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
    <p class="font-semibold">{{ __('Global site copy (site-ui)') }}</p>
    <p class="mt-2 text-amber-900/90">
        {{ __('This page merges into the public site. Use a JSON object whose top-level keys mirror') }}
        <code class="rounded bg-white/80 px-1 font-mono text-xs">lang/{locale}/site_frontend.php</code>
        — <code class="font-mono text-xs">nav</code>, <code class="font-mono text-xs">footer</code>, <code class="font-mono text-xs">home</code>,
        <code class="font-mono text-xs">payments</code>, <code class="font-mono text-xs">portal</code>, <code class="font-mono text-xs">auth</code>,
        <code class="font-mono text-xs">news</code>, <code class="font-mono text-xs">gallery</code>, <code class="font-mono text-xs">admissions_apply</code>,
        <code class="font-mono text-xs">admission_status</code>, <code class="font-mono text-xs">fee_receipt</code>, <code class="font-mono text-xs">portal_progress</code>,
        <code class="font-mono text-xs">news_show</code>, <code class="font-mono text-xs">payments_page</code>, <code class="font-mono text-xs">contact_page</code>.
        {{ __('Only include keys you want to override; the rest stay as defaults.') }}
    </p>
    <p class="mt-2 text-amber-900/90">
        {{ __('Per-page body sections (hero blocks, etc.) are still edited under each slug: home, about, academics, admissions, contact, payments, …') }}
    </p>
    <p class="mt-2 text-amber-900/90">
        {{ __('Use the English and Bengali JSON boxes below: Bengali overrides are merged on top of English for visitors who choose বাংলা.') }}
    </p>
</div>
