import './bootstrap';

// ---------- Notifications dropdown ----------
(function () {
    const root = document.querySelector('[data-notifications-root]');
    if (!root) return;

    const toggle = root.querySelector('[data-notifications-toggle]');
    const panel = root.querySelector('[data-notifications-panel]');
    const list = root.querySelector('[data-notifications-list]');
    const badge = root.querySelector('[data-notifications-badge]');
    const url = list.dataset.url;
    let pollTimer = null;

    function csrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function render(items) {
        if (!items || items.length === 0) {
            list.innerHTML = '<div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No notifications</div>';
            return;
        }
        list.innerHTML = items.map(n => `
            <a href="/dashboard/notifications/${n.id}/read"
               class="block border-b border-gray-100 px-4 py-3 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/30 ${n.unread ? 'bg-blue-50/40 dark:bg-blue-900/10' : ''}">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${escapeHtml(n.title || n.type)}</p>
                ${n.message ? `<p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">${escapeHtml(n.message)}</p>` : ''}
                <p class="mt-0.5 text-[0.65rem] text-gray-500 dark:text-gray-500">${formatTime(n.created_at)}</p>
            </a>
        `).join('');
    }

    function updateBadge(count) {
        if (count > 0) {
            if (badge) {
                badge.textContent = count > 99 ? '99+' : String(count);
            } else {
                const b = document.createElement('span');
                b.setAttribute('data-notifications-badge', '');
                b.className = 'absolute -right-0.5 -top-0.5 inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-red-600 px-1 text-[0.65rem] font-bold leading-none text-white';
                b.textContent = count > 99 ? '99+' : String(count);
                toggle.appendChild(b);
            }
        } else if (badge) {
            badge.remove();
        }
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function formatTime(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        const diff = (Date.now() - d.getTime()) / 1000;
        if (diff < 60) return Math.floor(diff) + 's ago';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        return Math.floor(diff / 86400) + 'd ago';
    }

    async function fetchAndRender() {
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            if (!res.ok) return;
            const data = await res.json();
            render(data.items);
            updateBadge(data.unread_count);
        } catch (e) {
            // network error — leave panel as is
        }
    }

    function open() {
        panel.classList.remove('hidden');
        toggle.setAttribute('aria-expanded', 'true');
        fetchAndRender();
        pollTimer = setInterval(fetchAndRender, 30000);
    }

    function close() {
        panel.classList.add('hidden');
        toggle.setAttribute('aria-expanded', 'false');
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    toggle.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (panel.classList.contains('hidden')) open();
        else close();
    });

    document.addEventListener('click', (e) => {
        if (!root.contains(e.target)) close();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });

    // Mark all read
    root.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-notifications-mark-all]');
        if (!btn) return;
        e.preventDefault();
        try {
            const res = await fetch('/dashboard/notifications/mark-all', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (res.ok) {
                updateBadge(0);
                fetchAndRender();
            }
        } catch (e) {}
    });

    // Light polling in background even when closed (keeps badge fresh)
    setInterval(() => {
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : null)
            .then(d => d && updateBadge(d.unread_count))
            .catch(() => {});
    }, 60000);
})();
