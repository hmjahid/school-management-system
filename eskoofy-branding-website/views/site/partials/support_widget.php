<?php
declare(strict_types=1);

$wSettings = \App\Models\Settings::all();
$wEnabled = (string) ($wSettings['support.widget_enabled'] ?? '1') === '1';
$wBrand = (string) ($wSettings['site.name'] ?? 'Eskoofy');
$wEmail = (string) ($wSettings['site.support_email'] ?? $wSettings['site.contact_email'] ?? '');
$wSales = (string) ($wSettings['site.sales_email'] ?? '');
$wPhone = (string) ($wSettings['site.support_phone'] ?? '');
$wWhatsapp = preg_replace('/[^0-9]/', '', (string) ($wSettings['site.whatsapp'] ?? ''));
$wHours = (string) ($wSettings['site.support_hours'] ?? '');

if (!$wEnabled) {
    return;
}
?>
<style>
    .esk-support-panel { transform: translateY(12px) scale(.98); opacity: 0; visibility: hidden; transition: opacity .18s ease, transform .18s ease, visibility .18s; }
    .esk-support-panel.open { transform: none; opacity: 1; visibility: visible; }
    .esk-support-bubble { transform: translateY(8px); opacity: 0; transition: opacity .2s ease, transform .2s ease; }
    .esk-support-bubble.show { transform: none; opacity: 1; }
</style>

<div class="fixed bottom-5 right-5 z-[55] flex flex-col items-end gap-3" data-support-root>
    <!-- Greeting bubble -->
    <div data-support-bubble class="esk-support-bubble hidden max-w-[15rem] rounded-2xl rounded-br-sm bg-white text-slate-700 shadow-xl border border-slate-200 px-4 py-3 text-sm" role="status">
        <div class="flex items-start gap-2">
            <span class="mt-0.5 inline-flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/></svg>
            </span>
            <span><?= __('support.bubble') ?></span>
            <button type="button" data-support-bubble-close aria-label="<?= __('support.dismiss') ?>" class="ml-1 text-slate-400 hover:text-slate-600">&times;</button>
        </div>
    </div>

    <!-- Panel -->
    <section data-support-panel class="esk-support-panel hidden w-[22rem] max-w-[calc(100vw-2.5rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
             role="dialog" aria-modal="false" aria-labelledby="support-widget-title" tabindex="-1">
        <header class="bg-gradient-to-br from-slate-900 to-blue-700 px-5 py-5 text-white">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="support-widget-title" class="font-bold text-base"><?= __('support.title') ?></h2>
                    <p class="mt-1 text-xs text-slate-300"><?= __('support.subtitle') ?></p>
                </div>
                <button type="button" data-support-close aria-label="<?= __('support.close') ?>" class="rounded-lg p-1.5 text-slate-200 hover:bg-white/10 hover:text-white">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
        </header>

        <div class="max-h-[26rem] overflow-y-auto p-5">
            <div class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-2"><?= __('support.quick_links') ?></div>
            <div class="grid grid-cols-2 gap-2 mb-5">
                <?php foreach ([
                    ['/features', __('support.link_help'), 'M12 2 2 7l10 5 10-5-10-5Zm0 8L2 15l10 5 10-5-10-5Z'],
                    ['/pricing', __('support.link_pricing'), 'M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'],
                    ['/compare', __('support.link_compare'), 'M4 4v16M20 4v16M4 8h6M4 16h6M14 6h6M14 14h6'],
                    ['/contact', __('support.link_demo'), 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z'],
                ] as [$href, $label, $path]): ?>
                    <a href="<?= $href ?>" class="flex flex-col gap-1.5 rounded-xl border border-slate-200 p-3 text-xs font-medium text-slate-600 hover:border-blue-400 hover:text-blue-600 transition">
                        <svg viewBox="0 0 24 24" class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?= $path ?>"/></svg>
                        <?= htmlspecialchars($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-2"><?= __('support.contact_us') ?></div>
            <ul class="space-y-1.5 text-sm">
                <?php if ($wEmail !== ''): ?>
                    <li><a href="mailto:<?= htmlspecialchars($wEmail) ?>" class="flex items-center gap-2.5 rounded-lg px-2 py-2 hover:bg-slate-50 text-slate-600">
                        <svg viewBox="0 0 24 24" class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/></svg>
                        <span class="break-all"><?= htmlspecialchars($wEmail) ?></span>
                    </a></li>
                <?php endif; ?>
                <?php if ($wSales !== ''): ?>
                    <li><a href="mailto:<?= htmlspecialchars($wSales) ?>" class="flex items-center gap-2.5 rounded-lg px-2 py-2 hover:bg-slate-50 text-slate-600">
                        <svg viewBox="0 0 24 24" class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>
                        <span class="break-all"><?= htmlspecialchars($wSales) ?></span>
                    </a></li>
                <?php endif; ?>
                <?php if ($wPhone !== ''): ?>
                    <li><a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $wPhone)) ?>" class="flex items-center gap-2.5 rounded-lg px-2 py-2 hover:bg-slate-50 text-slate-600">
                        <svg viewBox="0 0 24 24" class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/></svg>
                        <span><?= htmlspecialchars($wPhone) ?></span>
                    </a></li>
                <?php endif; ?>
                <?php if ($wWhatsapp !== ''): ?>
                    <li><a href="https://wa.me/<?= htmlspecialchars($wWhatsapp) ?>" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2.5 rounded-lg px-2 py-2 hover:bg-slate-50 text-slate-600">
                        <svg viewBox="0 0 24 24" class="h-4 w-4 text-green-500" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20Zm4.5-5.8c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.5.1l-.7.9c-.1.1-.3.2-.5.1a6.5 6.5 0 0 1-3.2-2.8c-.1-.2 0-.4.1-.5l.5-.6c.1-.2.1-.3 0-.5l-.7-1.7c-.2-.4-.4-.4-.5-.4h-.5a1 1 0 0 0-.7.3c-.3.3-.9.9-.9 2.2s1 2.6 1.1 2.8c.1.2 1.9 3 4.7 4.1 2.3.9 2.8.8 3.3.7.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.2-.2-.4-.2Z"/></svg>
                        <span><?= __('support.whatsapp') ?></span>
                    </a></li>
                <?php endif; ?>
            </ul>

            <?php if ($wHours !== ''): ?>
                <p class="mt-3 text-xs text-slate-400"><?= __('support.hours') ?>: <?= htmlspecialchars($wHours) ?></p>
            <?php endif; ?>

            <a href="/contact" class="mt-4 block rounded-xl bg-blue-600 hover:bg-blue-500 px-4 py-3 text-center text-sm font-semibold text-white transition"><?= __('support.cta') ?></a>
        </div>
    </section>

    <!-- Launcher -->
    <button type="button" data-support-toggle aria-haspopup="dialog" aria-expanded="false" aria-controls="support-widget-title"
            class="group inline-flex items-center gap-2 rounded-full bg-blue-600 hover:bg-blue-500 text-white shadow-xl shadow-blue-600/30 px-4 py-3.5 font-semibold transition">
        <svg data-support-icon-chat viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/></svg>
        <svg data-support-icon-close viewBox="0 0 24 24" class="h-5 w-5 hidden" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
        <span class="text-sm"><?= __('support.launcher') ?></span>
        <span class="sr-only"><?= htmlspecialchars($wBrand) ?></span>
    </button>
</div>

<script>
(function () {
    var root = document.querySelector('[data-support-root]');
    if (!root) return;
    var panel = root.querySelector('[data-support-panel]');
    var toggle = root.querySelector('[data-support-toggle]');
    var closeBtn = root.querySelector('[data-support-close]');
    var bubble = root.querySelector('[data-support-bubble]');
    var bubbleClose = root.querySelector('[data-support-bubble-close]');
    var iconChat = root.querySelector('[data-support-icon-chat]');
    var iconClose = root.querySelector('[data-support-icon-close]');

    function isOpen() { return panel.classList.contains('open'); }

    function open() {
        panel.classList.remove('hidden');
        requestAnimationFrame(function () { panel.classList.add('open'); });
        toggle.setAttribute('aria-expanded', 'true');
        iconChat.classList.add('hidden');
        iconClose.classList.remove('hidden');
        hideBubble(true);
        try { panel.focus(); } catch (e) {}
    }

    function close() {
        panel.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
        iconChat.classList.remove('hidden');
        iconClose.classList.add('hidden');
        setTimeout(function () { if (!isOpen()) panel.classList.add('hidden'); }, 180);
    }

    function hideBubble(remember) {
        if (bubble) bubble.classList.remove('show');
        if (remember) {
            try { localStorage.setItem('esk-support-bubble-dismissed', '1'); } catch (e) {}
        }
    }

    toggle.addEventListener('click', function () { isOpen() ? close() : open(); });
    if (closeBtn) closeBtn.addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && isOpen()) close(); });

    var dismissed = null;
    try { dismissed = localStorage.getItem('esk-support-bubble-dismissed'); } catch (e) {}
    if (bubble && !dismissed) {
        window.setTimeout(function () {
            if (isOpen()) return;
            bubble.classList.remove('hidden');
            requestAnimationFrame(function () { bubble.classList.add('show'); });
            window.setTimeout(function () { hideBubble(false); }, 12000);
        }, 2500);
    }
    if (bubbleClose) bubbleClose.addEventListener('click', function () { hideBubble(true); });
})();
</script>
