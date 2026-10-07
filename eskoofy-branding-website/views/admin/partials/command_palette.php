<?php
/**
 * Ctrl+K command palette (markup only).
 *
 * Behaviour lives in `public/js/admin.js`: it opens on Ctrl/Cmd+K or `/`, fetches
 * grouped results from `data-palette-endpoint`, and owns ↑/↓/Enter/Esc plus the
 * `aria-activedescendant` wiring. This file only provides the landmarks it
 * queries, so no JavaScript is duplicated here.
 *
 * @see docs/design/BRANDING-ADMIN-DASHBOARD-UX.md §9
 */
$paletteEndpoint = $paletteEndpoint ?? '/admin/palette';
?>
<div data-palette data-palette-endpoint="<?= htmlspecialchars((string) $paletteEndpoint) ?>" hidden>
    <div data-palette-backdrop class="esk-palette-backdrop">
        <div class="esk-palette" role="dialog" aria-modal="true" aria-label="Command palette">
            <div class="esk-palette-search">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input
                    type="text"
                    data-palette-input
                    class="esk-palette-input"
                    placeholder="Search pages, records and actions…"
                    role="combobox"
                    aria-expanded="true"
                    aria-controls="esk-palette-list"
                    aria-autocomplete="list"
                    autocomplete="off"
                    spellcheck="false"
                >
                <button type="button" data-palette-close class="esk-btn esk-btn--sm esk-btn--ghost" aria-label="Close search">Esc</button>
            </div>
            <div id="esk-palette-list" data-palette-list class="esk-palette-list" role="listbox" aria-label="Results"></div>
            <div class="esk-palette-foot">
                <span><span class="esk-kbd">↑</span><span class="esk-kbd">↓</span> navigate</span>
                <span><span class="esk-kbd">↵</span> open</span>
                <span><span class="esk-kbd">esc</span> close</span>
                <span data-palette-live class="esk-sr-only" role="status" aria-live="polite"></span>
            </div>
        </div>
    </div>
</div>
