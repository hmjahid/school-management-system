// Theme switcher — persists user choice in localStorage and applies `dark` class to <html>.
// Runs before any paint to avoid flash of wrong theme.
(function () {
    const STORAGE_KEY = 'school-theme';
    const root = document.documentElement;

    function getPreferred() {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored === 'dark' || stored === 'light') return stored;
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function apply(theme) {
        if (theme === 'dark') {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }
    }

    // Apply immediately (before body renders) to avoid FOUC.
    apply(getPreferred());

    // Expose toggle globally so the switcher button can call it.
    window.schoolTheme = {
        current() { return root.classList.contains('dark') ? 'dark' : 'light'; },
        toggle() {
            const next = this.current() === 'dark' ? 'light' : 'dark';
            localStorage.setItem(STORAGE_KEY, next);
            apply(next);
            document.dispatchEvent(new CustomEvent('theme:changed', { detail: { theme: next } }));
        },
        apply,
    };
})();
