import './bootstrap';

// ---------- Toast notifications ----------
window.showToast = function (message, type = 'info', duration = 5000) {
    const root = document.getElementById('toast-root');
    if (!root || !message) return;

    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <span class="flex-1">${escapeHtml(message)}</span>
        <button type="button" class="shrink-0 rounded-md p-1 opacity-60 hover:opacity-100" aria-label="Dismiss">&times;</button>
    `;

    const dismiss = () => {
        toast.classList.add('toast--leaving');
        setTimeout(() => toast.remove(), 250);
    };

    toast.querySelector('button').addEventListener('click', dismiss);
    root.appendChild(toast);
    if (duration > 0) setTimeout(dismiss, duration);
};

document.querySelectorAll('[data-flash-toast]').forEach((el) => {
    showToast(el.dataset.message, el.dataset.type || 'info');
});

// ---------- Confirm modal ----------
window.confirmAction = function (options = {}) {
    return new Promise((resolve) => {
        const root = document.getElementById('confirm-modal-root');
        if (!root) { resolve(window.confirm(options.message || 'Are you sure?')); return; }

        const title = root.querySelector('[data-confirm-title]');
        const message = root.querySelector('[data-confirm-message]');
        const okBtn = root.querySelector('[data-confirm-ok]');
        const cancelBtn = root.querySelector('[data-confirm-cancel]');
        const backdrop = root.querySelector('[data-confirm-backdrop]');

        title.textContent = options.title || 'Are you sure?';
        message.textContent = options.message || 'This action cannot be undone.';
        okBtn.textContent = options.confirmLabel || 'Confirm';

        const close = (result) => {
            root.classList.add('hidden');
            root.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            resolve(result);
        };

        const onOk = () => close(true);
        const onCancel = () => close(false);
        const onBackdrop = (e) => { if (e.target === backdrop) close(false); };
        const onKey = (e) => { if (e.key === 'Escape') close(false); };

        okBtn.addEventListener('click', onOk, { once: true });
        cancelBtn.addEventListener('click', onCancel, { once: true });
        backdrop.addEventListener('click', onBackdrop, { once: true });
        document.addEventListener('keydown', onKey, { once: true });

        root.classList.remove('hidden');
        root.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        okBtn.focus();
    });
};

document.addEventListener('submit', async (e) => {
    const form = e.target.closest('form[data-confirm]');
    if (!form) return;
    e.preventDefault();
    const ok = await confirmAction({
        title: form.dataset.confirmTitle || 'Confirm action',
        message: form.dataset.confirm || 'Are you sure?',
        confirmLabel: form.dataset.confirmLabel || 'Confirm',
    });
    if (ok) form.submit();
});

document.addEventListener('click', async (e) => {
    const link = e.target.closest('a[data-confirm]');
    if (!link) return;
    e.preventDefault();
    const ok = await confirmAction({
        title: link.dataset.confirmTitle || 'Confirm action',
        message: link.dataset.confirm || 'Are you sure?',
        confirmLabel: link.dataset.confirmLabel || 'Confirm',
    });
    if (ok) window.location.href = link.href;
});

// ---------- User menu ----------
(function () {
    const root = document.querySelector('[data-user-menu-root]');
    if (!root) return;
    const toggle = root.querySelector('[data-user-menu-toggle]');
    const panel = root.querySelector('[data-user-menu-panel]');

    const close = () => {
        panel.classList.add('hidden');
        toggle.setAttribute('aria-expanded', 'false');
    };
    const open = () => {
        panel.classList.remove('hidden');
        toggle.setAttribute('aria-expanded', 'true');
    };

    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        panel.classList.contains('hidden') ? open() : close();
    });
    document.addEventListener('click', (e) => { if (!root.contains(e.target)) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
})();

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
            list.innerHTML = '<div class="px-4 py-8 text-center text-sm text-slate-500">No notifications</div>';
            return;
        }
        list.innerHTML = items.map(n => `
            <a href="/dashboard/notifications/${n.id}/read"
               class="block border-b border-slate-100 px-4 py-3 transition hover:bg-slate-50 ${n.unread ? 'bg-brand-50/50' : ''}">
                <p class="text-sm font-medium text-slate-900">${escapeHtml(n.title || n.type)}</p>
                ${n.message ? `<p class="mt-0.5 text-xs text-slate-600">${escapeHtml(n.message)}</p>` : ''}
                <p class="mt-1 text-[0.65rem] text-slate-400">${formatTime(n.created_at)}</p>
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
                b.className = 'absolute right-1 top-1 inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-red-600 px-1 text-[0.6rem] font-bold leading-none text-white';
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
        } catch (e) {}
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
        panel.classList.contains('hidden') ? open() : close();
    });

    document.addEventListener('click', (e) => { if (!root.contains(e.target)) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });

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
            if (res.ok) { updateBadge(0); fetchAndRender(); }
        } catch (e) {}
    });

    setInterval(() => {
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : null)
            .then(d => d && updateBadge(d.unread_count))
            .catch(() => {});
    }, 60000);
})();
